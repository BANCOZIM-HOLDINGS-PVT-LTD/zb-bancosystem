<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\AccountOpeningResource\Pages;
use App\Models\AccountOpening;
use App\Services\AccountOpeningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class AccountOpeningResource extends Resource
{
    protected static ?string $model = AccountOpening::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Account Openings';

    protected static ?string $navigationGroup = 'ZB Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Opening Details')
                ->schema([
                    Forms\Components\TextInput::make('reference_code')
                        ->label('Reference Code')
                        ->disabled(),
                    Forms\Components\TextInput::make('user_identifier')
                        ->label('National ID / Phone')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'account_opened' => 'Account Opened',
                            'loan_eligible' => 'Loan Eligible',
                            'rejected' => 'Rejected',
                        ])
                        ->disabled(),
                    Forms\Components\TextInput::make('zb_account_number')
                        ->label('ZB Account Number')
                        ->disabled(),
                    Forms\Components\Toggle::make('loan_eligible')
                        ->label('Loan Eligible')
                        ->disabled(),
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_identifier')
                    ->label('ID/Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zb_account_number')
                    ->label('Account #')
                    ->searchable()
                    ->placeholder('Not assigned'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'account_opened',
                        'primary' => 'loan_eligible',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('loan_eligible')
                    ->label('Loan Ready')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'account_opened' => 'Account Opened',
                        'loan_eligible' => 'Loan Eligible',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('mark_opened')
                    ->label('Mark Account Opened')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('account_number')
                            ->label('ZB Account Number')
                            ->required()
                            ->maxLength(50),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->markAsOpened($data['account_number']);
                        
                        // Send SMS notification
                        $service = app(AccountOpeningService::class);
                        $service->sendAccountOpenedSMS($record);
                        
                        Notification::make()
                            ->title('Account Marked as Opened')
                            ->success()
                            ->send();
                    }),

                Action::make('approve_loan')
                    ->label('Approve for Loan Credibility')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (Model $record) => $record->status === 'account_opened')
                    ->requiresConfirmation()
                    ->modalHeading('Approve for Loan Credibility')
                    ->modalDescription('This will notify the user that they are eligible to apply for loans.')
                    ->action(function (Model $record) {
                        $record->approveForLoan();
                        
                        // Send SMS notification
                        $service = app(AccountOpeningService::class);
                        $service->sendLoanEligibleSMS($record);
                        
                        Notification::make()
                            ->title('Approved for Loan Credibility')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->reject($data['reason']);
                        
                        // Send SMS notification
                        $service = app(AccountOpeningService::class);
                        $service->sendRejectionSMS($record);
                        
                        Notification::make()
                            ->title('Application Rejected')
                            ->success()
                            ->send();
                    }),

                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('info')
                    ->action(function (Model $record) {
                        try {
                            $service = app(AccountOpeningService::class);
                            $pdfPath = $service->generatePDF($record);
                            
                            Notification::make()
                                ->title('PDF Generated')
                                ->success()
                                ->send();
                                
                            return response()->download(storage_path('app/' . $pdfPath));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('PDF Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Model $record) => route('account-opening.pdf.download', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountOpenings::route('/'),
            'view' => Pages\ViewAccountOpening::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::where('status', 'pending')->count();
        } catch (\Exception $e) {
            // Table might not exist yet if migrations haven't been run
            return null;
        }
    }
}
