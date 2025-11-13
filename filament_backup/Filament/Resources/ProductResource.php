<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('product_sub_category_id')
                            ->label('Category')
                            ->options(function () {
                                return ProductSubCategory::with('category')
                                    ->get()
                                    ->mapWithKeys(function ($subCategory) {
                                        return [
                                            $subCategory->id => $subCategory->category->name . ' > ' . $subCategory->name
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('base_price')
                            ->label('Base Price ($)')
                            ->numeric()
                            ->required()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL')
                            ->url()
                            ->placeholder('https://example.com/image.jpg'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Package Sizes')
                    ->schema([
                        Forms\Components\Repeater::make('packageSizes')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('e.g., Small, Medium, Large'),

                                Forms\Components\TextInput::make('multiplier')
                                    ->label('Price Multiplier')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->step(0.1),

                                Forms\Components\TextInput::make('custom_price')
                                    ->label('Custom Price ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Leave empty to use base price × multiplier'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Package Size')
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl('https://via.placeholder.com/150'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_path')
                    ->label('Category')
                    ->getStateUsing(function (Product $record) {
                        return $record->category->name . ' > ' . $record->subCategory->name;
                    })
                    ->searchable(['subCategory.name', 'category.name'])
                    ->sortable(false),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('Base Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_range_display')
                    ->label('Price Range')
                    ->getStateUsing(function (Product $record) {
                        return $record->formatted_price_range;
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('package_sizes_count')
                    ->label('Package Sizes')
                    ->counts('packageSizes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('subCategory')
                    ->relationship('subCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('price_to')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('base_price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('base_price', '<=', $price),
                            );
                    }),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
