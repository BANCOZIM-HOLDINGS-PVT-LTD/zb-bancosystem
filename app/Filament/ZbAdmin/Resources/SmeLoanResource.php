<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\SmeLoanResource\Pages;
use App\Models\ApplicationState;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

class SmeLoanResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'SME Booster Applications';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        $user = Filament::auth()->user();

        $query = parent::getEloquentQuery()
            ->where(function ($query) use ($isPgsql) {
                // Match by application_type column
                $query->where('application_type', 'sme');

                // OR match by form_data intent/employer (fallback for older data)
                if ($isPgsql) {
                    $query->orWhereRaw("form_data->>'intent' = 'smeBiz'")
                          ->orWhereRaw("form_data->>'employer' = 'sme-business'")
                          ->orWhereRaw("metadata->>'workflow_type' = 'sme'");
                } else {
                    $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.intent')) = 'smeBiz'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'sme-business'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.workflow_type')) = 'sme'");
                }
            })
            ->orderBy('created_at', 'desc');

        // Branch-scoping for Qupa Admin users
        if ($user && $user->isQupaAdmin()) {
            if ($user->isLoanOfficer() || $user->isBranchManager()) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_branch_id', $user->branch_id)
                      ->orWhere('qupa_admin_id', $user->id);
                });
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('current_step')
                            ->label('Status')
                            ->disabled(),
                    ])->columns(2),
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

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('National ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data;
                        $responses = $formData['formResponses'] ?? $formData;
                        $firstName = $responses['firstName'] ?? '';
                        $lastName = $responses['lastName'] ?? $responses['surname'] ?? '';
                        return trim("{$firstName} {$lastName}") ?: 'N/A';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('company_type')
                    ->label('Company Type')
                    ->getStateUsing(function ($record) {
                        $metadata = $record->metadata ?? [];
                        return $metadata['company_type_name'] ?? ($record->form_data['companyTypeName'] ?? 'N/A');
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('business')
                    ->label('Product/Business')
                    ->getStateUsing(function ($record) {
                        return $record->form_data['productName']
                            ?? $record->form_data['business']
                            ?? 'N/A';
                    }),

                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->getStateUsing(function ($record) {
                        $amount = $record->form_data['grossLoan']
                                ?? $record->form_data['finalPrice']
                                ?? $record->form_data['amount']
                                ?? 0;
                        return '$' . number_format($amount, 2);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sme_stage')
                    ->label('SME Stage')
                    ->getStateUsing(function ($record) {
                        $metadata = $record->metadata ?? [];
                        $stage = $metadata['sme_stage'] ?? 'submitted';
                        return match ($stage) {
                            'submitted' => 'Submitted',
                            'sme_document_review' => 'Doc Review',
                            'sme_credit_assessment' => 'Credit Assessment',
                            'sme_committee_review' => 'Committee',
                            'sme_pending_documents' => 'Pending Docs',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => ucfirst(str_replace('_', ' ', $stage)),
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Committee' => 'warning',
                        'Credit Assessment' => 'info',
                        'Pending Docs' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'in_review',
                        'danger' => 'rejected',
                        'primary' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('assignedBranch.name')
                    ->label('Branch')
                    ->default('Unassigned')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_step')
                    ->label('Status')
                    ->options([
                        'pending_review' => 'Pending Review',
                        'in_review' => 'In Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_branch_id')
                    ->label('Branch')
                    ->options(Branch::active()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // Advance to Document Review
                Action::make('start_review')
                    ->label('Start Review')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return in_array($stage, ['submitted', 'sme_pending_documents']);
                    })
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'sme_document_review');
                        
                        Notification::make()
                            ->title($success ? 'Document review started' : 'Failed to advance stage')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Advance to Credit Assessment
                Action::make('credit_assessment')
                    ->label('Credit Assessment')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return $stage === 'sme_document_review';
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Review Notes')
                            ->placeholder('Document review findings...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'sme_credit_assessment', [
                            'notes' => $data['notes'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title($success ? 'Moved to Credit Assessment' : 'Failed to advance stage')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Advance to Committee Review
                Action::make('committee_review')
                    ->label('Send to Committee')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return $stage === 'sme_credit_assessment';
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Assessment Notes')
                            ->placeholder('Credit assessment findings...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'sme_committee_review', [
                            'notes' => $data['notes'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title($success ? 'Sent to Committee Review' : 'Failed to advance stage')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Approve Application
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return $stage === 'sme_committee_review';
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Approval Notes')
                            ->placeholder('Committee decision notes...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'approved', [
                            'notes' => $data['notes'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title($success ? 'Application approved!' : 'Approval failed')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Request Documents
                Action::make('request_documents')
                    ->label('Request Documents')
                    ->icon('heroicon-o-document-plus')
                    ->color('secondary')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return in_array($stage, ['sme_document_review', 'sme_credit_assessment']);
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('requested_documents')
                            ->label('Required Documents')
                            ->required()
                            ->placeholder('Please list the specific documents the applicant needs to provide...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'sme_pending_documents', [
                            'requested_documents' => $data['requested_documents'],
                        ]);
                        
                        Notification::make()
                            ->title($success ? 'Documents requested' : 'Failed to request documents')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Reject Application (available at any stage)
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Model $record) {
                        $stage = $record->metadata['sme_stage'] ?? 'submitted';
                        return !in_array($stage, ['approved', 'rejected']);
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide a reason for rejection...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $smeService = app(\App\Services\SMEApplicationWorkflowService::class);
                        $success = $smeService->advanceToStage($record, 'rejected', [
                            'reason' => $data['reason'],
                        ]);
                        
                        Notification::make()
                            ->title($success ? 'Application rejected' : 'Rejection failed')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),

                // Refer to Branch
                Action::make('refer_to_branch')
                    ->label('Refer to Branch')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(function (Model $record) {
                        $user = Filament::auth()->user();
                        if (!$user) return false;
                        return ($user->isQupaManagement() || $user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ZB_ADMIN)
                            && !$record->assigned_branch_id;
                    })
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Refer to Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Model $record) {
                        $record->update(['assigned_branch_id' => $data['branch_id']]);
                        $branch = Branch::find($data['branch_id']);
                        Notification::make()
                            ->title('Application Referred')
                            ->body("Referred to {$branch->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => false), // Disabled for safety
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmeLoans::route('/'),
            'view' => Pages\ViewSmeLoan::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
