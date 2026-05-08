<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\CashPaymentResource\Pages;
use App\Models\CashPayment;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashPaymentResource extends Resource
{
    protected static ?string $model = CashPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Cash Verification';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('cashier_reference')->disabled(),
            Forms\Components\TextInput::make('received_amount')->numeric()->prefix('$')->disabled(),
            Forms\Components\TextInput::make('receipt_number'),
            Forms\Components\Textarea::make('notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashier_reference')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('applicationState.reference_code')->label('Application')->searchable(),
                Tables\Columns\TextColumn::make('payment.status')->badge(),
                Tables\Columns\TextColumn::make('received_amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')->searchable(),
                Tables\Columns\TextColumn::make('verifier.name')->label('Verified By'),
                Tables\Columns\TextColumn::make('verified_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CashPayment $record) => !$record->verified_at && !$record->rejected_at)
                    ->form([
                        Forms\Components\TextInput::make('receipt_number')
                            ->default(fn () => Payment::generateReceiptNumber()),
                        Forms\Components\Textarea::make('notes'),
                    ])
                    ->action(function (CashPayment $record, array $data) {
                        app(\App\Http\Controllers\CashPaymentController::class)
                            ->verify(request()->merge($data), $record);

                        Notification::make()->title('Cash payment verified')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CashPayment $record) => !$record->verified_at && !$record->rejected_at)
                    ->form([Forms\Components\Textarea::make('notes')->required()])
                    ->action(function (CashPayment $record, array $data) {
                        app(\App\Http\Controllers\CashPaymentController::class)
                            ->reject(request()->merge($data), $record);

                        Notification::make()->title('Cash payment rejected')->warning()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashPayments::route('/'),
        ];
    }
}
