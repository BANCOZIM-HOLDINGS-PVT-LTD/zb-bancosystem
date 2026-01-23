<?php

namespace App\Filament\Stores\Resources;

use App\Filament\Stores\Resources\PersonalServiceResource\Pages;
use App\Models\PersonalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PersonalServiceResource extends Resource
{
    protected static ?string $model = PersonalService::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Personal Services';

    protected static ?string $navigationGroup = 'Procurement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Service Details')
                ->schema([
                    Forms\Components\TextInput::make('reference_code')->disabled(),
                    Forms\Components\Select::make('service_type')
                        ->options(PersonalService::getServiceTypeOptions())
                        ->disabled(),
                    Forms\Components\TextInput::make('client_name')->disabled(),
                    Forms\Components\TextInput::make('phone')->disabled(),
                    Forms\Components\TextInput::make('destination')->disabled(),
                    Forms\Components\DatePicker::make('start_date')->disabled(),
                    Forms\Components\DatePicker::make('end_date')->disabled(),
                    Forms\Components\TextInput::make('total_cost')->disabled()->prefix('$'),
                    Forms\Components\Select::make('status')
                        ->options(PersonalService::getStatusOptions())
                        ->disabled(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->searchable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('client_name')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('start_date')->date('M j, Y'),
                Tables\Columns\TextColumn::make('total_cost')->money('USD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'redeemed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_type')
                    ->options(PersonalService::getServiceTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(PersonalService::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersonalServices::route('/'),
            'view' => Pages\ViewPersonalService::route('/{record}'),
        ];
    }

    // View-only access for Stores
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
