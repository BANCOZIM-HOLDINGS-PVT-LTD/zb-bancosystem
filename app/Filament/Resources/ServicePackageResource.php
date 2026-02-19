<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicePackageResource\Pages;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageResource extends BaseResource
{
    protected static ?string $model = MicrobizSubcategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Service Packages';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'service-packages';

    protected static ?string $modelLabel = 'Service Package';

    protected static ?string $pluralModelLabel = 'Service Packages';

    /**
     * Only show service domain categories.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('category', fn (Builder $q) => $q->where('domain', 'service'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Package Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('microbiz_category_id')
                                    ->label('Service Category')
                                    ->options(function () {
                                        return MicrobizCategory::service()->get()
                                            ->mapWithKeys(fn ($cat) => [$cat->id => "{$cat->emoji} {$cat->name}"]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Category Name')
                                            ->required()
                                            ->placeholder('e.g., Home Construction'),
                                        Forms\Components\TextInput::make('emoji')
                                            ->required()
                                            ->default('🏗️')
                                            ->placeholder('e.g., 🏗️'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $data['domain'] = 'service';
                                        return MicrobizCategory::create($data)->id;
                                    }),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Service Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Core House - Basic'),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->placeholder('Brief description of this service package'),
                            ]),
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Service Image')
                            ->image()
                            ->directory('service-packages')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Package Items / Constituents')
                    ->description('Define the items that make up this service package.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('item_code')
                                    ->label('Item Code')
                                    ->placeholder('Auto-generated')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('name')
                                    ->label('Item Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Cement Bags'),
                                Forms\Components\Textarea::make('specification')
                                    ->label('Specification')
                                    ->rows(2)
                                    ->placeholder('Brand, size, material, etc.'),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('markup_percentage')
                                    ->label('Markup %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->default(0),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->placeholder('e.g., bag, piece')
                                    ->maxLength(50),
                            ])
                            ->columns(3)
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
                    ->label('Service Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Service Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(fn () => null),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('microbiz_category_id')
                    ->label('Service Category')
                    ->options(function () {
                        return MicrobizCategory::service()->pluck('name', 'id');
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
            'index' => Pages\ListServicePackages::route('/'),
            'create' => Pages\CreateServicePackage::route('/create'),
            'view' => Pages\ViewServicePackage::route('/{record}'),
            'edit' => Pages\EditServicePackage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
}
