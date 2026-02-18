<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryManagementResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\ProductSeries;
use App\Models\Supplier;
use App\Models\ProductInventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class InventoryManagementResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Hire Purchase Products';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'inventory-products';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->hirePurchaseOnly();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Identification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('product_code')
                                    ->label('Product Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., HP-CEL-SAM-024')
                                    ->maxLength(50)
                                    ->helperText('Unique product identifier used across the system'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_sub_category_id')
                                    ->label('Category / Subcategory')
                                    ->options(function () {
                                        return ProductSubCategory::whereHas('category', function ($q) {
                                            $q->where('type', 'hire_purchase');
                                        })
                                        ->with('category')
                                        ->get()
                                        ->mapWithKeys(function ($sub) {
                                            return [$sub->id => $sub->category->emoji . ' ' . $sub->category->name . ' → ' . $sub->name];
                                        });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->createOptionForm([
                                        Forms\Components\Select::make('product_category_id')
                                            ->label('Parent Category')
                                            ->options(ProductCategory::where('type', 'hire_purchase')->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')->required(),
                                                Forms\Components\TextInput::make('emoji')->required()->placeholder('📱'),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $data['type'] = 'hire_purchase';
                                                return ProductCategory::create($data)->id;
                                            }),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Subcategory Name')
                                            ->required(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return ProductSubCategory::create($data)->id;
                                    }),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_series_id')
                                    ->label('Series / Model Line')
                                    ->options(function (callable $get) {
                                        $subCatId = $get('product_sub_category_id');
                                        if (!$subCatId) return [];
                                        
                                        return ProductSeries::where('product_sub_category_id', $subCatId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->reactive()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\Textarea::make('description'),
                                    ])
                                    ->createOptionUsing(function (array $data, callable $get) {
                                        $data['product_sub_category_id'] = $get('product_sub_category_id');
                                        return ProductSeries::create($data)->id;
                                    }),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Assign a supplier or leave empty'),
                            ]),
                    ]),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('base_price')
                                    ->label('Cost / Base Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $markup = $get('markup_percentage');
                                        if ($state && $markup) {
                                            $set('purchase_price', round($state * (1 + $markup / 100), 2));
                                        }
                                    }),
                                Forms\Components\TextInput::make('markup_percentage')
                                    ->label('Markup %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $base = $get('base_price');
                                        if ($base && $state) {
                                            $set('purchase_price', round($base * (1 + $state / 100), 2));
                                        }
                                    }),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Selling Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->helperText('Auto-calculated from base + markup'),
                            ]),
                    ]),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Product Image')
                            ->image()
                            ->disk('public_uploads')
                            ->directory('products')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('subCategory.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subCategory.name')
                    ->label('Subcategory')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('series.name')
                    ->label('Series')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Cost Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Selling Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('inventory.stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(function () {
                        return ProductCategory::pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] ?? null) {
                            return $query->whereHas('subCategory', function ($q) use ($data) {
                                $q->where('product_category_id', $data['value']);
                            });
                        }
                        return $query;
                    }),
                Filter::make('missing_code')
                    ->label('Missing Product Code')
                    ->query(fn (Builder $query): Builder => $query->whereNull('product_code'))
                    ->toggle(),
                Filter::make('no_supplier')
                    ->label('No Supplier Assigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('supplier_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('adjustment_type')
                            ->label('Type')
                            ->options([
                                'add' => 'Add Stock',
                                'remove' => 'Remove Stock',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('reason')
                            ->required()
                            ->default('Restock'),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $inventory = ProductInventory::firstOrCreate(
                            ['product_id' => $record->id],
                            ['stock_quantity' => 0, 'reserved_quantity' => 0, 'reorder_point' => 10]
                        );

                        if ($data['adjustment_type'] === 'add') {
                            $inventory->addStock((int) $data['quantity'], $data['reason']);
                            Notification::make()->title('Stock Added')->success()->send();
                        } else {
                            if ($inventory->removeStock((int) $data['quantity'], $data['reason'])) {
                                Notification::make()->title('Stock Removed')->success()->send();
                            } else {
                                Notification::make()->title('Insufficient Stock')->danger()->send();
                            }
                        }
                    }),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryManagement::route('/'),
            'create' => Pages\CreateInventoryManagement::route('/create'),
            'view' => Pages\ViewInventoryManagement::route('/{record}'),
            'edit' => Pages\EditInventoryManagement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
