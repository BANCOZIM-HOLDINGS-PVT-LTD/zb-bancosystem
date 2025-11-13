<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryManagementResource\Pages;
use App\Models\ProductInventory;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class InventoryManagementResource extends Resource
{
    protected static ?string $model = ProductInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Inventory Management';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('product_sub_category_id')
                                    ->relationship('subCategory', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('base_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Stock Management')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('stock_quantity')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('reserved_quantity')
                                    ->label('Reserved Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('available_stock')
                                    ->label('Available Stock')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record ? $record->available_stock : 0),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('minimum_stock_level')
                                    ->label('Minimum Level')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('reorder_point')
                                    ->label('Reorder Point')
                                    ->numeric()
                                    ->default(10)
                                    ->required(),
                                Forms\Components\TextInput::make('reorder_quantity')
                                    ->label('Reorder Quantity')
                                    ->numeric()
                                    ->default(50)
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('maximum_stock_level')
                            ->label('Maximum Stock Level')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Pricing Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Cost Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Selling Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('markup_percentage')
                                    ->label('Markup %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Availability & Status')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured Product'),
                            ]),
                        Forms\Components\Select::make('availability_status')
                            ->label('Availability Status')
                            ->options([
                                'available' => 'Available',
                                'out_of_stock' => 'Out of Stock',
                                'discontinued' => 'Discontinued',
                                'coming_soon' => 'Coming Soon',
                            ])
                            ->default('available')
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('availability_date')
                                    ->label('Available From'),
                                Forms\Components\DateTimePicker::make('discontinue_date')
                                    ->label('Discontinue Date'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('warehouse_location')
                            ->label('Warehouse Location')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                        Forms\Components\KeyValue::make('supplier_info')
                            ->label('Supplier Information')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.subCategory.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock_status_color),
                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Available')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->available_stock),
                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Reserved')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('stock_status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => ucfirst(str_replace('_', ' ', $record->stock_status)))
                    ->colors([
                        'success' => 'in_stock',
                        'warning' => 'low',
                        'danger' => ['critical', 'out_of_stock'],
                        'gray' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('warehouse_location')
                    ->label('Location')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low' => 'Low Stock',
                        'critical' => 'Critical Stock',
                        'out_of_stock' => 'Out of Stock',
                        'inactive' => 'Inactive',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->whereRaw('(stock_quantity - reserved_quantity) > reorder_point'),
                            'low' => $query->whereRaw('(stock_quantity - reserved_quantity) <= reorder_point')
                                ->whereRaw('(stock_quantity - reserved_quantity) > minimum_stock_level'),
                            'critical' => $query->whereRaw('(stock_quantity - reserved_quantity) <= minimum_stock_level')
                                ->whereRaw('(stock_quantity - reserved_quantity) > 0'),
                            'out_of_stock' => $query->whereRaw('(stock_quantity - reserved_quantity) <= 0'),
                            'inactive' => $query->where('is_active', false),
                            default => $query,
                        };
                    }),
                SelectFilter::make('availability_status')
                    ->options([
                        'available' => 'Available',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                        'coming_soon' => 'Coming Soon',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Filter::make('low_stock')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->lowStock()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'add' => 'Add Stock',
                                'remove' => 'Remove Stock',
                                'set' => 'Set Stock Level',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('reason')
                            ->label('Reason')
                            ->required()
                            ->placeholder('e.g., Stock count correction, Damaged goods, etc.'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Additional details about this adjustment...'),
                    ])
                    ->action(function ($record, array $data): void {
                        $inventory = $record;
                        $success = false;
                        
                        switch ($data['adjustment_type']) {
                            case 'add':
                                $inventory->addStock($data['quantity'], $data['reason']);
                                $success = true;
                                break;
                            case 'remove':
                                $success = $inventory->removeStock($data['quantity'], $data['reason']);
                                break;
                            case 'set':
                                $currentStock = $inventory->stock_quantity;
                                $difference = $data['quantity'] - $currentStock;
                                if ($difference > 0) {
                                    $inventory->addStock($difference, $data['reason']);
                                } elseif ($difference < 0) {
                                    $success = $inventory->removeStock(abs($difference), $data['reason']);
                                }
                                $success = true;
                                break;
                        }
                        
                        if ($success) {
                            Notification::make()
                                ->title('Stock Adjusted Successfully')
                                ->body("Stock has been {$data['adjustment_type']}ed for {$inventory->product->name}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Stock Adjustment Failed')
                                ->body('Insufficient stock for this operation')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Adjust Stock Level')
                    ->modalDescription('This will create an inventory movement record for audit purposes.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Products Activated')
                                ->body(count($records) . ' products have been activated')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Products Deactivated')
                                ->body(count($records) . ' products have been deactivated')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListInventoryManagement::route('/'),
            'create' => Pages\CreateInventoryManagement::route('/create'),
            'view' => Pages\ViewInventoryManagement::route('/{record}'),
            'edit' => Pages\EditInventoryManagement::route('/{record}/edit'),
        ];
    }

    protected static ?string $slug = 'inventory-managements';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::lowStock()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $lowStockCount = static::getModel()::lowStock()->count();
        return $lowStockCount > 0 ? 'warning' : null;
    }
}
