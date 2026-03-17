<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentVerificationResource\Pages;
use App\Models\ApplicationState;
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

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Stage 1: Doc Verification';
    
    protected static ?string $modelLabel = 'Bancozim Verification';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?string $slug = 'stage-1-verification';

    public static function getEloquentQuery(): Builder
    {
        // Bancozim Admin sees all applications in 'pending_review' stage
        return parent::getEloquentQuery()
            ->where('current_step', 'pending_review');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Tables\Columns\TextColumn::make('reference_code')->label('Ref Code')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) => 
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('qupaAdmin.name')
                    ->label('Source')
                    ->default('General (No Link)')
                    ->colors([
                        'primary' => fn ($state) => $state !== 'General (No Link)',
                        'secondary' => 'General (No Link)',
                    ]),
            ])
            ->actions([
                Action::make('verify_documents')
                    ->label('Verify Documents')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Section::make('Document Checklist')
                            ->description('Check all documents that are clear and usable.')
                            ->schema([
                                Forms\Components\Checkbox::make('doc_id')->label('National ID Card'),
                                Forms\Components\Checkbox::make('doc_payslip')->label('Latest Payslip'),
                                Forms\Components\Checkbox::make('doc_residence')->label('Proof of Residence'),
                                Forms\Components\Checkbox::make('doc_photo')->label('Passport Photo / Selfie'),
                                Forms\Components\Checkbox::make('doc_statement')->label('Bank Statement (if applicable)'),
                            ]),
                        Forms\Components\Textarea::make('bancozim_notes')
                            ->label('Bancozim Admin Notes')
                            ->placeholder('Any observations about the documents...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $metadata = $record->metadata ?? [];
                        $metadata['bancozim_verification'] = [
                            'verified_at' => now()->toIso8601String(),
                            'verified_by' => auth()->user()->name,
                            'docs' => [
                                'id' => $data['doc_id'],
                                'payslip' => $data['doc_payslip'],
                                'residence' => $data['doc_residence'],
                                'photo' => $data['doc_photo'],
                                'statement' => $data['doc_statement'],
                            ],
                            'notes' => $data['bancozim_notes'] ?? '',
                        ];
                        
                        $record->metadata = $metadata;

                        // Routing Logic
                        if ($record->qupa_admin_id) {
                            // Route A: Referral Link -> Goes to the specific Loan Officer
                            $record->current_step = 'officer_check';
                            $message = "Documents verified. Sent to Loan Officer: " . ($record->qupaAdmin->name ?? 'Assigned Officer');
                        } else {
                            // Route B: General -> Goes to Main Qupa Admin for allocation
                            $record->current_step = 'qupa_allocation_pending';
                            $message = "Documents verified. Sent to Qupa Management for allocation.";
                        }

                        $record->status = 'documents_verified';
                        $record->save();

                        // Notify Client (Optional Service call here)
                        try {
                             $notificationService = app(\App\Services\NotificationService::class);
                             $notificationService->sendStatusUpdateNotification($record, 'pending_review', $record->current_step);
                        } catch (\Exception $e) {
                             \Log::error("Failed to send status update notification: " . $e->getMessage());
                        }

                        Notification::make()->title($message)->success()->send();
                    }),

                Action::make('reject_documents')
                    ->label('Reject / Missing Docs')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->current_step = 'rejected';
                        $record->status = 'rejected';
                        $metadata = $record->metadata ?? [];
                        $metadata['rejection_details'] = [
                            'reason' => $data['rejection_reason'],
                            'stage' => 'Bancozim Document Verification',
                            'at' => now()->toIso8601String(),
                            'by' => auth()->user()->name,
                        ];
                        $record->metadata = $metadata;
                        $record->save();

                        Notification::make()->title('Application Rejected')->danger()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentVerifications::route('/'),
        ];
    }
}
