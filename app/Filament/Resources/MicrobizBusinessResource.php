<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MicrobizBusinessResource\Pages;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MicrobizBusinessResource extends BaseResource
{
    protected static ?string $model = MicrobizSubcategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'MicroBiz Businesses';

    protected static ?string $navigationGroup = 'MicroBiz';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'microbiz-businesses';

    protected static ?string $modelLabel = 'Business';

    protected static ?string $pluralModelLabel = 'Businesses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('microbiz_category_id')
                                    ->label('Business Category')
                                    ->options(function () {
                                        return MicrobizCategory::all()
                                            ->mapWithKeys(fn ($cat) => [$cat->id => "{$cat->emoji} {$cat->name}"]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Category Name')
                                            ->required()
                                            ->placeholder('e.g., Chicken Projects'),
                                        Forms\Components\TextInput::make('emoji')
                                            ->required()
                                            ->default('📦')
                                            ->placeholder('e.g., 🐔'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return MicrobizCategory::create($data)->id;
                                    }),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('The supplier for this business line (optional)'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Business Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Layers Production'),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->placeholder('Brief description of this business subcategory'),
                            ]),
                    ]),

                Forms\Components\Section::make('Business Items')
                    ->description('Define the items/products available within this business. These items will be used when creating tier packages.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('item_code')
                                    ->label('Item Code')
                                    ->placeholder('Auto-generated if empty')
                                    ->maxLength(50)
                                    ->helperText('e.g., MB-0001'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Item Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Drinkers'),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->placeholder('e.g., piece, bag, litre')
                                    ->maxLength(50),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Item')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $code = $state['item_code'] ?? '';
                                $name = $state['name'] ?? '';
                                if (!$name) return null;
                                return $code ? "[{$code}] {$name}" : $name;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.emoji')
                    ->label('')
                    ->width('30px'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Business Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->placeholder('Undefined')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('Tiers')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('microbiz_category_id')
                    ->label('Category')
                    ->options(function () {
                        return MicrobizCategory::pluck('name', 'id');
                    }),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('category.name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMicrobizBusinesses::route('/'),
            'create' => Pages\CreateMicrobizBusiness::route('/create'),
            'view' => Pages\ViewMicrobizBusiness::route('/{record}'),
            'edit' => Pages\EditMicrobizBusiness::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }
}
