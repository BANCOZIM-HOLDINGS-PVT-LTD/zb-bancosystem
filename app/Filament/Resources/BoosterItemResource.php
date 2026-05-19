<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoosterItemResource\Pages;
use App\Models\BoosterBusiness;
use App\Models\BoosterCategory;
use App\Models\BoosterItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterItemResource extends BaseResource
{
    protected static ?string $model = BoosterItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Items';

    protected static ?string $navigationGroup = 'SME Booster';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Booster Item';

    protected static ?string $slug = 'booster-items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Details')
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
                            ->helperText('The business this item belongs to'),
                        Forms\Components\TextInput::make('item_code')
                            ->label('Item Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g. AGR-001'),
                        Forms\Components\TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->placeholder('e.g. kg, pcs, litres')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost ($)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('markup_percentage')
                            ->label('Markup (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->helperText('Added on top of unit cost for selling price'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('business.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Item Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('markup_percentage')
                    ->label('Markup')
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('USD')
                    ->getStateUsing(fn (BoosterItem $record) => $record->selling_price),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('booster_business_id')
                    ->label('Business')
                    ->options(BoosterBusiness::with('category')
                        ->get()
                        ->mapWithKeys(fn ($b) => [$b->id => $b->category->name . ' → ' . $b->name]))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBoosterItems::route('/'),
            'create' => Pages\CreateBoosterItem::route('/create'),
            'edit'   => Pages\EditBoosterItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}
