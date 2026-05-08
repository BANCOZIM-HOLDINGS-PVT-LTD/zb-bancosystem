<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\SSBBatchLogResource\Pages;
use App\Models\SSBBatchLog;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SSBBatchLogResource extends Resource
{
    protected static ?string $model = SSBBatchLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'SSB Batch Logs';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_reference')
                    ->label('Batch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        'running' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_records')
                    ->label('Total')
                    ->sortable(),
                Tables\Columns\TextColumn::make('success_count')
                    ->label('Success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_path')
                    ->label('File')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('batch_type')
                    ->options([
                        'export' => 'Export',
                        'import' => 'Import',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'success' => 'Success',
                        'partial' => 'Partial',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Batch Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('batch_reference'),
                        Infolists\Components\TextEntry::make('batch_type')->badge(),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('file_path'),
                        Infolists\Components\TextEntry::make('started_at')->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')->dateTime(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Counts')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_records'),
                        Infolists\Components\TextEntry::make('success_count'),
                        Infolists\Components\TextEntry::make('failed_count'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Errors')
                    ->schema([
                        Infolists\Components\TextEntry::make('errors')
                            ->getStateUsing(fn (SSBBatchLog $record) => empty($record->errors) ? 'None' : json_encode($record->errors, JSON_PRETTY_PRINT)),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSSBBatchLogs::route('/'),
            'view' => Pages\ViewSSBBatchLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
