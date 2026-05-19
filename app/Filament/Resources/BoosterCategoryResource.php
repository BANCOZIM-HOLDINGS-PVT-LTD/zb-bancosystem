<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoosterCategoryResource\Pages;
use App\Models\BoosterCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterCategoryResource extends BaseResource
{
    protected static ?string $model = BoosterCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $navigationGroup = 'SME Booster';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Booster Category';

    protected static ?string $slug = 'booster-categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\TextInput::make('emoji')
                            ->label('Emoji')
                            ->placeholder('e.g. 🚜')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Agricultural Machinery'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emoji')
                    ->label('')
                    ->width('40px'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('businesses_count')
                    ->counts('businesses')
                    ->label('Businesses')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index'  => Pages\ListBoosterCategories::route('/'),
            'create' => Pages\CreateBoosterCategory::route('/create'),
            'edit'   => Pages\EditBoosterCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}
