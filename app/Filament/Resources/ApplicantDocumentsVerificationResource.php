<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicantDocumentsVerificationResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApplicantDocumentsVerificationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Applicant Documents Verif.';
    
    protected static ?string $modelLabel = 'Verification';

    protected static ?string $navigationGroup = 'Document Management';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('current_step', ['zb_verification_pending', 'zb_approval_pending']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Application Data')
                ->schema([
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->label('Ref')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) => 
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['surname'] ?? ''))
                    ),
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Stage')
                    ->colors([
                        'warning' => 'zb_verification_pending', // Checker stage
                        'primary' => 'zb_approval_pending',     // Approver stage
                    ]),
                Tables\Columns\TextColumn::make('check_result.status')
                    ->label('FCB Status')
                    ->badge()
                    ->colors([
                        'success' => 'GOOD',
                        'success' => 'CLEAN',
                        'danger' => 'ADVERSE',
                        'danger' => 'DEFAULT',
                    ]),
            ])
            ->actions([
                // CHECKER ACTION
                Action::make('checker_verify')
                    ->label('Check Documents')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Model $record) => $record->current_step === 'zb_verification_pending')
                    ->form([
                        Forms\Components\Section::make('Verification Checklist')
                            ->schema([
                                Forms\Components\Radio::make('paid_deposit_consistency')
                                    ->label('Paid Deposit Consistency')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No'
                                    ])
                                    ->required(),
                                Forms\Components\Radio::make('installment_sufficiency')
                                    ->label('Installment Sufficiency (DBR 40%)')
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                        'borderline' => 'Borderline'
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\Section::make('Checker Details')
                            ->schema([
                                Forms\Components\TextInput::make('checker_name')
                                    ->label('Name')
                                    ->required(),
                                Forms\Components\TextInput::make('checker_designation')
                                    ->label('Designation')
                                    ->required(),
                                Forms\Components\TextInput::make('checker_branch')
                                    ->label('Branch')
                                    ->required(),
                            ])
                    ])
                    ->action(function (Model $record, array $data) {
                        try {
                            $isRejected = $data['paid_deposit_consistency'] === 'no' || $data['installment_sufficiency'] === 'no';
                            
                            // Update metadata with checker details
                            $metadata = $record->metadata ?? [];
                            $metadata['zb_checker'] = [
                                'name' => $data['checker_name'],
                                'designation' => $data['checker_designation'],
                                'branch' => $data['checker_branch'],
                                'date' => now()->toIso8601String(),
                                'checks' => [
                                    'paid_deposit' => $data['paid_deposit_consistency'],
                                    'installment' => $data['installment_sufficiency']
                                ]
                            ];
                            
                            $record->metadata = $metadata;
                            
                            if ($isRejected) {
                                $record->current_step = 'rejected';
                                $record->save();
                                Notification::make()->title('Application Rejected by Checker')->success()->send();
                                
                                // Send Notification
                                $notificationService = app(\App\Services\NotificationService::class);
                                $notificationService->sendStatusUpdateNotification($record, 'zb_verification_pending', 'rejected');
                            } else {
                                $record->current_step = 'zb_approval_pending';
                                $record->save();
                                Notification::make()->title('Checks Complete - Sent to Approver')->success()->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // APPROVER ACTION
                Action::make('approver_decide')
                    ->label('Final Approval')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->current_step === 'zb_approval_pending')
                    ->form([
                        Forms\Components\Placeholder::make('checker_info')
                            ->label('Checker Information')
                            ->content(fn (Model $record) => 
                                "Checked by: " . ($record->metadata['zb_checker']['name'] ?? 'N/A') . 
                                "\nDesignation: " . ($record->metadata['zb_checker']['designation'] ?? 'N/A') .
                                "\nBranch: " . ($record->metadata['zb_checker']['branch'] ?? 'N/A')
                            ),
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Notes'),
                    ])
                    ->action(function (Model $record, array $data) {
                         // Approve logic
                         $poService = app(\App\Services\PurchaseOrderService::class);
                         $poService->createFromApplication($record);
                         
                         $workflow = app(\App\Services\ApplicationWorkflowService::class);
                         $workflow->approveApplication($record, ['notes' => $data['approval_notes'] ?? 'Approved by ZB Admin']);
                         
                         // Update PDF Signature metadata
                         $metadata = $record->metadata ?? [];
                         $metadata['zb_approver'] = [
                             'name' => auth()->user()->name,
                             'date' => now()->toIso8601String(),
                         ];
                         $record->metadata = $metadata;
                         $record->save();
                         
                         Notification::make()->title('Application Approved & PO Created')->success()->send();
                    }),
                    
                Action::make('approver_reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record) => $record->current_step === 'zb_approval_pending')
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $workflow = app(\App\Services\ApplicationWorkflowService::class);
                        $workflow->rejectApplication($record, 'Rejected by ZB Approver');
                        Notification::make()->title('Application Rejected')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplicantDocumentsVerifications::route('/'),
        ];
    }
}
