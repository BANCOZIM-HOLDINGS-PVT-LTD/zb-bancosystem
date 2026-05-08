<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\BoosterItemResource\Pages;
use App\Models\BoosterBusiness;
use App\Models\BoosterItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterItemResource extends Resource
{
    protected static ?string $model = BoosterItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Booster Items';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('booster_business_id')
                ->options(BoosterBusiness::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('item_code')->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('unit'),
            Forms\Components\TextInput::make('unit_cost')->numeric()->prefix('$')->required(),
            Forms\Components\TextInput::make('markup_percentage')->numeric()->suffix('%')->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.name')->label('Business')->searchable(),
                Tables\Columns\TextColumn::make('item_code')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('unit_cost')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('markup_percentage')->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoosterItems::route('/'),
            'create' => Pages\CreateBoosterItem::route('/create'),
            'edit' => Pages\EditBoosterItem::route('/{record}/edit'),
        ];
    }
}
