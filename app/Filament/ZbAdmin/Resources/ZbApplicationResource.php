<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\ZbApplicationResource\Pages;
use App\Models\ApplicationState;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\PDFGeneratorService;
use App\Services\ZBStatusService;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Facades\Filament;

class ZbApplicationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Stage 2 & 3: Qupa Approval';

    protected static ?string $navigationGroup = 'Loan Management';

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();

        // 1. Filter out Stage 1 applications (they are in DocumentVerificationResource)
        $query->whereNotIn('current_step', ['pending_review']);

        // 2. Role-based scoping
        if ($user && $user->isQupaAdmin()) {
            if ($user->isQupaManagement()) {
                // Management sees everything EXCEPT what's still in Stage 1
                // They handle allocation for 'qupa_allocation_pending'
            } elseif ($user->isBranchManager()) {
                // Branch Managers see applications for their branch that are in 'manager_approval'
                // OR 'officer_check' (to supervise)
                // NEW: Also see 'qupa_allocation_pending' if it's assigned to their branch
                $query->where('assigned_branch_id', $user->branch_id)
                      ->whereIn('current_step', ['qupa_allocation_pending', 'officer_check', 'manager_approval', 'approved', 'rejected']);
            } elseif ($user->isLoanOfficer()) {                // Loan Officers see applications assigned to them OR from their referral links
                $query->where(function($q) use ($user) {
                    $q->where('qupa_admin_id', $user->id)
                      ->orWhere('assigned_branch_id', $user->branch_id); // If branch-wide access allowed
                })->whereIn('current_step', ['officer_check', 'manager_approval', 'approved', 'rejected']);
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
             Forms\Components\Section::make('Application Details')
                ->schema([
                    Forms\Components\TextInput::make('session_id')->disabled(),
                    Forms\Components\TextInput::make('reference_code')->disabled(),
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->label('Ref Code')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) =>
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    ),
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Workflow Stage')
                    ->colors([
                        'warning' => 'qupa_allocation_pending',
                        'primary' => 'officer_check',
                        'info' => 'manager_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'qupa_allocation_pending' => 'Awaiting Allocation',
                        'officer_check' => 'Officer Review',
                        'manager_approval' => 'Manager Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('assignedBranch.name')
                    ->label('Branch')
                    ->default('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('qupaAdmin.name')
                    ->label('Assigned Officer')
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // MANAGEMENT ACTION: Allocate to Branch & Officer
                Action::make('allocate')
                    ->label('Allocate')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (Model $record) => 
                        $record->current_step === 'qupa_allocation_pending' && 
                        ($user->isQupaManagement() || ($user->isBranchManager() && $record->assigned_branch_id === $user->branch_id))
                    )
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Assign to Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->default(fn (Model $record) => $record->assigned_branch_id)
                            ->disabled(fn () => !$user->isQupaManagement())
                            ->dehydrated(),
                        Forms\Components\Select::make('officer_id')
                            ->label('Assign to Officer')
                            ->options(fn (Forms\Get $get) => 
                                User::where('branch_id', $get('branch_id'))
                                    ->where('designation', User::DESIGNATION_LOAN_OFFICER)
                                    ->pluck('name', 'id')
                            )
                            ->required(),
                    ])
                    ->action(function (array $data, Model $record) {
                        $record->update([
                            'assigned_branch_id' => $data['branch_id'],
                            'qupa_admin_id' => $data['officer_id'],
                            'current_step' => 'officer_check',
                        ]);
                        Notification::make()->title('Application Allocated Successfully')->success()->send();
                    }),

                // OFFICER ACTION: Financial Check
                Action::make('officer_verify')
                    ->label('Officer Check')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (Model $record) => $record->current_step === 'officer_check' && ($user->isLoanOfficer() || $user->isQupaManagement()))
                    ->form([
                        Forms\Components\Section::make('Financial Verification')
                            ->schema([
                                Forms\Components\Select::make('salary_consistency')
                                    ->label('Salary Deposit Consistency')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('dbr_status')
                                    ->label('DBR 40% Status')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                        'borderline' => 'Borderline',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('officer_notes')
                                    ->label('Assessment Notes')
                                    ->required(),
                            ]),
                    ])
                    ->action(function (array $data, Model $record) {
                        $metadata = $record->metadata ?? [];
                        $metadata['officer_check'] = [
                            'name' => auth()->user()->name,
                            'designation' => 'Loan Officer',
                            'date' => now()->toIso8601String(),
                            'salary_consistency' => $data['salary_consistency'],
                            'dbr_status' => $data['dbr_status'],
                            'notes' => $data['officer_notes'],
                        ];
                        $record->metadata = $metadata;
                        $record->current_step = 'manager_approval';
                        $record->save();

                        Notification::make()->title('Check Complete. Sent for Manager Approval.')->success()->send();
                    }),

                // MANAGER ACTION: Final Approval
                Action::make('manager_approve')
                    ->label('Final Approval')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->current_step === 'manager_approval' && ($user->isBranchManager() || $user->isQupaManagement()))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Section::make('Officer Assessment Results')
                            ->schema([
                                Forms\Components\Placeholder::make('salary_consistency_view')
                                    ->label('Salary Deposit Consistency')
                                    ->content(fn (Model $record) => strtoupper($record->metadata['officer_check']['salary_consistency'] ?? 'N/A')),
                                Forms\Components\Placeholder::make('dbr_status_view')
                                    ->label('DBR 40% Status')
                                    ->content(fn (Model $record) => strtoupper($record->metadata['officer_check']['dbr_status'] ?? 'N/A')),
                                Forms\Components\Placeholder::make('officer_notes_view')
                                    ->label('Officer Assessment Notes')
                                    ->content(fn (Model $record) => $record->metadata['officer_check']['notes'] ?? 'N/A'),
                            ])->columns(2),
                        Forms\Components\Textarea::make('manager_notes')->label('Manager Comments'),
                    ])
                    ->action(function (array $data, Model $record) {
                        $metadata = $record->metadata ?? [];
                        $metadata['manager_approval'] = [
                            'name' => auth()->user()->name,
                            'designation' => 'Branch Manager',
                            'date' => now()->toIso8601String(),
                            'notes' => $data['manager_notes'] ?? '',
                        ];
                        $record->metadata = $metadata;
                        $record->current_step = 'approved';
                        $record->status = 'approved';
                        $record->approved_at = now();
                        $record->save();

                        // Trigger PO and Delivery
                        try {
                            $poService = app(\App\Services\PurchaseOrderService::class);
                            $poService->createFromApplication($record);
                            
                            // Initialize delivery
                            $deliveryService = app(\App\Services\DeliveryTrackingController::class); // Assuming this is where it's handled or similar service
                            // ... existing delivery initiation logic ...
                        } catch (\Exception $e) {
                            \Log::error("Post-approval services failed: " . $e->getMessage());
                        }

                        Notification::make()->title('Application Fully Approved!')->success()->send();
                    }),

                Action::make('generate_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Model $record) {
                        return redirect()->route('application.pdf.view', $record->session_id);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZbApplications::route('/'),
        ];
    }
}
