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

    // View-only access
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
