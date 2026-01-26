<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalServiceResource\Pages;
use App\Models\PersonalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class PersonalServiceResource extends Resource
{
    protected static ?string $model = PersonalService::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Personal Services';

    protected static ?string $navigationGroup = 'Delivery and Service Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Service Details')
                ->schema([
                    Forms\Components\TextInput::make('reference_code')
                        ->disabled(),
                    Forms\Components\Select::make('service_type')
                        ->options(PersonalService::getServiceTypeOptions())
                        ->disabled(),
                    Forms\Components\TextInput::make('client_name')
                        ->disabled(),
                    Forms\Components\TextInput::make('phone')
                        ->disabled(),
                    Forms\Components\TextInput::make('destination')
                        ->disabled()
                        ->visible(fn ($record) => $record && $record->service_type === 'vacation'),
                    Forms\Components\DatePicker::make('start_date')
                        ->disabled(),
                    Forms\Components\DatePicker::make('end_date')
                        ->disabled(),
                    Forms\Components\TextInput::make('total_cost')
                        ->disabled()
                        ->prefix('$'),
                    Forms\Components\Select::make('status')
                        ->options(PersonalService::getStatusOptions())
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'redeemed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_type')
                    ->options(PersonalService::getServiceTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(PersonalService::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Action::make('mark_redeemed')
                    ->label('Mark as Redeemed')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->status === 'approved')
                    ->form([
                        Forms\Components\TextInput::make('redeemed_by')
                            ->label('Redeemed By (Staff Name)')
                            ->required(),
                        Forms\Components\Textarea::make('redemption_notes')
                            ->label('Notes')
                            ->maxLength(500),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->markAsRedeemed($data['redeemed_by'], $data['redemption_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Service Marked as Redeemed')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersonalServices::route('/'),
            'view' => Pages\ViewPersonalService::route('/{record}'),
            'edit' => Pages\EditPersonalService::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'approved')->count();
    }
}
