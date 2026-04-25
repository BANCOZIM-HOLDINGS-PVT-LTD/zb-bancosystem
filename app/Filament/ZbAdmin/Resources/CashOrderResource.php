<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\CashOrderResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;

class CashOrderResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Cash Orders';

    protected static ?string $navigationGroup = 'Sales & Orders';
    
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('payment_type', 'cash')
            ->where('deposit_paid', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
             Forms\Components\Section::make('Order Details')
                ->schema([
                    Forms\Components\TextInput::make('application_number')->disabled(),
                    Forms\Components\TextInput::make('reference_code')->label('Reference')->disabled(),
                    Forms\Components\TextInput::make('deposit_amount')->label('Total Paid')->prefix('$')->disabled(),
                    Forms\Components\TextInput::make('deposit_transaction_id')->label('Transaction ID')->disabled(),
                    Forms\Components\DateTimePicker::make('deposit_paid_at')->label('Paid At')->disabled(),
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('Order No')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Customer')
                    ->getStateUsing(fn (Model $record) =>
                        trim(($record->form_data['formResponses']['firstName'] ?? $record->form_data['formResponses']['name'] ?? '') . ' ' . ($record->form_data['formResponses']['surname'] ?? ''))
                    ),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (Model $record) => 
                        $record->form_data['productName'] ?? $record->form_data['selectedBusiness']['name'] ?? 'Multiple Items'
                    ),
                Tables\Columns\TextColumn::make('deposit_amount')
                    ->label('Total Paid')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Order Status')
                    ->colors([
                        'primary' => 'paid',
                        'success' => 'delivered',
                        'info' => 'processing',
                    ]),
                Tables\Columns\TextColumn::make('deposit_paid_at')
                    ->label('Paid Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('download_receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Model $record) => route('application.receipt.download', $record->session_id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashOrders::route('/'),
        ];
    }
}
