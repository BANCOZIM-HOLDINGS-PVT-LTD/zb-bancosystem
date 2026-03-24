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
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_code')->label('National ID')->searchable(),
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
                        $referenceCode = $record->reference_code;

                        // Routing Logic & Status Message
                        if (str_starts_with($referenceCode, 'SSB')) {
                            $clientMessage = "Documents reviewed and accepted. Awaiting Qupa Loan Officer Checking";
                            $record->current_step = 'officer_check'; // SSB goes straight to officer for SSB check
                        } else {
                            $clientMessage = "Documents were reviewed and accepted. Please upload your proof of employment here:";
                            $record->current_step = 'awaiting_proof_of_employment'; // ZB needs employment letter
                        }

                        $metadata['client_status_message'] = $clientMessage;
                        $record->metadata = $metadata;
                        $record->status = 'documents_verified';
                        $record->save();

                        Notification::make()->title($clientMessage)->success()->send();
                    }),

                Action::make('reject_documents')
                    ->label('Reject / Missing Docs')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\CheckboxList::make('unclear_docs')
                            ->label('Unclear Documents')
                            ->options([
                                'id' => 'National ID Card',
                                'payslip' => 'Latest Payslip',
                                'photo' => 'Passport Photo / Selfie',
                                
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Additional Notes')
                            ->placeholder('Any other reason for rejection...'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $metadata = $record->metadata ?? [];
                        $unclearDocs = $data['unclear_docs'];
                        
                        $docLabels = [
                            'id' => 'National ID Card',
                            'payslip' => 'Latest Payslip',
                            'residence' => 'Proof of Residence',
                            'photo' => 'Passport Photo / Selfie',
                            'statement' => 'Bank Statement',
                        ];

                        $list = array_map(fn($d) => $docLabels[$d], $unclearDocs);
                        $listStr = implode(', ', $list);

                        $clientMessage = "Documents Reviewed, however the following documents were unclear {$listStr}. Please reupload these documents.";
                        
                        $record->current_step = 'awaiting_document_reupload';
                        $record->status = 'resubmission_required';
                        
                        $metadata['client_status_message'] = $clientMessage;
                        $metadata['unclear_documents'] = $unclearDocs;
                        $metadata['rejection_details'] = [
                            'reason' => $data['rejection_reason'],
                            'stage' => 'Bancozim Document Verification',
                            'at' => now()->toIso8601String(),
                            'by' => auth()->user()->name,
                        ];
                        $record->metadata = $metadata;
                        $record->save();

                        Notification::make()->title('Sent back for document reupload')->danger()->send();
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
