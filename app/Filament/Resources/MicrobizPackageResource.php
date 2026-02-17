<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MicrobizPackageResource\Pages;
use App\Models\MicrobizPackage;
use App\Models\Product;
use App\Models\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MicrobizPackageResource extends BaseResource
{
    protected static ?string $model = MicrobizPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'MicroBiz Packages';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Definition')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('MicroBiz Business')
                                    ->options(function () {
                                        // Only show products that belong to microbiz categories
                                        return Product::whereHas('subCategory.category', function ($q) {
                                            $q->where('type', 'microbiz');
                                        })
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->helperText('The MicroBiz business this package is for'),
                                Forms\Components\Select::make('tier')
                                    ->options([
                                        'lite' => 'Lite Package ($280)',
                                        'standard' => 'Standard Package ($490)',
                                        'full_house' => 'Full House Package ($930)',
                                        'gold' => 'Gold Package ($2,000)',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $defaults = [
                                            'lite' => 280.00,
                                            'standard' => 490.00,
                                            'full_house' => 930.00,
                                            'gold' => 2000.00,
                                        ];
                                        $set('price', $defaults[$state] ?? 0);
                                    }),
                                Forms\Components\TextInput::make('price')
                                    ->label('Fixed Package Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required()
                                    ->helperText('User-facing price (not sum of items)'),
                            ]),
                    ]),

                Forms\Components\Section::make('Package Contents')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Inventory Product')
                                    ->options(function () {
                                        return Product::orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($product) {
                                                $code = $product->product_code ? "[{$product->product_code}] " : '';
                                                $price = $product->base_price ? " - \${$product->base_price}" : '';
                                                return [$product->id => "{$code}{$product->name}{$price}"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_cost', $product->base_price);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->helperText('Auto-filled from product cost'),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Product to Package')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!($state['product_id'] ?? null)) return null;
                                $product = Product::find($state['product_id']);
                                if (!$product) return null;
                                $qty = $state['quantity'] ?? 1;
                                return "{$product->name} × {$qty}";
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business.subCategory.category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('tier')
                    ->label('Tier')
                    ->colors([
                        'gray' => 'lite',
                        'info' => 'standard',
                        'warning' => 'full_house',
                        'success' => 'gold',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'lite' => 'Lite',
                        'standard' => 'Standard',
                        'full_house' => 'Full House',
                        'gold' => 'Gold',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Package Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items_cost')
                    ->label('Total Cost')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->total_items_cost),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        'lite' => 'Lite',
                        'standard' => 'Standard',
                        'full_house' => 'Full House',
                        'gold' => 'Gold',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('business.name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMicrobizPackages::route('/'),
            'create' => Pages\CreateMicrobizPackage::route('/create'),
            'view' => Pages\ViewMicrobizPackage::route('/{record}'),
            'edit' => Pages\EditMicrobizPackage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
