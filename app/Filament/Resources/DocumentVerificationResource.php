<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentVerificationResource\Pages;
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
use Illuminate\Support\Facades\Log;

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Document Manual Verification';
    
    protected static ?string $modelLabel = 'Verification Pending';

    protected static ?string $navigationGroup = 'Document Management';

    protected static ?string $slug = 'document-verification';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('current_step', 'pending_verification');
    }

    public static function form(Form $form): Form
    {
        // View-only form for modal context if needed
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
                Tables\Columns\BadgeColumn::make('channel'),
            ])
            ->actions([
                // View Documents Action
                Action::make('view_documents')
                    ->label('View Documents')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Submitted Documents')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (Model $record) {
                        return view('filament.forms.components.application-documents-list', [
                            'documents' => $record->form_data['documents'] ?? $record->form_data['documentsByType'] ?? []
                        ]);
                    }),

                // Approve Action
                Action::make('approve')
                    ->label('Approve & Check')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Documents')
                    ->modalDescription('Are you sure the documents are valid? This will trigger automated checks.')
                    ->action(function (Model $record) {
                        try {
                            // 1. Update status to processing
                            $record->current_step = 'processing';
                            $record->save();
                            
                            // 2. Trigger Automated Checks
                            $checkService = app(\App\Services\AutomatedCheckService::class);
                            $checkService->executeAutomatedChecks($record);
                            
                            // 3. Update status to sent_for_checks
                            $record->current_step = 'sent_for_checks';
                            $record->save();
                            
                            Notification::make()
                                ->title('Documents Approved')
                                ->body('Application sent for automated checks.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                             Log::error('Verification Approval Failed', ['id' => $record->id, 'error' => $e->getMessage()]);
                             Notification::make()
                                ->title('Error')
                                ->body('Failed to process approval: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Decline Action
                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Rejection')
                            ->default('Application declined due to incorrect documents sent, please try again and upload the correct documents.')
                            ->required()
                    ])
                    ->action(function (array $data, Model $record) {
                        try {
                            // Update status
                            $record->current_step = 'rejected';
                            $record->status_updated_at = now();
                            $record->save();
                            
                            // Send SMS
                            $formData = $record->form_data;
                            $phone = $formData['formResponses']['mobile'] ??
                                     $formData['formResponses']['phoneNumber'] ??
                                     $formData['formResponses']['contactPhone'] ?? null;
                                     
                            if ($phone) {
                                $smsService = app(\App\Services\SMSService::class);
                                $smsService->sendSMS($phone, $data['reason']);
                            }
                            
                            Notification::make()
                                ->title('Application Declined')
                                ->body('Applicant notified via SMS.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                             Log::error('Verification Decline Failed', ['id' => $record->id, 'error' => $e->getMessage()]);
                             Notification::make()
                                ->title('Error')
                                ->body('Failed to decline: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
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
