<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'approved',
                        'success' => 'paid',
                    ]),
                Tables\Columns\TextColumn::make('earned_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\DatePicker::make('paid_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('payment_method')
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference'),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_date' => $data['paid_date'],
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                        ]);
                        
                        // Update agent's last commission amount
                        $agent = $record->agent;
                        if ($agent) {
                            $agent->update(['last_commission_amount' => $record->amount]);
                            
                            // Log activity for the agent
                            \App\Models\AgentActivityLog::create([
                                'agent_id' => $agent->id,
                                'agent_type' => $record->agent_type === 'agent_applications' ? 'agent_applications' : 'agents',
                                'activity_type' => 'commission_paid',
                                'description' => "Commission of $" . number_format($record->amount, 2) . " paid via " . $data['payment_method'] . ".",
                                'metadata' => [
                                    'commission_id' => $record->id,
                                    'reference' => $record->reference_number,
                                    'paid_date' => $data['paid_date'],
                                ],
                            ]);
                        }

                        Notification::make()
                            ->title('Commission marked as paid')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
