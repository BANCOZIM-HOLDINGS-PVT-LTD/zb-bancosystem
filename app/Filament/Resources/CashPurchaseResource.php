<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashPurchaseResource\Pages;
use App\Models\CashPurchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashPurchaseResource extends Resource
{
    protected static ?string $model = CashPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Cash Orders';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Information')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_number')
                            ->label('Purchase Number')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('purchase_type')
                            ->label('Purchase Type')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('product_name')
                            ->label('Product')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('category')
                            ->label('Category')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('cash_price')
                            ->label('Cash Price (USD)')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                    ])->columns(2),

                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('national_id')
                            ->label('National ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Delivery Information')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_type')
                            ->label('Delivery Type')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('depot_name')
                            ->label('Depot/City')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->label('Delivery Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'dispatched' => 'Dispatched',
                                'ready_for_collection' => 'Ready for Collection',
                                'collected' => 'Collected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->helperText('Only delivery status can be manually updated'),
                        Forms\Components\TextInput::make('swift_tracking_number')
                            ->label('Swift Tracking Number')
                            ->maxLength(255)
                            ->helperText('For Swift deliveries only'),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment Method')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Paynow Transaction ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid (USD)')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                        Forms\Components\TextInput::make('payment_status')
                            ->label('Payment Status')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Purchase #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('purchase_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'personal' => 'success',
                        'microbiz' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('national_id')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('depot_name')
                    ->label('Delivery Depot/City')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 25) {
                            return null;
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Payment ID')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Delivery Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'collected' => 'success',
                        'ready_for_collection' => 'info',
                        'dispatched' => 'primary',
                        'processing' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('purchase_type')
                    ->label('Purchase Type')
                    ->options([
                        'personal' => 'Personal',
                        'microbiz' => 'MicroBiz',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Delivery Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'dispatched' => 'Dispatched',
                        'ready_for_collection' => 'Ready for Collection',
                        'collected' => 'Collected',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('delivery_type')
                    ->label('Delivery Type')
                    ->options([
                        'swift' => 'Swift',
                        'gain_outlet' => 'Gain Outlet',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('From'),
                        Forms\Components\DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected'),
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
            'index' => Pages\ListCashPurchases::route('/'),
            'edit' => Pages\EditCashPurchase::route('/{record}/edit'),
            'view' => Pages\ViewCashPurchase::route('/{record}'),
        ];
    }

    // Disable create action
    public static function canCreate(): bool
    {
        return false;
    }

    // Disable delete action
    public static function canDelete($record): bool
    {
        return false;
    }
}
