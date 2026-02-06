<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\ZbApplicationResource\Pages;
use App\Models\ApplicationState;
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

class ZbApplicationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'ZB Loan Applications';

    protected static ?string $navigationGroup = 'Loan Management';

    public static function getEloquentQuery(): Builder
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        
        return parent::getEloquentQuery()
            ->where(function ($query) use ($isPgsql) {
                // Only ZB applications (has account or wants account)
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'hasAccount' = 'true'")
                          ->orWhereRaw("form_data->>'wantsAccount' = 'true'");
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'");
                }
            })
            ->where(function ($query) use ($isPgsql) {
                // Exclude SSB applications
                if ($isPgsql) {
                    $query->whereRaw("COALESCE(form_data->>'employer', '') != 'government-ssb'");
                } else {
                    $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
                }
            })
            // Exclude agent applications
            ->where('current_step', 'not like', 'agent_%');
    }

    public static function form(Form $form): Form
    {
        // Minimal readonly form for viewing details if needed
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->label('Ref Code')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) => 
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    ),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Model $record) => '$' . number_format($record->form_data['finalPrice'] ?? 0)),
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['completed', 'approved']),
                        'warning' => fn ($state) => in_array($state, ['in_review', 'processing', 'pending_verification', 'sent_for_checks']),
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\BadgeColumn::make('check_status')
                    ->label('SSB Check')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['S', 'A']), // Success / Approved
                        'danger' => fn ($state): bool => in_array($state, ['F', 'B']), // Failure / Blacklisted
                        'warning' => 'P', // Pending
                    ])
                    ->formatStateUsing(function ($state, Model $record) {
                        $type = $record->check_type ?? 'Check';
                        
                        $labels = [
                            'S' => 'Success',
                            'F' => 'Failure',
                            'A' => 'Approved',
                            'B' => 'Blacklisted',
                            'P' => 'Pending',
                        ];
                        
                        return ($type ? "$type: " : "") . ($labels[$state] ?? $state ?? 'N/A');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->action(function (Model $record) {
                        try {
                            $pdfGenerator = app(PDFGeneratorService::class);
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);
                            Notification::make()->title('PDF Generated')->success()->send();
                            return redirect()->route('application.pdf.view', $record->session_id);
                        } catch (\Exception $e) {
                            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('zb_action')
                            ->label('Action')
                            ->options([
                                'credit_check_good' => 'Approve (Credit Good)',
                                'credit_check_poor' => 'Reject (Credit Poor)',
                                'salary_not_regular' => 'Reject (Salary Not Regular)',
                                'insufficient_salary' => 'Reject (Insufficient Salary)',
                                'approved' => 'Final Approval',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('recommended_period')
                            ->visible(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->required(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->numeric(),
                        Forms\Components\Textarea::make('notes')
                    ])
                    ->action(function (array $data, Model $record) {
                        $zbService = app(ZBStatusService::class);
                        try {
                            switch ($data['zb_action']) {
                                case 'credit_check_good': $zbService->processCreditCheckGood($record, $data['notes'] ?? ''); break;
                                case 'credit_check_poor': $zbService->processCreditCheckPoor($record, $data['notes'] ?? ''); break;
                                case 'salary_not_regular': $zbService->processSalaryNotRegular($record, $data['notes'] ?? ''); break;
                                case 'insufficient_salary': $zbService->processInsufficientSalary($record, $data['recommended_period'], $data['notes'] ?? ''); break;
                                case 'approved': $zbService->processApproved($record); break;
                            }
                            Notification::make()->title('Status Updated')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_ssb_loans')
                        ->label('Export SSB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function () {
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\SSBLoanExport(),
                                'ssb_loans_export_' . date('Y-m-d') . '.csv'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_zb_loans')
                        ->label('Export ZB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function () {
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\ZBLoanExport(),
                                'zb_loans_export_' . date('Y-m-d') . '.csv'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
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
