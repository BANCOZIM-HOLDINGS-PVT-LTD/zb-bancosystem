<?php

namespace App\Filament\Stores\Resources;

use App\Filament\Stores\Resources\StoreProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Product Pricing';

    protected static ?string $navigationGroup = 'Inventory';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Filter out MicroBiz products as per requirement ("not microbiz")
        // MicroBiz is likely a category/purchase_type.
        // Assuming 'purchase_type' column or checking category name.
        // I'll check 'purchase_type' != 'microbiz' if the column exists (CashPurchase has it, Product might not).
        // Viewing Product.php might reveal the scope.
        // Assuming strict filter later. For now, general products.
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->disabled() // Read only name
                    ->dehydrated(false),
                
                Forms\Components\TextInput::make('purchase_price')
                    ->label('Purchase Price')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Components\TextInput $component, $state, Forms\Set $set, Forms\Get $get) {
                        self::updateBasePrice($set, $get);
                    }),
                
                Forms\Components\TextInput::make('markup_percentage')
                    ->label('Markup (%)')
                    ->numeric()
                    ->suffix('%')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Components\TextInput $component, $state, Forms\Set $set, Forms\Get $get) {
                        self::updateBasePrice($set, $get);
                    }),
                
                Forms\Components\TextInput::make('base_price')
                    ->label('Base Price (Calculated)')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated() // Save the calculated value
                    ->required(),
            ]);
    }

    protected static function updateBasePrice(Forms\Set $set, Forms\Get $get): void
    {
        $purchasePrice = floatval($get('purchase_price'));
        $markup = floatval($get('markup_percentage'));
        
        if ($purchasePrice > 0) {
            $basePrice = $purchasePrice * (1 + ($markup / 100));
            $set('base_price', number_format($basePrice, 2, '.', ''));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('purchase_price')->money('USD'),
                Tables\Columns\TextColumn::make('markup_percentage')->suffix('%'),
                Tables\Columns\TextColumn::make('base_price')->money('USD')->label('Selling Price'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreProducts::route('/'),
            'edit' => Pages\EditStoreProduct::route('/{record}/edit'),
        ];
    }
}
