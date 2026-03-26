<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentRewardResource\Pages;
use App\Models\AgentReward;
use App\Models\AgentActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AgentRewardResource extends Resource
{
    protected static ?string $model = AgentReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Agent Rewards';

    protected static ?string $navigationGroup = 'Agent Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Details')
                    ->schema([
                        Forms\Components\Select::make('agent_id')
                            ->label('Agent')
                            ->relationship('agent', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name ?? ($record->first_name . " " . ($record->last_name ?? $record->surname) . " (" . $record->agent_code . ")"))
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('reward_type')
                            ->label('Reward Type')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'sent' => 'Sent',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Sent At')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent.agent_code')
                    ->label('Agent Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('agent.first_name')
                    ->label('Agent Name')
                    ->getStateUsing(fn ($record) => ($record->agent->first_name ?? '') . ' ' . ($record->agent->last_name ?? $record->agent->surname ?? ''))
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('reward_type')
                    ->colors([
                        'primary' => 'data_5gb',
                        'info' => 'data_4gb',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'sent',
                    ]),

                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Actions\Action::make('markSent')
                    ->label('Mark Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (AgentReward $record) => $record->status === 'pending')
                    ->action(function (AgentReward $record): void {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        // Log activity for the agent
                        AgentActivityLog::create([
                            'agent_id' => $record->agent_id,
                            'agent_type' => $record->agent_type,
                            'activity_type' => 'reward_received',
                            'description' => "Reward received: " . str_replace('_', ' ', strtoupper($record->reward_type)) . ".",
                            'metadata' => [
                                'reward_id' => $record->id,
                                'reward_type' => $record->reward_type,
                                'sent_at' => now()->toISOString(),
                            ],
                        ]);

                        Notification::make()
                            ->title('Reward marked as sent')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
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
            'index' => Pages\ListAgentRewards::route('/'),
        ];
    }
}
