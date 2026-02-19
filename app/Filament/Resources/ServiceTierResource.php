<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTierResource\Pages;
use App\Models\MicrobizCategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use App\Models\MicrobizSubcategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceTierResource extends BaseResource
{
    protected static ?string $model = MicrobizPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Service Tiers';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'service-tiers';

    protected static ?string $modelLabel = 'Service Tier';

    protected static ?string $pluralModelLabel = 'Service Tiers';

    /**
     * Only show tiers for service domain.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('subcategory.category', fn (Builder $q) => $q->where('domain', 'service'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Tier Definition')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('microbiz_subcategory_id')
                                    ->label('Service Package')
                                    ->options(function () {
                                        return MicrobizSubcategory::with('category')
                                            ->whereHas('category', fn ($q) => $q->where('domain', 'service'))
                                            ->get()
                                            ->mapWithKeys(function ($sub) {
                                                $emoji = $sub->category->emoji ?? '📦';
                                                return [$sub->id => "{$emoji} {$sub->category->name} → {$sub->name}"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive(),
                                Forms\Components\Select::make('tier')
                                    ->label('Tier')
                                    ->options([
                                        'lite' => 'Lite',
                                        'standard' => 'Standard',
                                        'full_house' => 'Full House',
                                        'gold' => 'Gold',
                                    ])
                                    ->required()
                                    ->searchable(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Tier Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Standard Construction Package'),
                                Forms\Components\TextInput::make('price')
                                    ->label('Tier Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Describe what\'s included...'),
                    ]),

                Forms\Components\Section::make('Transport')
                    ->description('TS = Transport from Source, TC = 10% of delivered items total.')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('transport_method')
                                    ->label('TS Method')
                                    ->options([
                                        'small_truck' => 'Small Truck ($20)',
                                        'indrive' => 'InDrive ($5)',
                                    ])
                                    ->nullable(),
                                Forms\Components\TextInput::make('courier')
                                    ->label('Courier')
                                    ->default('zimpost')
                                    ->placeholder('zimpost'),
                                Forms\Components\TextInput::make('ts_code')
                                    ->label('TS Code')
                                    ->prefix('TS-')
                                    ->placeholder('Enter code'),
                                Forms\Components\TextInput::make('tc_code')
                                    ->label('TC Code')
                                    ->prefix('TC-')
                                    ->placeholder('Enter code'),
                            ]),
                    ]),

                Forms\Components\Section::make('Tier Contents')
                    ->description('Add items. Toggle "Delivered?" for items needing delivery.')
                    ->schema([
                        Forms\Components\Repeater::make('tierItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('microbiz_item_id')
                                    ->label('Item')
                                    ->options(function (callable $get) {
                                        $subcategoryId = $get('../../microbiz_subcategory_id');
                                        if (!$subcategoryId) {
                                            return MicrobizItem::orderBy('name')
                                                ->get()
                                                ->mapWithKeys(fn ($item) => [$item->id => "[{$item->item_code}] {$item->name} - \${$item->unit_cost}"]);
                                        }

                                        return MicrobizItem::where('microbiz_subcategory_id', $subcategoryId)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                $unit = $item->unit ? " ({$item->unit})" : '';
                                                return [$item->id => "[{$item->item_code}] {$item->name}{$unit} - \${$item->unit_cost}"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\Toggle::make('is_delivered')
                                    ->label('Delivered?')
                                    ->default(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add Item')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!($state['microbiz_item_id'] ?? null)) return null;
                                $item = MicrobizItem::find($state['microbiz_item_id']);
                                if (!$item) return null;
                                $qty = $state['quantity'] ?? 1;
                                $delivered = ($state['is_delivered'] ?? false) ? ' 🚚' : '';
                                return "[{$item->item_code}] {$item->name} × {$qty}{$delivered}";
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subcategory.category.name')
                    ->label('Service Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label('Service Package')
                    ->searchable()
                    ->sortable(),
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
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Tier Name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transport_method')
                    ->label('TS')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'small_truck' => 'Truck $20',
                        'indrive' => 'InDrive $5',
                        default => 'None',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tier_items_count')
                    ->counts('tierItems')
                    ->label('Items')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_transport_cost')
                    ->label('Transport')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->total_transport_cost)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        'lite' => 'Lite',
                        'standard' => 'Standard',
                        'full_house' => 'Full House',
                        'gold' => 'Gold',
                    ]),
                Tables\Filters\SelectFilter::make('microbiz_subcategory_id')
                    ->label('Service Package')
                    ->options(function () {
                        return MicrobizSubcategory::with('category')
                            ->whereHas('category', fn ($q) => $q->where('domain', 'service'))
                            ->get()
                            ->mapWithKeys(fn ($sub) => [$sub->id => "{$sub->category->name} → {$sub->name}"]);
                    })
                    ->searchable(),
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
            ->defaultSort('subcategory.name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceTiers::route('/'),
            'create' => Pages\CreateServiceTier::route('/create'),
            'view' => Pages\ViewServiceTier::route('/{record}'),
            'edit' => Pages\EditServiceTier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
}
