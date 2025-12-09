<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductSeriesResource\Pages;
use App\Models\ProductSeries;
use App\Models\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductSeriesResource extends BaseResource
{
    protected static ?string $model = ProductSeries::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Product Series';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Series Information')
                    ->schema([
                        Forms\Components\Select::make('product_sub_category_id')
                            ->label('Subcategory')
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

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Series Image')
                            ->image()
                            ->disk('public_uploads')
                            ->directory('product-series')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('338'),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('subCategory.name')
                    ->label('Subcategory')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subCategory.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subCategory')
                    ->relationship('subCategory', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProductSeries::route('/'),
            'create' => Pages\CreateProductSeries::route('/create'),
            'edit' => Pages\EditProductSeries::route('/{record}/edit'),
        ];
    }
}
