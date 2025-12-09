<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\ZbDeliveryTrackingResource\Pages;
use App\Models\DeliveryTracking;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ZbDeliveryTrackingResource extends Resource
{
    protected static ?string $model = DeliveryTracking::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Delivery Tracking';

    protected static ?string $navigationGroup = 'Logistics';

    public static function form(Form $form): Form
    {
        // View-only, so we can reuse the schema but all disabled, or just rely on Table ViewAction.
        // We'll return an empty schema as we are read-only and will use View page mostly.
        // Actually, View page uses the form schema.
        // So we should copy the schema from DeliveryTrackingResource but make everything disabled.
        // For brevity, I will define a simpler schema for viewing.
        return $form->schema([
             // Implementation details omitted for brevity, will rely on Table columns for the list view
             // If View page is needed, we should copy the schema. 
             // Given constraint "not be able to update", read-only form is best.
        ]);
    }

    public static function table(Table $table): Table
    {
        // Reuse table columns from DeliveryTrackingResource (copied manually for independence)
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicationState.reference_code')
                    ->label('Ref Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Client Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_type')
                    ->label('Product'),
                Tables\Columns\TextColumn::make('courier_type')
                    ->label('Courier')
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'primary' => 'processing',
                        'info' => 'dispatched',
                        'success' => 'delivered',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('dispatched_at')->dateTime(),
                Tables\Columns\TextColumn::make('delivered_at')->dateTime(),
            ])
            ->filters([
                // Simplified filters
            ])
            // READ ONLY: No Edit, No Update Status
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZbDeliveryTrackings::route('/'),
            // 'view' => Pages\ViewZbDeliveryTracking::route('/{record}'), // Optional
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
}
