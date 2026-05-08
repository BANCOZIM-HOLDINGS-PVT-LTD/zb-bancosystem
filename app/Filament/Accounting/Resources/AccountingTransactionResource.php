<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\AccountingTransactionResource\Pages;
use App\Models\AccountingTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingTransactionResource extends Resource
{
    protected static ?string $model = AccountingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Transactions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source')->badge()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('applicationState.reference_code')->label('Application')->searchable(),
                Tables\Columns\TextColumn::make('payment.reference')->label('Payment')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'income' => 'Income',
                        'commission' => 'Commission',
                        'expense' => 'Expense',
                        'purchase_order' => 'Purchase Order',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'paynow' => 'Paynow',
                        'cash' => 'Cash',
                        'commission' => 'Commission',
                        'purchase_order' => 'Purchase Order',
                    ]),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingTransactions::route('/'),
            'view' => Pages\ViewAccountingTransaction::route('/{record}'),
        ];
    }
}
