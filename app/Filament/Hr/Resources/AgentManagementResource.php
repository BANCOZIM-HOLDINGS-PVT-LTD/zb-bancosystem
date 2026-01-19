<?php

namespace App\Filament\Hr\Resources;

use App\Filament\Hr\Resources\AgentManagementResource\Pages;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgentManagementResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Agents';

    protected static ?string $navigationGroup = 'Personnel';

    protected static ?string $slug = 'agents'; // Override generic slug

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')->required(),
                        Forms\Components\TextInput::make('last_name')->required(),
                        Forms\Components\TextInput::make('national_id')
                            ->label('National ID')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')->tel()->required(),
                        Forms\Components\DatePicker::make('date_of_birth')->required(),
                        Forms\Components\Textarea::make('address')->columnSpanFull(),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\TextInput::make('region'),
                    ])->columns(2),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\TextInput::make('agent_code')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Select::make('type')
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'freelance' => 'Freelance',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                                'probation' => 'Probation',
                            ])
                            ->required()
                            ->default('active'),
                        Forms\Components\DatePicker::make('hire_date')->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->default(5.00),
                    ])->columns(2),

                Forms\Components\Section::make('Bank Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name'),
                        Forms\Components\TextInput::make('bank_account'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('full_name')->label('Name')->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        'probation' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('commission_rate')->suffix('%'),
                Tables\Columns\TextColumn::make('hire_date')->date(),
                Tables\Columns\TextColumn::make('phone'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'probation' => 'Probation',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'permanent' => 'Permanent',
                        'contract' => 'Contract',
                        'freelance' => 'Freelance',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAgentManagement::route('/'),
            'create' => Pages\CreateAgentManagement::route('/create'),
            'edit' => Pages\EditAgentManagement::route('/{record}/edit'),
        ];
    }
}
