<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolBusinessResource\Pages;
use App\Models\SchoolBusiness;
use App\Models\SchoolCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolBusinessResource extends BaseResource
{
    protected static ?string $model = SchoolBusiness::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'School Types';
    protected static ?string $navigationGroup = 'School Booster';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'School Type';
    protected static ?string $slug = 'school-businesses';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('School Details')->schema([
                Forms\Components\Select::make('school_category_id')
                    ->label('Category')
                    ->options(SchoolCategory::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\TextInput::make('name')->label('School Type Name')->required()->maxLength(255)->placeholder('e.g. Primary Schools'),
                Forms\Components\Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                Forms\Components\FileUpload::make('image_url')->label('Image')->image()->directory('school-businesses')->visibility('public')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')->label('Image')->square(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('School Type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('packages_count')->counts('packages')->label('Packages')->badge()->color('success'),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Items')->badge()->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_category_id')->label('Category')
                    ->options(SchoolCategory::orderBy('name')->pluck('name', 'id'))->searchable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchoolBusinesses::route('/'),
            'create' => Pages\CreateSchoolBusiness::route('/create'),
            'edit'   => Pages\EditSchoolBusiness::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string // guarded-badge
    {
        try {
            return (string) static::getModel()::count() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
