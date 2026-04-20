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
            } elseif ($user->isBranchManager()) {
                // Branch Managers see applications for their branch
                $query->where('assigned_branch_id', $user->branch_id)
                      ->whereIn('current_step', [
                          'vlc_allocation_pending', 
                          'awaiting_ssb_csv_export',
                          'awaiting_ssb_approval',
                          'qupa_allocation_pending', 
                          'officer_check', 
                          'manager_approval', 
                          'approved', 
                          'rejected'
                      ]);
            } elseif ($user->isLoanOfficer()) {
                $query->where(function($q) use ($user) {
                    $q->where('qupa_admin_id', $user->id)
                      ->orWhere('assigned_branch_id', $user->branch_id);
                })->whereIn('current_step', [
                    'awaiting_ssb_csv_export',
                    'awaiting_ssb_approval',
                    'officer_check', 
                    'manager_approval', 
                    'approved', 
                    'rejected'
                ]);
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
                        'warning' => fn ($state) => in_array($state, ['qupa_allocation_pending', 'vlc_allocation_pending']),
                        'primary' => fn ($state) => in_array($state, ['officer_check', 'awaiting_ssb_csv_export']),
                        'info' => fn ($state) => in_array($state, ['manager_approval', 'awaiting_ssb_approval']),
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'vlc_allocation_pending' => 'VLC Allocation',
                        'awaiting_ssb_csv_export' => 'Awaiting SSB Batch',
                        'awaiting_ssb_approval' => 'Sent to SSB',
                        'qupa_allocation_pending' => 'Awaiting Allocation',
                        'officer_check' => 'Officer Review',
                        'manager_approval' => 'Manager Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => ucwords(str_replace('_', ' ', $state)),
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

                // VLC ALLOCATION ACTION
                Action::make('vlc_allocate')
                    ->label('VLC Allocate')
                    ->icon('heroicon-o-building-office')
                    ->color('warning')
                    ->visible(fn (Model $record) => 
                        $record->current_step === 'vlc_allocation_pending' && 
                        ($user->isQupaManagement() || ($user->isBranchManager() && $record->assigned_branch_id === $user->branch_id))
                    )
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Assign to Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->default(fn (Model $record) => $record->assigned_branch_id)
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
                            'current_step' => 'awaiting_ssb_csv_export',
                        ]);
                        
                        app(\App\Services\SSBStatusService::class)->updateStatus(
                            $record, 
                            \App\Enums\SSBLoanStatus::AWAITING_SSB_CSV_EXPORT,
                            "Allocated to branch {$record->assignedBranch->name} and officer {$record->qupaAdmin->name}"
                        );

                        Notification::make()->title('VLC Allocation Successful')->success()->send();
                    }),

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
                            $record->metadata = $metadata;
                            $record->save();

                            $workflowService = app(\App\Services\ApplicationWorkflowService::class);
                            $success = $workflowService->approveApplication($record, ['notes' => $data['manager_notes'] ?? null]);
                            
                            if ($success) {
                                $formData = $record->form_data ?? [];
                                $creditType = $formData['creditType'] ?? '';
                                $isPDC = str_starts_with($creditType, 'PDC');
                                
                                $message = $isPDC 
                                    ? 'Application approved and moved to Stage 4 (Awaiting Deposit).' 
                                    : 'Application fully approved! Delivery initiated.';
                                    
                                Notification::make()
                                    ->title($message)
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Approval Failed')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Log::error("Manager approval failed: " . $e->getMessage(), [
                                'exception' => $e,
                                'application_id' => $record->id
                            ]);
                            
                            Notification::make()
                                ->title('Approval Error')
                                ->body($e->getMessage())
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
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_ssb_responses')
                    ->label('Import SSB Responses')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('ssb_csv')
                            ->label('SSB CSV File')
                            ->required()
                            ->disk('local')
                            ->directory('temp_ssb_imports'),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('local')->path($data['ssb_csv']);
                        $ssbService = app(\App\Services\SSBStatusService::class);
                        
                        $results = $ssbService->parseAndProcessSSBCSV($filePath);
                        
                        Notification::make()
                            ->title('SSB Import Complete')
                            ->body("Processed: {$results['processed']}, Failed: {$results['failed']}")
                            ->success()
                            ->send();
                        
                        // Delete temp file (cleanup)
                        try {
                            Storage::disk('local')->delete($data['ssb_csv']);
                        } catch (\Exception $e) {
                            Log::warning("Failed to delete temporary SSB import file: " . $e->getMessage());
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_as_ssb_sent')
                        ->label('Export to SSB (Mark as Sent)')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $ssbService = app(\App\Services\SSBStatusService::class);
                            $count = 0;
                            
                            foreach ($records as $record) {
                                if ($record->current_step === 'awaiting_ssb_csv_export') {
                                    $ssbService->markAsExported($record);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("Batch Exported")
                                ->body("{$count} applications marked as 'Sent to SSB'.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZbApplications::route('/'),
        ];
    }
}
