<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProofVerificationResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProofVerificationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Stage 1B: Proof Verification';

    protected static ?string $modelLabel = 'Proof Verification';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?string $slug = 'stage-1b-proof-verification';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('current_step', ['awaiting_deposit_payment', 'awaiting_proof_of_employment']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('National ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) =>
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    ),
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Proof Type')
                    ->colors([
                        'warning' => 'awaiting_deposit_payment',
                        'info' => 'awaiting_proof_of_employment',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'awaiting_deposit_payment' => 'Deposit Payment',
                        'awaiting_proof_of_employment' => 'Employment Proof',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('proof_status')
                    ->label('Client Upload')
                    ->getStateUsing(function (Model $record) {
                        $resubmissions = $record->metadata['resubmissions'] ?? [];
                        if (empty($resubmissions)) {
                            return 'awaiting_upload';
                        }
                        return 'uploaded';
                    })
                    ->colors([
                        'danger' => 'awaiting_upload',
                        'success' => 'uploaded',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'awaiting_upload' => 'Awaiting Client Upload',
                        'uploaded' => 'Uploaded — Ready for Review',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                // View the uploaded proof documents
                Action::make('view_documents')
                    ->label('View Application')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Model $record) => route('application.pdf.view', $record->session_id))
                    ->openUrlInNewTab(),

                // Confirm proof — advances to qupa_allocation_pending
                Action::make('confirm_proof')
                    ->label('Confirm Proof')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Proof Submission')
                    ->modalDescription('Confirm that the client\'s uploaded proof is valid and complete. This will advance the application to the allocation stage.')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Verification Notes')
                            ->placeholder('Any observations about the proof submitted...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $metadata = $record->metadata ?? [];
                        $proofType = $record->current_step === 'awaiting_deposit_payment' ? 'deposit_payment' : 'employment_proof';

                        $metadata['proof_verified'] = [
                            'verified_at' => now()->toIso8601String(),
                            'verified_by' => auth()->user()->name,
                            'type' => $proofType,
                            'notes' => $data['notes'] ?? '',
                        ];

                        $record->current_step = 'qupa_allocation_pending';
                        $record->status = 'proof_verified';
                        $metadata['client_status_message'] = "Proof verified. Your application is being allocated for review.";
                        $record->metadata = $metadata;
                        $record->save();

                        Notification::make()
                            ->title('Proof confirmed. Application moved to allocation.')
                            ->success()
                            ->send();
                    }),

                // Reject proof — keeps current_step, asks client to re-upload
                Action::make('reject_proof')
                    ->label('Reject Proof')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->placeholder('Explain why the proof is insufficient...')
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $metadata = $record->metadata ?? [];
                        $proofType = $record->current_step === 'awaiting_deposit_payment' ? 'deposit payment' : 'employment proof';

                        $metadata['proof_rejection'] = [
                            'reason' => $data['rejection_reason'],
                            'rejected_at' => now()->toIso8601String(),
                            'rejected_by' => auth()->user()->name,
                        ];
                        // Clear resubmissions so client can re-upload
                        $metadata['resubmissions'] = [];
                        $metadata['client_status_message'] = "Your {$proofType} was not accepted: {$data['rejection_reason']}. Please re-upload.";
                        $record->status = 'proof_rejected';
                        $record->metadata = $metadata;
                        $record->save();

                        Notification::make()
                            ->title('Proof rejected. Client will be notified to re-upload.')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProofVerifications::route('/'),
        ];
    }
}
