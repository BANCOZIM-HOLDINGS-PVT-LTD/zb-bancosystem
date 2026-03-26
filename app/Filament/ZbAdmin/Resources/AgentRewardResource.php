<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\AgentRewardResource\Pages;
use App\Models\AgentReward;
use App\Models\Agent;
use App\Models\AgentApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AgentRewardResource extends Resource
{
    protected static ?string $model = AgentReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Agent Rewards';

    protected static ?string $navigationGroup = 'Agent Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agent_type')
                    ->options([
                        'agents' => 'Physical Agent',
                        'agent_applications' => 'Online Agent',
                    ])
                    ->required()
                    ->live(),
                
                Forms\Components\Select::make('agent_id')
                    ->label('Agent')
                    ->options(function (callable $get) {
                        $type = $get('agent_type');
                        if ($type === 'agents') {
                            return Agent::pluck('first_name', 'id');
                        } elseif ($type === 'agent_applications') {
                            return AgentApplication::pluck('first_name', 'id');
                        }
                        return [];
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('reward_type')
                    ->required()
                    ->placeholder('e.g. data_5gb, data_4gb'),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                    ])
                    ->default('pending')
                    ->required(),

                Forms\Components\DateTimePicker::make('sent_at'),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'agents' ? 'Physical' : 'Online'),
                
                Tables\Columns\TextColumn::make('agent_name')
                    ->label('Agent Name')
                    ->getStateUsing(function (AgentReward $record) {
                        if ($record->agent_type === 'agents') {
                            $agent = Agent::find($record->agent_id);
                            return $agent ? $agent->full_name : 'Unknown';
                        } else {
                            $agent = AgentApplication::find($record->agent_id);
                            return $agent ? ($agent->first_name . ' ' . $agent->surname) : 'Unknown';
                        }
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('reward_type')
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                    ]),

                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsSent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AgentReward $record) => $record->status === 'pending')
                    ->action(function (AgentReward $record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Reward marked as sent')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentRewards::route('/'),
            'create' => Pages\CreateAgentReward::route('/create'),
            'edit' => Pages\EditAgentReward::route('/{record}/edit'),
        ];
    }
}
