<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountOpeningResource\Pages;
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
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
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
                
                // ZB Admin Actions
                Tables\Actions\Action::make('approve_account')
                    ->label('Approve Opening')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->status === 'pending' || $record->status === 'account_opening_initiated') // Handle synced status
                    ->form([
                        Forms\Components\TextInput::make('account_number')
                            ->label('ZB Account Number')
                            ->required()
                            ->unique(AccountOpening::class, 'zb_account_number'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->update([
                            'status' => 'account_opened',
                            'zb_account_number' => $data['account_number'],
                        ]);
                        
                        // Sync Application State if linked
                        if ($record->application_state_id) {
                            $appState = \App\Models\ApplicationState::find($record->application_state_id);
                            if ($appState) {
                                $appState->update(['current_step' => 'account_opened']);
                                // Notify user
                                $notificationService = app(\App\Services\NotificationService::class);
                                $notificationService->sendNotification(
                                    $appState, 
                                    "Your account has been opened! Account Number: {$data['account_number']}"
                                );
                            }
                        }
                        
                        Notification::make()->title('Account Opened Successfully')->success()->send();
                    }),

                Tables\Actions\Action::make('approve_loan_eligibility')
                    ->label('Approve Loan Eligibility')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (Model $record) => $record->status === 'account_opened')
                    ->action(function (Model $record) {
                        $record->update([
                            'status' => 'loan_eligible',
                            'loan_eligible' => true,
                        ]);
                        
                        // Sync Application State
                         if ($record->application_state_id) {
                            $appState = \App\Models\ApplicationState::find($record->application_state_id);
                            if ($appState) {
                                $appState->update(['current_step' => 'loan_eligible']);
                                // Notify user
                                $notificationService = app(\App\Services\NotificationService::class);
                                $notificationService->sendNotification(
                                    $appState, 
                                    "Your account is now eligible for loans. You may proceed to apply."
                                );
                            }
                        }
                        
                        Notification::make()->title('Loan Eligibility Approved')->success()->send();
                    }),

                // Super Admin can only send custom SMS
                Action::make('send_sms')
                    ->label('Send SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('SMS Message')
                            ->required()
                            ->maxLength(160)
                            ->helperText('Maximum 160 characters'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $service = app(AccountOpeningService::class);
                        $service->sendCustomSMS($record, $data['message']);
                        
                        Notification::make()
                            ->title('SMS Sent')
                            ->success()
                            ->send();
                    }),
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return true; // Allow editing to enable actions? No, actions work regardless.
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
