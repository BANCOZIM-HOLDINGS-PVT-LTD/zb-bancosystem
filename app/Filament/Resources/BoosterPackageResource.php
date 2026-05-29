<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoosterPackageResource\Pages;
use App\Models\BoosterBusiness;
use App\Models\BoosterCategory;
use App\Models\BoosterItem;
use App\Models\BoosterPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BoosterPackageResource extends BaseResource
{
    protected static ?string $model = BoosterPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Packages';

    protected static ?string $navigationGroup = 'SME Booster';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Booster Package';

    protected static ?string $pluralModelLabel = 'Booster Packages';

    protected static ?string $slug = 'booster-packages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Definition')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('booster_business_id')
                                    ->label('Business')
                                    ->options(function () {
                                        return BoosterBusiness::with('category')
                                            ->get()
                                            ->mapWithKeys(fn ($b) => [
                                                $b->id => ($b->category->emoji ?? '🏪') . ' ' . $b->category->name . ' → ' . $b->name
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->helperText('The SME Booster business this package belongs to'),

                                Forms\Components\Select::make('tier')
                                    ->label('Tier')
                                    ->options([
                                        'silver'   => 'Silver',
                                        'gold'     => 'Gold',
                                        'diamond'  => 'Diamond',
                                        'platinum' => 'Platinum',
                                    ])
                                    ->required()
                                    ->searchable(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Package Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Silver Tractor Package'),

                                Forms\Components\TextInput::make('price')
                                    ->label('Package Price ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required()
                                    ->helperText('Total package price (user-facing)'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Package Description')
                            ->rows(3)
                            ->placeholder("Describe what's included...")
                            ->helperText('Visible to applicants'),

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Package Image')
                            ->image()
                            ->directory('booster-packages')
                            ->visibility('public'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive packages are hidden from the application wizard'),
                    ]),

                Forms\Components\Section::make('Loan Terms')
                    ->description('Financial terms for this package tier.')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('deposit')
                                    ->label('Deposit ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->default(0),

                                Forms\Components\TextInput::make('loan_term')
                                    ->label('Loan Term (months)')
                                    ->numeric()
                                    ->suffix('mo')
                                    ->default(24),

                                Forms\Components\TextInput::make('interest_rate')
                                    ->label('Interest Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(108.00)
                                    ->helperText('Annual flat rate'),

                                Forms\Components\TextInput::make('monthly_installment')
                                    ->label('Monthly Installment ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->helperText('Leave blank to auto-calculate'),
                            ]),
                    ]),

                Forms\Components\Section::make('Package Contents')
                    ->description('Add items from the selected business. Toggle "Delivered?" for items that need delivery.')
                    ->schema([
                        Forms\Components\Repeater::make('tierItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('booster_item_id')
                                    ->label('Item')
                                    ->options(function (callable $get) {
                                        $businessId = $get('../../booster_business_id');
                                        if (!$businessId) {
                                            return BoosterItem::where('is_active', true)
                                                ->orderBy('name')
                                                ->get()
                                                ->mapWithKeys(fn ($i) => [
                                                    $i->id => "[{$i->item_code}] {$i->name} - \${$i->unit_cost}"
                                                ]);
                                        }
                                        return BoosterItem::where('booster_business_id', $businessId)
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($i) {
                                                $unit = $i->unit ? " ({$i->unit})" : '';
                                                return [$i->id => "[{$i->item_code}] {$i->name}{$unit} - \${$i->unit_cost}"];
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
                                    ->helperText('Needs delivery to client')
                                    ->default(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add Item to Package')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!($state['booster_item_id'] ?? null)) return null;
                                $item = BoosterItem::find($state['booster_item_id']);
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
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->square(),

                Tables\Columns\TextColumn::make('business.category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tier')
                    ->label('Tier')
                    ->colors([
                        'gray'    => 'silver',
                        'warning' => 'gold',
                        'info'    => 'diamond',
                        'success' => 'platinum',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'silver'   => '🥈 Silver',
                        'gold'     => '🥇 Gold',
                        'diamond'  => '💎 Diamond',
                        'platinum' => '🏆 Platinum',
                        default    => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Package Name')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('loan_term')
                    ->label('Term')
                    ->suffix(' mo')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tier_items_count')
                    ->counts('tierItems')
                    ->label('Items')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_items_cost')
                    ->label('Items Cost')
                    ->money('USD')
                    ->getStateUsing(fn (BoosterPackage $record) => $record->total_items_cost)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        'silver'   => '🥈 Silver',
                        'gold'     => '🥇 Gold',
                        'diamond'  => '💎 Diamond',
                        'platinum' => '🏆 Platinum',
                    ]),

                Tables\Filters\SelectFilter::make('booster_business_id')
                    ->label('Business')
                    ->options(function () {
                        return BoosterBusiness::with('category')
                            ->get()
                            ->mapWithKeys(fn ($b) => [$b->id => $b->category->name . ' → ' . $b->name]);
                    })
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->headerActions([
                Action::make('setTierPrice')
                    ->label('Set Tier Price')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('tier')
                            ->label('Tier')
                            ->options([
                                'silver'   => 'Silver',
                                'gold'     => 'Gold',
                                'diamond'  => 'Diamond',
                                'platinum' => 'Platinum',
                            ])
                            ->required()
                            ->helperText('All Business Booster packages of this tier will be updated'),
                        Forms\Components\TextInput::make('price')
                            ->label('New Price (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (array $data): void {
                        $count = BoosterPackage::where('tier', $data['tier'])->update(['price' => $data['price']]);
                        Notification::make()
                            ->title("Updated {$count} " . ucfirst($data['tier']) . " package(s) to \${$data['price']}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set Price for All Business Booster Packages of a Tier')
                    ->modalDescription('This will update the price for ALL Business Booster packages of the selected tier. This cannot be undone.'),
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
            'index'  => Pages\ListBoosterPackages::route('/'),
            'create' => Pages\CreateBoosterPackage::route('/create'),
            'view'   => Pages\ViewBoosterPackage::route('/{record}'),
            'edit'   => Pages\EditBoosterPackage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getModel()::count() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
