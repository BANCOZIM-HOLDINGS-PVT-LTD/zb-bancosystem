<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\BoosterPackageResource\Pages;
use App\Models\BoosterBusiness;
use App\Models\BoosterItem;
use App\Models\BoosterPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterPackageResource extends Resource
{
    protected static ?string $model = BoosterPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Booster Packages';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Package')
                ->schema([
                    Forms\Components\Select::make('booster_business_id')
                        ->options(BoosterBusiness::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('tier')
                        ->options([
                            'starter' => 'Starter',
                            'growth' => 'Growth',
                            'premium' => 'Premium',
                            'elite' => 'Elite',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('slug'),
                    Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
                    Forms\Components\TextInput::make('deposit')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('monthly_installment')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('loan_term')->numeric()->default(12),
                    Forms\Components\TextInput::make('interest_rate')->numeric()->suffix('%')->default(0),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\TextInput::make('image_url')->label('Image URL')->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Included Items')
                ->schema([
                    Forms\Components\Repeater::make('tierItems')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('booster_item_id')
                                ->options(BoosterItem::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->minValue(1)->required(),
                            Forms\Components\Toggle::make('is_delivered')->default(true),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.category.name')->label('Category')->searchable(),
                Tables\Columns\TextColumn::make('business.name')->label('Business')->searchable(),
                Tables\Columns\TextColumn::make('tier')->badge()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('deposit')->money('USD'),
                Tables\Columns\TextColumn::make('monthly_installment')->money('USD'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoosterPackages::route('/'),
            'create' => Pages\CreateBoosterPackage::route('/create'),
            'edit' => Pages\EditBoosterPackage::route('/{record}/edit'),
        ];
    }
}
