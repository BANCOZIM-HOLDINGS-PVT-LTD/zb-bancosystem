<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\EmailDeliveryLogResource\Pages;
use App\Models\EmailDeliveryLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailDeliveryLogResource extends Resource
{
    protected static ?string $model = EmailDeliveryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'Email Delivery Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient')->searchable(),
                Tables\Columns\TextColumn::make('mailable')->label('Template')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('status')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed', 'bounced' => 'danger',
                        'retrying' => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('attempts')->sortable(),
                Tables\Columns\TextColumn::make('applicationState.reference_code')->label('Application')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'retrying' => 'Retrying',
                        'bounced' => 'Bounced',
                    ]),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailDeliveryLogs::route('/'),
            'view' => Pages\ViewEmailDeliveryLog::route('/{record}'),
        ];
    }
}
