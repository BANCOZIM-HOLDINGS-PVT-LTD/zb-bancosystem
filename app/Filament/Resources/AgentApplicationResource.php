<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentApplicationResource\Pages;
use App\Models\AgentApplication;
use App\Services\RapiWhaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AgentApplicationResource extends Resource
{
    protected static ?string $model = AgentApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationLabel = 'Agent Applications';
    
    protected static ?string $navigationGroup = 'Agent Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('application_number')
                            ->label('Application #')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->requiredIf('status', 'rejected'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('surname')
                            ->label('Surname')
                            ->disabled(),
                        Forms\Components\TextInput::make('id_number')
                            ->label('ID Number'),
                        Forms\Components\TextInput::make('voice_number')
                            ->label('SMS/Voice Number')
                            ->disabled(),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Other' => 'Other',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('age_range')
                            ->disabled(),
                        Forms\Components\TextInput::make('province')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp (Source)')
                            ->disabled(),
                        Forms\Components\TextInput::make('whatsapp_contact')
                            ->label('WhatsApp Contact')
                            ->disabled(),
                        Forms\Components\TextInput::make('ecocash_number')
                            ->label('EcoCash Number')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Agent Details')
                    ->schema([
                        Forms\Components\TextInput::make('agent_code')
                            ->label('Agent Code')
                            ->disabled()
                            ->helperText('Auto-generated upon approval'),
                        Forms\Components\TextInput::make('referral_link')
                            ->label('Referral Link')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->status === 'approved'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Agent Name')
                    ->getStateUsing(fn (AgentApplication $record) => $record->first_name . ' ' . $record->surname)
                    ->searchable(['first_name', 'surname'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_number')
                    ->label('ID Number')
                    ->searchable()
                    ->placeholder('Not provided'),
                Tables\Columns\TextColumn::make('voice_number')
                    ->label('SMS Number')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('province')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('agent_code')
                    ->label('Agent Code')
                    ->copyable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('province')
                    ->options([
                        'Harare' => 'Harare',
                        'Bulawayo' => 'Bulawayo',
                        'Mash East' => 'Mashonaland East',
                        'Mash West' => 'Mashonaland West',
                        'Manicaland' => 'Manicaland',
                        'Masvingo' => 'Masvingo',
                        'Midlands' => 'Midlands',
                    ]),
            ])
            ->actions([
                // View ID Documents
                Action::make('viewId')
                    ->label('View ID')
                    ->icon('heroicon-o-identification')
                    ->color('info')
                    ->modalHeading('ID Documents')
                    ->modalContent(fn (AgentApplication $record) => view('filament.modals.agent-id-documents', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                
                // Approve Action
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Agent Application')
                    ->modalDescription('This will generate an agent code and send approval notification via WhatsApp.')
                    ->visible(fn (AgentApplication $record) => $record->status === 'pending')
                    ->action(function (AgentApplication $record) {
                        $record->approve();
                        
                        // Send WhatsApp notification
                        try {
                            $rapiWhaService = app(RapiWhaService::class);
                            $from = 'whatsapp:+' . $record->whatsapp_contact;
                            
                            $msg = "🎉 *Congratulations, {$record->first_name}!*\n\n";
                            $msg .= "Your agent application has been *APPROVED*!\n\n";
                            $msg .= "🔐 Your Agent Code: *{$record->agent_code}*\n\n";
                            $msg .= "🔗 Your Referral Link:\n{$record->referral_link}\n\n";
                            $msg .= "📱 To access your Agent Portal, visit:\n";
                            $msg .= config('app.url') . "/agent/login\n\n";
                            $msg .= "Use your Agent Code to login and start earning commissions!\n\n";
                            $msg .= "Welcome to Microbiz Zimbabwe! 🚀";
                            
                            $rapiWhaService->sendMessage($from, $msg);
                            
                            Log::info('Agent approval WhatsApp sent', ['agent_code' => $record->agent_code]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send agent approval WhatsApp: ' . $e->getMessage());
                        }
                        
                        Notification::make()
                            ->title('Agent Approved')
                            ->body("Agent code {$record->agent_code} generated and sent to {$record->first_name}")
                            ->success()
                            ->send();
                    }),
                
                // Reject Action
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Agent Application')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->helperText('This reason will be sent to the applicant and they can reapply.')
                            ->maxLength(500),
                    ])
                    ->visible(fn (AgentApplication $record) => $record->status === 'pending')
                    ->action(function (AgentApplication $record, array $data) {
                        $record->rejection_reason = $data['rejection_reason'];
                        $record->reject();
                        
                        // Send WhatsApp notification
                        try {
                            $rapiWhaService = app(RapiWhaService::class);
                            $from = 'whatsapp:+' . $record->whatsapp_contact;
                            
                            $msg = "Hi {$record->first_name},\n\n";
                            $msg .= "Unfortunately, your agent application could not be approved at this time.\n\n";
                            $msg .= "📋 *Reason:* {$data['rejection_reason']}\n\n";
                            $msg .= "You may reapply after addressing the above concerns by sending 'Hi' to start a new application.\n\n";
                            $msg .= "Thank you for your interest in Microbiz Zimbabwe.";
                            
                            $rapiWhaService->sendMessage($from, $msg);
                        } catch (\Exception $e) {
                            Log::error('Failed to send agent rejection WhatsApp: ' . $e->getMessage());
                        }
                        
                        Notification::make()
                            ->title('Application Rejected')
                            ->body("Application from {$record->first_name} has been rejected. They can reapply.")
                            ->warning()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve();
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Bulk Approval Complete')
                                ->body("{$count} applications approved")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('application_number')
                            ->label('Application #'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->visible(fn ($record) => $record->status === 'rejected'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Applied On')
                            ->dateTime(),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name'),
                        Infolists\Components\TextEntry::make('surname'),
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('ID Number')
                            ->placeholder('Not provided'),
                        Infolists\Components\TextEntry::make('gender'),
                        Infolists\Components\TextEntry::make('age_range'),
                        Infolists\Components\TextEntry::make('province'),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Contact Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('voice_number')
                            ->label('SMS Number'),
                        Infolists\Components\TextEntry::make('whatsapp_contact'),
                        Infolists\Components\TextEntry::make('ecocash_number'),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('ID Documents')
                    ->schema([
                        Infolists\Components\ImageEntry::make('id_front_url')
                            ->label('ID Front')
                            ->height(200),
                        Infolists\Components\ImageEntry::make('id_back_url')
                            ->label('ID Back')
                            ->height(200),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Agent Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('agent_code')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('referral_link')
                            ->copyable()
                            ->url(fn ($record) => $record->referral_link, true),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->status === 'approved'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentApplications::route('/'),
            'view' => Pages\ViewAgentApplication::route('/{record}'),
            'edit' => Pages\EditAgentApplication::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    
    /**
     * Disable create - applications come from WhatsApp only
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
