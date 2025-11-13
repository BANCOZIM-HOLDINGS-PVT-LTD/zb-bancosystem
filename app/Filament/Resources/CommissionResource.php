<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\Commission;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Commissions';

    protected static ?string $navigationGroup = 'Agent Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commission Details')
                    ->schema([
                        Forms\Components\Select::make('agent_id')
                            ->label('Agent')
                            ->relationship('agent', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                            ->searchable(['first_name', 'last_name', 'agent_code'])
                            ->required(),

                        Forms\Components\Select::make('application_id')
                            ->label('Application')
                            ->relationship('application', 'session_id')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated if empty'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'application' => 'Application',
                                'delivery' => 'Delivery',
                                'bonus' => 'Bonus',
                                'penalty' => 'Penalty',
                            ])
                            ->default('application')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('base_amount')
                            ->label('Base Amount ($)')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\TextInput::make('rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Commission Amount ($)')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\DatePicker::make('earned_date')
                            ->label('Earned Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Paid Date')
                            ->nullable(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment Method')
                            ->placeholder('e.g., Bank Transfer, Mobile Money'),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder('Transaction reference'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('agent.display_name')
                    ->label('Agent')
                    ->searchable(['agent.first_name', 'agent.last_name', 'agent.agent_code'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('application.session_id')
                    ->label('Application')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'application',
                        'success' => 'delivery',
                        'warning' => 'bonus',
                        'danger' => 'penalty',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'approved',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Base Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Commission')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('earned_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_method')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('agent')
                    ->relationship('agent', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                    ->searchable(),

                SelectFilter::make('type')
                    ->options([
                        'application' => 'Application',
                        'delivery' => 'Delivery',
                        'bonus' => 'Bonus',
                        'penalty' => 'Penalty',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('earned_from'),
                        Forms\Components\DatePicker::make('earned_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['earned_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('earned_date', '>=', $date),
                            )
                            ->when(
                                $data['earned_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('earned_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Commission $record) => $record->status === 'pending')
                    ->action(function (Commission $record): void {
                        $record->update(['status' => 'approved']);
                        
                        Notification::make()
                            ->title('Commission approved')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Commission $record) => $record->status === 'approved')
                    ->form([
                        Forms\Components\DatePicker::make('paid_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('payment_method')
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference'),
                    ])
                    ->action(function (Commission $record, array $data): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_date' => $data['paid_date'],
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title('Commission marked as paid')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'approved']);
                                }
                            });
                            
                            Notification::make()
                                ->title('Commissions approved')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'view' => Pages\ViewCommission::route('/{record}'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count();
    }
}
