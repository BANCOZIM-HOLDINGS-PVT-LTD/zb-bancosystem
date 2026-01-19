<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\PayrollResource\Pages;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PayrollResource extends Resource
{
    protected static ?string $model = PayrollEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payroll';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipient Information')
                    ->schema([
                        Forms\Components\Select::make('recipient_type')
                            ->label('Recipient Type')
                            ->options([
                                'employee' => 'Employee',
                                'intern' => 'Intern',
                                'agent_online' => 'Online Agent',
                                'agent_physical' => 'Physical Agent',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('user_id', null) && $set('agent_id', null)),

                        Forms\Components\Select::make('user_id')
                            ->label('Employee/Intern')
                            ->options(fn () => User::whereIn('role', ['employee', 'intern', 'ROLE_HR'])->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => in_array($get('recipient_type'), ['employee', 'intern']))
                            ->required(fn (Forms\Get $get) => in_array($get('recipient_type'), ['employee', 'intern']))
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    $set('recipient_name', $user?->name ?? '');
                                }
                            }),

                        Forms\Components\Select::make('agent_id')
                            ->label('Agent')
                            ->options(fn (Forms\Get $get) => Agent::where('agent_type', $get('recipient_type') === 'agent_online' ? 'online' : 'physical')->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => in_array($get('recipient_type'), ['agent_online', 'agent_physical']))
                            ->required(fn (Forms\Get $get) => in_array($get('recipient_type'), ['agent_online', 'agent_physical']))
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $agent = Agent::find($state);
                                    $set('recipient_name', $agent?->name ?? '');
                                }
                            }),

                        Forms\Components\TextInput::make('recipient_name')
                            ->label('Recipient Name')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('Pay Period')
                    ->schema([
                        Forms\Components\DatePicker::make('pay_period_start')
                            ->label('Period Start')
                            ->required()
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('pay_period_end')
                            ->label('Period End')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Base Salary')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateNetPay($set, $get)),

                        Forms\Components\TextInput::make('commission')
                            ->label('Commission')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Auto-calculated from approved sales')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateNetPay($set, $get)),

                        Forms\Components\TextInput::make('allowances')
                            ->label('Allowances')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateNetPay($set, $get)),

                        Forms\Components\TextInput::make('deductions')
                            ->label('Deductions')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateNetPay($set, $get)),

                        Forms\Components\TextInput::make('net_pay')
                            ->label('Net Pay')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'processed' => 'Processed',
                                'paid' => 'Paid',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder('Bank transfer reference, etc.')
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['processed', 'paid'])),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    protected static function calculateNetPay(Forms\Set $set, Forms\Get $get): void
    {
        $baseSalary = floatval($get('base_salary') ?? 0);
        $commission = floatval($get('commission') ?? 0);
        $allowances = floatval($get('allowances') ?? 0);
        $deductions = floatval($get('deductions') ?? 0);

        $netPay = $baseSalary + $commission + $allowances - $deductions;
        $set('net_pay', number_format($netPay, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipient_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'employee' => 'success',
                        'intern' => 'info',
                        'agent_online' => 'primary',
                        'agent_physical' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('pay_period_start')
                    ->label('Period')
                    ->date()
                    ->description(fn ($record) => 'to ' . $record->pay_period_end->format('M j, Y')),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission')
                    ->label('Commission')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'processed' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('recipient_type')
                    ->options([
                        'employee' => 'Employee',
                        'intern' => 'Intern',
                        'agent_online' => 'Online Agent',
                        'agent_physical' => 'Physical Agent',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PayrollEntry $record) => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->required(),
                    ])
                    ->action(function (PayrollEntry $record, array $data) {
                        $record->markAsPaid($data['payment_reference']);
                        
                        Notification::make()
                            ->title('Payroll Marked as Paid')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_processed')
                        ->label('Mark as Processed')
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markAsProcessed();
                            }
                            
                            Notification::make()
                                ->title('Payroll entries marked as processed')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollEntries::route('/'),
            'create' => Pages\CreatePayrollEntry::route('/create'),
            'edit' => Pages\EditPayrollEntry::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
}
