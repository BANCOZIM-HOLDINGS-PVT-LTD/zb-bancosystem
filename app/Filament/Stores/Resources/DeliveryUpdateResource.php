<?php

namespace App\Filament\Stores\Resources;

use App\Filament\Stores\Resources\DeliveryUpdateResource\Pages;
use App\Models\DeliveryTracking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryUpdateResource extends Resource
{
    protected static ?string $model = DeliveryTracking::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $navigationGroup = 'Logistics';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Delivery Information')
                    ->schema([
                        Forms\Components\Select::make('application_state_id')
                            ->relationship('applicationState', 'reference_code')
                            ->label('Application Reference')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'dispatched' => 'Dispatched',
                                'in_transit' => 'In Transit',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered' => 'Delivered',
                                'failed' => 'Failed',
                                'returned' => 'Returned',
                            ])
                            ->required(),
                        Forms\Components\Select::make('courier_type')
                            ->options([
                                'Zim Post Office' => 'Zim Post Office',
                                'Gain Cash & Carry' => 'Gain Cash & Carry',
                                'Bus Courier' => 'Bus Courier',
                                'Bancozim' => 'Bancozim',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('recipient_name')->required(),
                        Forms\Components\TextInput::make('recipient_phone')->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Tracking Details')
                    ->schema([
                        Forms\Components\TextInput::make('swift_tracking_number')->label('Tracking Number'),
                        Forms\Components\DatePicker::make('dispatched_at'),
                        Forms\Components\DatePicker::make('estimated_delivery_date'),
                        Forms\Components\DatePicker::make('delivered_at'),
                        Forms\Components\TextInput::make('delivery_depot'),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_notes')
                            ->rows(3),
                        Forms\Components\Textarea::make('admin_notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicationState.reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_name')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'dispatched', 'in_transit', 'out_for_delivery' => 'info',
                        'failed', 'returned' => 'danger',
                        'pending' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('courier_type'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'dispatched' => 'Dispatched',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'returned' => 'Returned',
                    ]),
                Tables\Filters\SelectFilter::make('courier_type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('dispatch')
                    ->label('Mark Dispatched')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (DeliveryTracking $record) => in_array($record->status, ['pending', 'processing']))
                    ->action(fn (DeliveryTracking $record) => $record->update([
                        'status' => 'dispatched',
                        'dispatched_at' => now()
                    ]))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('delivered')
                    ->label('Mark Delivered')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DeliveryTracking $record) => in_array($record->status, ['dispatched', 'in_transit', 'out_for_delivery']))
                    ->action(fn (DeliveryTracking $record) => $record->update([
                        'status' => 'delivered',
                        'delivered_at' => now()
                    ]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryUpdates::route('/'),
            'create' => Pages\CreateDeliveryUpdate::route('/create'),
            'edit' => Pages\EditDeliveryUpdate::route('/{record}/edit'),
        ];
    }
}
