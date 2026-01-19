<?php

namespace App\Filament\Hr\Resources;

use App\Filament\Hr\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Personnel';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\TextInput::make('email')->email()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\TextInput::make('position')->required(),
                Forms\Components\TextInput::make('department')->required(),
                Forms\Components\Select::make('employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'intern' => 'Intern',
                        'contract' => 'Contract',
                    ])
                    ->required()
                    ->default('full_time'),
                Forms\Components\TextInput::make('salary')->numeric()->prefix('$')->required(),
                Forms\Components\DatePicker::make('joined_date')->required(),
                Forms\Components\TextInput::make('vacation_days')->numeric()->default(0),
                Forms\Components\TextInput::make('sick_days')->numeric()->default(0),
                Forms\Components\Select::make('performance_rating')
                    ->options([
                        1 => '1 - Poor',
                        2 => '2 - Needs Improvement',
                        3 => '3 - Meets Expectations',
                        4 => '4 - Exceeds Expectations',
                        5 => '5 - Outstanding',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Name')->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('position')->searchable(),
                Tables\Columns\TextColumn::make('department')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('employment_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_time' => 'success',
                        'part_time' => 'info',
                        'intern' => 'warning',
                        'contract' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('salary')->money('USD'),
                Tables\Columns\TextColumn::make('joined_date')->date(),
                Tables\Columns\TextColumn::make('performance_rating')->label('Rating'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
