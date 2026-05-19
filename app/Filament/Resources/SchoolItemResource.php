<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolItemResource\Pages;
use App\Models\SchoolBusiness;
use App\Models\SchoolItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolItemResource extends BaseResource
{
    protected static ?string $model = SchoolItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Items';
    protected static ?string $navigationGroup = 'School Booster';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'School Item';
    protected static ?string $slug = 'school-items';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Item Details')->schema([
                Forms\Components\Select::make('school_business_id')
                    ->label('School Type')
                    ->options(fn () => SchoolBusiness::with('category')->get()
                        ->mapWithKeys(fn ($b) => [$b->id => ($b->category->emoji ?? '🏫') . ' ' . $b->category->name . ' → ' . $b->name]))
                    ->searchable()->required(),
                Forms\Components\TextInput::make('item_code')->label('Item Code')->required()->unique(ignoreRecord: true)->maxLength(50)->placeholder('e.g. SCH-001'),
                Forms\Components\TextInput::make('name')->label('Item Name')->required()->maxLength(255),
                Forms\Components\TextInput::make('unit')->label('Unit')->placeholder('e.g. pcs, sets')->maxLength(50),
                Forms\Components\TextInput::make('unit_cost')->label('Unit Cost ($)')->numeric()->prefix('$')->step(0.01)->required(),
                Forms\Components\TextInput::make('markup_percentage')->label('Markup (%)')->numeric()->suffix('%')->default(0),
                Forms\Components\Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')->label('Code')->searchable()->badge()->color('gray'),
                Tables\Columns\TextColumn::make('business.category.name')->label('Category')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('business.name')->label('School Type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Item Name')->searchable(),
                Tables\Columns\TextColumn::make('unit_cost')->label('Cost')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('selling_price')->label('Price')->money('USD')->getStateUsing(fn (SchoolItem $r) => $r->selling_price),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_business_id')->label('School Type')
                    ->options(SchoolBusiness::with('category')->get()->mapWithKeys(fn ($b) => [$b->id => $b->category->name . ' → ' . $b->name]))->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchoolItems::route('/'),
            'create' => Pages\CreateSchoolItem::route('/create'),
            'edit'   => Pages\EditSchoolItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count() ?: null;
    }
}
