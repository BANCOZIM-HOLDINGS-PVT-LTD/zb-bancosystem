<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Payments';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('applicationState.reference_code')->label('Application')->searchable(),
                Tables\Columns\TextColumn::make('provider')->badge()->sortable(),
                Tables\Columns\TextColumn::make('method')->badge()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        Payment::STATUS_PAID => 'success',
                        Payment::STATUS_FAILED, Payment::STATUS_CANCELLED, Payment::STATUS_TIMEOUT, Payment::STATUS_INSUFFICIENT_FUNDS => 'danger',
                        Payment::STATUS_PROCESSING => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Payment::STATUS_PENDING => 'Pending',
                        Payment::STATUS_PROCESSING => 'Processing',
                        Payment::STATUS_PAID => 'Paid',
                        Payment::STATUS_FAILED => 'Failed',
                        Payment::STATUS_CANCELLED => 'Cancelled',
                        Payment::STATUS_TIMEOUT => 'Timeout',
                        Payment::STATUS_INSUFFICIENT_FUNDS => 'Insufficient Funds',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options(['paynow' => 'Paynow', 'cash' => 'Cash', 'manual' => 'Manual']),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Payment')
                ->schema([
                    Infolists\Components\TextEntry::make('reference'),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('provider')->badge(),
                    Infolists\Components\TextEntry::make('method')->badge(),
                    Infolists\Components\TextEntry::make('amount')->money('USD'),
                    Infolists\Components\TextEntry::make('receipt_number'),
                    Infolists\Components\TextEntry::make('provider_reference'),
                    Infolists\Components\TextEntry::make('paid_at')->dateTime(),
                ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
