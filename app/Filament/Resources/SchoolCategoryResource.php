<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolCategoryResource\Pages;
use App\Models\SchoolCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolCategoryResource extends BaseResource
{
    protected static ?string $model = SchoolCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $navigationGroup = 'School Booster';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'School Category';
    protected static ?string $slug = 'school-categories';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Category Details')->schema([
                Forms\Components\TextInput::make('emoji')->label('Emoji')->placeholder('e.g. 🏫')->maxLength(10),
                Forms\Components\TextInput::make('name')->label('Category Name')->required()->maxLength(255)->placeholder('e.g. Primary Schools'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emoji')->label('')->width('40px'),
                Tables\Columns\TextColumn::make('name')->label('Category')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('businesses_count')->counts('businesses')->label('Businesses')->badge()->color('info'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchoolCategories::route('/'),
            'create' => Pages\CreateSchoolCategory::route('/create'),
            'edit'   => Pages\EditSchoolCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}
