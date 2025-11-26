<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationLabel = 'Campaigns';
    
    protected static ?string $navigationGroup = 'Agent Management';
    
    protected static ?int $navigationSort = 3;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->after('start_date'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('active'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Performance Targets')
                    ->schema([
                        Forms\Components\TextInput::make('target_applications')
                            ->numeric()
                            ->label('Target Applications')
                            ->helperText('Total number of applications goal'),
                        Forms\Components\TextInput::make('target_sales')
                            ->numeric()
                            ->prefix('$')
                            ->label('Target Sales (USD)')
                            ->helperText('Total sales amount goal'),
                        Forms\Components\TextInput::make('target_conversions')
                            ->numeric()
                            ->label('Target Conversions')
                            ->helperText('Number of approved applications goal'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Assign Agents')
                    ->schema([
                        Forms\Components\CheckboxList::make('agents')
                            ->relationship('agents', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Agent $record) => $record->display_name)
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row'),
                    ])
                    ->collapsible()
                    ->collapsed(fn (?Model $record) => $record !== null),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->date('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->date('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('agents_count')
                    ->counts('agents')
                    ->label('Agents')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('total_applications')
                    ->label('Total Apps')
                    ->getStateUsing(fn (Model $record) => $record->total_applications)
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->getStateUsing(fn (Model $record) => '$' . number_format($record->total_sales, 2))
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Start From'),
                        Forms\Components\DatePicker::make('until')->label('End Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'view' => Pages\ViewCampaign::route('/{record}'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
