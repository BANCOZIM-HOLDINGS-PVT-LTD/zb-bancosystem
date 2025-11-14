<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use App\Services\AgentReferralLinkService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Agents';

    protected static ?string $navigationGroup = 'Agent Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('agent_code')
                            ->label('Agent Code')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated if empty'),

                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('national_id')
                            ->label('National ID')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth'),

                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Hire Date')
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Agent Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'individual' => 'Individual',
                                'corporate' => 'Corporate',
                            ])
                            ->default('individual')
                            ->required(),

                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0.00),

                        Forms\Components\TextInput::make('region')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_account')
                            ->label('Account Number')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn (Agent $record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'suspended',
                    ]),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'individual',
                        'secondary' => 'corporate',
                    ]),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_applications')
                    ->label('Applications')
                    ->getStateUsing(fn (Agent $record) => $record->total_applications)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Conversion %')
                    ->getStateUsing(fn (Agent $record) => $record->conversion_rate.'%')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('total_commission_earned')
                    ->label('Total Earned')
                    ->getStateUsing(fn (Agent $record) => '$'.number_format($record->total_commission_earned, 2))
                    ->sortable(false),

                Tables\Columns\TextColumn::make('region')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'individual' => 'Individual',
                        'corporate' => 'Corporate',
                    ]),

                SelectFilter::make('region')
                    ->options(function () {
                        return Agent::whereNotNull('region')
                            ->distinct()
                            ->pluck('region', 'region')
                            ->toArray();
                    }),

                Filter::make('commission_rate')
                    ->form([
                        Forms\Components\TextInput::make('min_rate')
                            ->numeric()
                            ->placeholder('Min %'),
                        Forms\Components\TextInput::make('max_rate')
                            ->numeric()
                            ->placeholder('Max %'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_rate'],
                                fn (Builder $query, $rate): Builder => $query->where('commission_rate', '>=', $rate),
                            )
                            ->when(
                                $data['max_rate'],
                                fn (Builder $query, $rate): Builder => $query->where('commission_rate', '<=', $rate),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('generateReferralLink')
                    ->label('Generate Link')
                    ->icon('heroicon-o-link')
                    ->form([
                        Forms\Components\TextInput::make('campaign_name')
                            ->label('Campaign Name')
                            ->placeholder('e.g., Summer 2024 Campaign'),
                    ])
                    ->action(function (Agent $record, array $data): void {
                        $link = $record->generateReferralLink($data['campaign_name'] ?? null);

                        Notification::make()
                            ->title('Referral link generated')
                            ->body("Link: {$link->url}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (Agent $record) => $record->status === 'active' ? 'Deactivate' : 'Activate')
                    ->icon(fn (Agent $record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Agent $record) => $record->status === 'active' ? 'danger' : 'success')
                    ->action(function (Agent $record): void {
                        $newStatus = $record->status === 'active' ? 'inactive' : 'active';
                        $record->update(['status' => $newStatus]);

                        Notification::make()
                            ->title('Agent status updated')
                            ->body("Agent is now {$newStatus}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $records->each->update(['status' => 'active']);

                            Notification::make()
                                ->title('Agents activated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            $records->each->update(['status' => 'inactive']);

                            Notification::make()
                                ->title('Agents deactivated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('generate_bulk_referral_links')
                        ->label('Generate Referral Links')
                        ->icon('heroicon-o-link')
                        ->color('primary')
                        ->form([
                            Forms\Components\TextInput::make('campaign_name')
                                ->label('Campaign Name')
                                ->default('Bulk Campaign '.now()->format('Y-m-d'))
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Campaign Description')
                                ->placeholder('Description for this referral campaign...')
                                ->rows(2),
                            Forms\Components\DatePicker::make('expires_at')
                                ->label('Expiration Date')
                                ->default(now()->addMonths(6))
                                ->required(),
                            Forms\Components\TextInput::make('max_uses')
                                ->label('Maximum Uses')
                                ->numeric()
                                ->placeholder('Leave empty for unlimited uses'),
                        ])
                        ->action(function ($records, array $data): void {
                            $referralService = app(AgentReferralLinkService::class);
                            $agentIds = $records->pluck('id')->toArray();

                            $results = $referralService->generateBulkLinks($agentIds, [
                                'campaign_name' => $data['campaign_name'],
                                'description' => $data['description'] ?? null,
                                'expires_at' => $data['expires_at'],
                                'max_uses' => $data['max_uses'] ?? null,
                            ]);

                            $successCount = count($results['success']);
                            $failedCount = count($results['failed']);

                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title("Generated {$successCount} Referral Links")
                                    ->body('All selected agents now have new referral links.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("Generated {$successCount} Referral Links")
                                    ->body("{$failedCount} links failed to generate. Check logs for details.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Bulk Referral Links')
                        ->modalDescription('This will create new referral links for all selected agents.'),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'view' => Pages\ViewAgent::route('/{record}'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
