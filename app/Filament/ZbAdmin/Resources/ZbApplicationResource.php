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

        // 1. Filter out Stage 1 and Waiting stages (Stage 1 is DocumentVerificationResource)
        $query->whereNotIn('current_step', ['pending_review', 'awaiting_document_reupload', 'awaiting_proof_of_employment', 'awaiting_deposit_payment']);

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
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_code')->label('National ID')->searchable(),
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
                    ->form(function (Model $record) {
                        $isSSB = str_starts_with($record->reference_code, 'SSB');
                        
                        $fields = [
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
                                    
                                    // Predefined Assessment Notes
                                    Forms\Components\Select::make('officer_decision')
                                        ->label('Assessment Result')
                                        ->options([
                                            'recommend' => 'Recommend (Everything in order)',
                                            'decline_salary_inconsistency' => 'Declined - due to salary inconsistency',
                                            'decline_salary_insufficiency' => 'Declined - due to salary insufficiency',
                                            'decline_fcb' => 'Declined - due to FCB blacklisting',
                                        ])
                                        ->required()
                                        ->reactive(),
                                    
                                    // FCB Check for ZB
                                    Forms\Components\Select::make('fcb_status')
                                        ->label('FCB Check Status')
                                        ->options([
                                            'good' => 'Good',
                                            'fair' => 'Fair',
                                            'bad' => 'Bad',
                                        ])
                                        ->required()
                                        ->hidden($isSSB),
                                        
                                    // SSB Status for SSB
                                    Forms\Components\Select::make('ssb_status')
                                        ->label('SSB Approval Status')
                                        ->options([
                                            'successful' => 'Successful',
                                            'failed' => 'Failed',
                                        ])
                                        ->required()
                                        ->visible($isSSB),
                                    
                                    // Report Upload (FCB or SSB proof)
                                    Forms\Components\FileUpload::make('verification_report')
                                        ->label($isSSB ? 'SSB Confirmation Report' : 'FCB Report')
                                        ->disk('public')
                                        ->directory('reports')
                                        ->visibility('public'),

                                    Forms\Components\Textarea::make('officer_notes')
                                        ->label('Additional Officer Notes'),
                                ]),
                        ];
                        
                        return $fields;
                    })
                    ->action(function (array $data, Model $record) {
                        $metadata = $record->metadata ?? [];
                        $isSSB = str_starts_with($record->reference_code, 'SSB');
                        
                        $decisionLabels = [
                            'recommend' => 'Recommend (Everything in order)',
                            'decline_salary_inconsistency' => 'Declined - due to salary inconsistency',
                            'decline_salary_insufficiency' => 'Declined - due to salary insufficiency',
                            'decline_fcb' => 'Declined - due to FCB blacklisting',
                        ];

                        $metadata['officer_check'] = [
                            'name' => auth()->user()->name,
                            'designation' => 'Loan Officer',
                            'date' => now()->toIso8601String(),
                            'salary_consistency' => $data['salary_consistency'],
                            'dbr_status' => $data['dbr_status'],
                            'officer_decision' => $data['officer_decision'],
                            'fcb_status' => $data['fcb_status'] ?? null,
                            'ssb_status' => $data['ssb_status'] ?? null,
                            'report_path' => $data['verification_report'] ?? null,
                            'notes' => $data['officer_notes'],
                        ];
                        
                        if ($data['officer_decision'] === 'recommend') {
                            $record->current_step = 'manager_approval';
                            
                            if ($isSSB) {
                                $metadata['client_status_message'] = "Loan officer checked, ssb status successfully approved. Awaiting Approval";
                            } else {
                                $metadata['client_status_message'] = "Loan officer checked and recommended. Awaiting Final Manager Approval.";
                            }
                        } else {
                            $record->current_step = 'rejected';
                            $record->status = 'rejected';
                            $reason = $decisionLabels[$data['officer_decision']];
                            
                            if ($isSSB && $data['ssb_status'] === 'failed') {
                                $metadata['client_status_message'] = "Loan officer ssb check failed because of {$reason}";
                            } else {
                                $metadata['client_status_message'] = "Application declined: {$reason}";
                            }
                        }

                        $record->metadata = $metadata;
                        $record->save();

                        Notification::make()->title('Check Complete. Application status updated.')->success()->send();
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
                                Forms\Components\Placeholder::make('decision_view')
                                    ->label('Officer Decision')
                                    ->content(fn (Model $record) => $record->metadata['officer_check']['officer_decision'] ?? 'N/A'),
                                Forms\Components\Placeholder::make('fcb_ssb_view')
                                    ->label('FCB/SSB Status')
                                    ->content(fn (Model $record) => $record->metadata['officer_check']['fcb_status'] ?? $record->metadata['officer_check']['ssb_status'] ?? 'N/A'),
                                Forms\Components\Placeholder::make('officer_notes_view')
                                    ->label('Officer Assessment Notes')
                                    ->content(fn (Model $record) => $record->metadata['officer_check']['notes'] ?? 'N/A')
                                    ->columnSpanFull(),
                            ])->columns(2),
                        Forms\Components\Textarea::make('manager_notes')->label('Manager Comments'),
                    ])
                    ->action(function (array $data, Model $record) {
                        try {
                            $metadata = $record->metadata ?? [];
                            $metadata['manager_approval'] = [
                                'name' => auth()->user()->name,
                                'designation' => 'Branch Manager',
                                'date' => now()->toIso8601String(),
                                'notes' => $data['manager_notes'] ?? '',
                            ];
                            
                            $record->current_step = 'approved';
                            $record->status = 'approved';
                            
                            // Only set approved_at if the column exists (safeguard)
                            if (\Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'approved_at')) {
                                $record->approved_at = now();
                            }
                            
                            $metadata['client_status_message'] = "Loan application approved, Delivery Process has been initiated";
                            $record->metadata = $metadata;
                            $record->save();

                            // Trigger PO and Delivery
                            $poService = app(\App\Services\PurchaseOrderService::class);
                            $poService->createFromApplication($record);
                            
                            // Initialize delivery tracking
                            $formResponses = $record->form_data['formResponses'] ?? [];
                            $deliverySelection = $record->form_data['deliverySelection'] ?? [];
                            $depot = $deliverySelection['depot'] ?? $deliverySelection['city'] ?? 'Default Depot';

                            \App\Models\DeliveryTracking::create([
                                'application_state_id' => $record->id,
                                'status' => 'pending',
                                'recipient_name' => trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))),
                                'recipient_phone' => $formResponses['mobile'] ?? $formResponses['cellNumber'] ?? $record->user_identifier,
                                'client_national_id' => $formResponses['nationalIdNumber'] ?? $record->reference_code,
                                'product_type' => $record->form_data['productName'] ?? 'Product',
                                'courier_type' => $deliverySelection['agent'] ?? 'Courier',
                                'delivery_depot' => $depot,
                                'delivery_address' => $depot,
                            ]);

                            Notification::make()->title('Application Fully Approved!')->success()->send();
                        } catch (\Throwable $e) {
                            Log::error("Manager approval failed: " . $e->getMessage(), [
                                'exception' => $e,
                                'application_id' => $record->id
                            ]);
                            
                            Notification::make()
                                ->title('Approval Error')
                                ->body('The application was approved but post-approval services failed: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
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
