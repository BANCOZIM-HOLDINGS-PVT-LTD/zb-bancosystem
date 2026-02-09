<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerManagementResource\Pages;
use App\Models\BulkSMSCampaign;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CustomerManagementResource extends BaseResource
{
    protected static ?string $model = BulkSMSCampaign::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Customer Management';
    
    protected static ?string $navigationGroup = 'Communications';
    
    protected static ?string $modelLabel = 'SMS Campaign';
    
    protected static ?string $pluralModelLabel = 'SMS Campaigns';
    
    protected static ?int $navigationSort = 1;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., February Birthday Wishes')
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('type')
                            ->options(BulkSMSCampaign::getTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $set('message_template', BulkSMSCampaign::getDefaultTemplate($state));
                                }
                            }),
                            
                        Forms\Components\Select::make('status')
                            ->options(BulkSMSCampaign::getStatuses())
                            ->default('draft')
                            ->required(),
                            
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule Send Time')
                            ->native(false)
                            ->helperText('Leave empty to send immediately when ready'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Message Template')
                    ->schema([
                        Forms\Components\Textarea::make('message_template')
                            ->required()
                            ->rows(4)
                            ->helperText('Available placeholders: {name}, {reference}, {amount}, {date}, {holiday}')
                            ->columnSpanFull(),
                            
                        Forms\Components\Placeholder::make('character_count')
                            ->label('SMS Segments')
                            ->content(fn (Get $get) => self::calculateSmsSegments($get('message_template') ?? ''))
                            ->helperText('160 chars = 1 SMS, 306 chars = 2 SMS, etc.'),
                    ]),
                    
                Forms\Components\Section::make('Recipient Filters')
                    ->schema([
                        Forms\Components\KeyValue::make('recipient_filters')
                            ->keyLabel('Filter')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Filter')
                            ->helperText('Optional: Add filters to target specific users')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->placeholder('Internal notes about this campaign...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BulkSMSCampaign::getTypes()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'birthday' => 'success',
                        'holiday' => 'warning',
                        'installment_due' => 'danger',
                        'incomplete_application' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BulkSMSCampaign::getStatuses()[$state] ?? $state)
                    ->color(fn (Model $record): string => $record->status_color),
                    
                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->numeric()
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Not scheduled'),
                    
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Not sent'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(BulkSMSCampaign::getTypes()),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options(BulkSMSCampaign::getStatuses()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('send')
                        ->label('Send Now')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Send SMS Campaign')
                        ->modalDescription('Are you sure you want to send this campaign now? This will send SMS to all matching recipients.')
                        ->visible(fn (Model $record) => $record->canBeSent())
                        ->action(fn (Model $record) => static::sendCampaign($record)),
                    Tables\Actions\Action::make('preview')
                        ->label('Preview Recipients')
                        ->icon('heroicon-o-users')
                        ->color('info')
                        ->url(fn (Model $record) => static::getUrl('preview', ['record' => $record])),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerManagement::route('/'),
            'create' => Pages\CreateCustomerManagement::route('/create'),
            'view' => Pages\ViewCustomerManagement::route('/{record}'),
            'edit' => Pages\EditCustomerManagement::route('/{record}/edit'),
            'preview' => Pages\PreviewRecipients::route('/{record}/preview'),
            'reports' => Pages\ReportsDownload::route('/reports'),
        ];
    }
    
    /**
     * Calculate SMS segments based on message length
     */
    protected static function calculateSmsSegments(string $message): string
    {
        $length = strlen($message);
        if ($length === 0) return '0 characters (0 SMS)';
        
        $segments = $length <= 160 ? 1 : ceil($length / 153);
        return "{$length} characters ({$segments} SMS segment" . ($segments > 1 ? 's' : '') . ")";
    }
    
    /**
     * Send campaign
     */
    protected static function sendCampaign(BulkSMSCampaign $campaign): void
    {
        // This will be handled by the SendCampaign page/job
        $campaign->update(['status' => 'sending']);
        
        // Dispatch job to send SMS in background
        dispatch(function () use ($campaign) {
            app(\App\Services\CustomerManagementService::class)->sendCampaign($campaign);
        })->afterCommit();
        
        \Filament\Notifications\Notification::make()
            ->title('Campaign Started')
            ->body('SMS campaign is now being sent in the background.')
            ->success()
            ->send();
    }
}
