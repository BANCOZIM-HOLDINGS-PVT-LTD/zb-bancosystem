<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderResource extends BaseResource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Purchase Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('po_number')
                                    ->label('PO Number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->placeholder('Auto-generated'),
                                
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->options(function () {
                                        // Get or create the default supplier
                                        $defaultSupplier = Supplier::firstOrCreate(
                                            ['name' => 'Seven Hundred Nine Hundred Pvt Ltd'],
                                            [
                                                'supplier_code' => 'SUP-0001',
                                                'status' => 'active',
                                                'country' => 'Zimbabwe'
                                            ]
                                        );
                                        
                                        return Supplier::active()
                                            ->pluck('name', 'id');
                                    })
                                    ->default(function () {
                                        $defaultSupplier = Supplier::where('name', 'Seven Hundred Nine Hundred Pvt Ltd')->first();
                                        return $defaultSupplier?->id;
                                    })
                                    ->searchable()
                                    ->required(),
                                
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'ordered' => 'Ordered',
                                        'received' => 'Received',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('order_date')
                                    ->label('Order Date'),
                                
                                Forms\Components\DatePicker::make('expected_delivery_date')
                                    ->label('Expected Delivery'),
                                
                                Forms\Components\DatePicker::make('actual_delivery_date')
                                    ->label('Actual Delivery')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->cost_price ?? $product->unit_price);
                                            }
                                        }
                                    }),

                                Forms\Components\Placeholder::make('product_code_display')
                                    ->label('Product Code')
                                    ->content(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $product = Product::find($productId);
                                            return $product?->product_code ?? '—';
                                        }
                                        return '—';
                                    }),
                                
                                Forms\Components\TextInput::make('quantity_ordered')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $set('total_price', $state * $unitPrice);
                                    }),
                                
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $get('quantity_ordered') ?? 0;
                                        $set('total_price', $state * $quantity);
                                    }),
                                
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(),
                                
                                Forms\Components\TextInput::make('notes')
                                    ->label('Notes'),
                            ])
                            ->columns(6)
                            ->defaultItems(0)
                            ->createItemButtonLabel('Add Item')
                            ->collapsible()
                            ->cloneable(),
                    ]),
                
                Forms\Components\Section::make('Financial Summary')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                                
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Tax')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                
                                Forms\Components\TextInput::make('shipping_cost')
                                    ->label('Shipping')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'info' => 'ordered',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('usd')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Expected Delivery')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'ordered' => 'Ordered',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                
                Tables\Filters\Filter::make('awaiting_delivery')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'ordered'])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->action(fn (PurchaseOrder $record) => $record->approve(auth()->user()->name ?? 'System'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'pending'),
                
                Tables\Actions\Action::make('mark_ordered')
                    ->label('Mark as Ordered')
                    ->action(fn (PurchaseOrder $record) => $record->markAsOrdered())
                    ->requiresConfirmation()
                    ->color('info')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'approved'),
                
                Tables\Actions\Action::make('mark_received')
                    ->label('Mark as Received')
                    ->action(fn (PurchaseOrder $record) => $record->markAsReceived())
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-inbox-in')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'ordered'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $orders = PurchaseOrder::with(['supplier', 'items.product'])->get();

                        $csv = "PO Number,Supplier,Status,Product Code,Product Name,Qty,Unit Price,Total,Order Date\n";

                        foreach ($orders as $order) {
                            foreach ($order->items as $item) {
                                $csv .= implode(',', [
                                    $order->po_number,
                                    '"' . ($order->supplier?->name ?? 'Unassigned') . '"',
                                    $order->status,
                                    $item->product?->product_code ?? '',
                                    '"' . ($item->product?->name ?? 'Unknown') . '"',
                                    $item->quantity_ordered,
                                    number_format($item->unit_price, 2),
                                    number_format($item->total_price, 2),
                                    $order->order_date?->format('Y-m-d') ?? '',
                                ]) . "\n";
                            }
                        }

                        return response()->streamDownload(
                            fn() => print($csv),
                            'purchase_orders_' . now()->format('Y-m-d') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}