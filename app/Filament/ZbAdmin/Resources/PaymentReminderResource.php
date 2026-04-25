<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\PaymentReminderResource\Pages;
use App\Filament\ZbAdmin\Resources\PaymentReminderResource\RelationManagers;
use App\Models\PaymentReminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentReminderResource extends Resource
{
    protected static ?string $model = PaymentReminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicationState.reference_code')
                    ->label('Application Ref')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applicationState.application_number')
                    ->label('App #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reminder_stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '3_days' => 'info',
                        '7_days' => 'warning',
                        '14_days' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Read-only resource
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sent_at', 'desc');
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
            'index' => Pages\ListPaymentReminders::route('/'),
        ];
    }
}
