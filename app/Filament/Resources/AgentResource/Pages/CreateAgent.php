<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\AgentApplication;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;
    
    /**
     * Get the form for creating from approved application
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Create from Approved Application')
                    ->description('Search and select an approved agent application to create an agent.')
                    ->schema([
                        Forms\Components\Select::make('agent_application_id')
                            ->label('Search Approved Applications')
                            ->placeholder('Search by name, application number, or phone...')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return AgentApplication::where('status', 'approved')
                                    ->where(function ($query) use ($search) {
                                        $query->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('surname', 'like', "%{$search}%")
                                            ->orWhere('application_number', 'like', "%{$search}%")
                                            ->orWhere('voice_number', 'like', "%{$search}%")
                                            ->orWhere('agent_code', 'like', "%{$search}%");
                                    })
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($app) => [
                                        $app->id => "{$app->application_number} - {$app->first_name} {$app->surname} ({$app->agent_code})"
                                    ]);
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $app = AgentApplication::find($value);
                                return $app ? "{$app->application_number} - {$app->first_name} {$app->surname} ({$app->agent_code})" : null;
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $app = AgentApplication::find($state);
                                    if ($app) {
                                        $set('agent_code', $app->agent_code);
                                        $set('first_name', $app->first_name);
                                        $set('last_name', $app->surname);
                                        $set('phone', $app->voice_number);
                                        $set('national_id', $app->id_number);
                                        $set('region', $app->province);
                                        $set('ecocash_number', $app->ecocash_number);
                                        $set('ecocash_name', $app->first_name . ' ' . $app->surname);
                                    }
                                }
                            }),
                    ]),
                    
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('agent_code')
                            ->label('Agent Code')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->helperText('Optional - can be added later'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('national_id')
                            ->label('National ID')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Agent Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
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
                        Forms\Components\Select::make('agent_type')
                            ->label('Agent Type')
                            ->options([
                                'online' => 'Online Agent',
                                'physical' => 'Physical Agent',
                            ])
                            ->default('online')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'online') {
                                    $set('commission_rate', 0.3);
                                } elseif ($state === 'physical') {
                                    $set('commission_rate', 3.5);
                                }
                            }),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0.3),
                        Forms\Components\TextInput::make('region')
                            ->label('Province/Region')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('EcoCash Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('ecocash_name')
                            ->label('EcoCash Account Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ecocash_number')
                            ->label('EcoCash Number')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Agent Created Successfully')
            ->body("Agent {$this->record->agent_code} has been created from the approved application.")
            ->success()
            ->send();
    }
}
