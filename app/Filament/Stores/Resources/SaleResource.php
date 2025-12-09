<?php

namespace App\Filament\Stores\Resources;

use App\Filament\Stores\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $product = Product::find($state);
                        if ($product) {
                            $set('unit_price', $product->base_price);
                        }
                    }),
                
                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total_amount', $state * $get('quantity'))),

                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total_amount', $state * $get('unit_price'))),
                
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->readOnly(),

                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'ecocash' => 'EcoCash',
                        'swipe' => 'Swipe',
                    ])
                    ->required()
                    ->default('cash'),

                Forms\Components\DatePicker::make('sale_date')
                    ->required()
                    ->default(now()),

                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),

                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('total_amount')->money('USD'),
                Tables\Columns\TextColumn::make('payment_method')->badge(),
                Tables\Columns\TextColumn::make('sale_date')->date(),
                Tables\Columns\TextColumn::make('user.name')->label('Sold By'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
        ];
    }
}
