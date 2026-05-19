<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoosterBusinessResource\Pages;
use App\Models\BoosterBusiness;
use App\Models\BoosterCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterBusinessResource extends BaseResource
{
    protected static ?string $model = BoosterBusiness::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Businesses';

    protected static ?string $navigationGroup = 'SME Booster';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Booster Business';

    protected static ?string $slug = 'booster-businesses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Details')
                    ->schema([
                        Forms\Components\Select::make('booster_category_id')
                            ->label('Category')
                            ->options(BoosterCategory::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('The SME Booster category this business belongs to'),
                        Forms\Components\TextInput::make('name')
                            ->label('Business Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Tractors'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Business Image')
                            ->image()
                            ->directory('booster-businesses')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->square(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('Packages')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('booster_category_id')
                    ->label('Category')
                    ->options(BoosterCategory::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
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
            'index'  => Pages\ListBoosterBusinesses::route('/'),
            'create' => Pages\CreateBoosterBusiness::route('/create'),
            'edit'   => Pages\EditBoosterBusiness::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}
