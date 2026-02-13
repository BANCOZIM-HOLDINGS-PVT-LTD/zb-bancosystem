<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaidDepositCreditsResource\Pages;
use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaidDepositCreditsResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Paid Deposit Credits';
    
    protected static ?string $modelLabel = 'Deposit Credit Application';

    protected static ?string $navigationGroup = 'Finance & Delivery';
    
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Filter for applications with creditType starting with PDC
        // We use a raw query or check the JSON field
        $query = parent::getEloquentQuery();
        
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                
        if ($isPgsql) {
            $query->whereRaw("form_data->>'creditType' LIKE 'PDC%'");
        } else {
            $query->whereRaw("JSON_EXTRACT(form_data, '$.creditType') LIKE 'PDC%'");
        }
        
        // Also ensure they are at least approved or in a relevant state
        return $query->whereIn('current_step', ['approved', 'processing', 'completed', 'account_opened']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('credit_type')
                            ->label('Credit Type')
                            ->formatStateUsing(fn (Model $record) => $record->form_data['creditType'] ?? 'N/A')
                            ->disabled(),
                         Forms\Components\TextInput::make('deposit_amount')
                            ->label('Deposit Amount')
                            ->prefix('$')
                            ->formatStateUsing(fn (Model $record) => number_format($record->deposit_amount ?? 0, 2))
                            ->disabled(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Payment Status')
                    ->schema([
                        Forms\Components\Toggle::make('deposit_paid')
                            ->label('Deposit Paid')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('deposit_paid_at')
                            ->label('Paid At')
                            ->disabled(),
                        Forms\Components\TextInput::make('deposit_payment_method')
                            ->label('Method')
                            ->disabled(),
                        Forms\Components\TextInput::make('deposit_transaction_id')
                            ->label('Transaction ID')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) => 
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['surname'] ?? ''))
                    )
                    ->searchable(['reference_code']),
                Tables\Columns\BadgeColumn::make('credit_type')
                    ->label('Type')
                    ->getStateUsing(fn (Model $record) => $record->form_data['creditType'] ?? 'N/A')
                    ->colors([
                        'primary' => 'PDC30',
                        'purple' => 'PDC50',
                    ]),
                Tables\Columns\TextColumn::make('deposit_amount')
                    ->label('Dep. Amount')
                    ->money('USD')
                    ->getStateUsing(fn (Model $record) => $record->deposit_amount ?? 0),
                Tables\Columns\IconColumn::make('deposit_paid')
                    ->label('Paid')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('has_delivery')
                    ->label('Delivery')
                    ->boolean()
                    ->getStateUsing(fn (Model $record) => $record->delivery()->exists())
                    ->trueIcon('heroicon-o-truck')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->filters([
                Tables\Filters\Filter::make('paid')
                    ->label('Paid')
                    ->query(fn (Builder $query) => $query->where('deposit_paid', true)),
                Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid')
                    ->query(fn (Builder $query) => $query->where('deposit_paid', false)),
                Tables\Filters\Filter::make('no_delivery')
                    ->label('Missing Delivery')
                    ->query(fn (Builder $query) => $query->where('deposit_paid', true)->whereDoesntHave('delivery')),
            ])
            ->actions([
                // Action to manually mark as paid
                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Model $record) => !$record->deposit_paid)
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'ecocash' => 'EcoCash',
                                'swipe' => 'Swipe',
                            ])
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->update([
                            'deposit_paid' => true,
                            'deposit_paid_at' => now(),
                            'deposit_payment_method' => $data['payment_method'],
                            'deposit_transaction_id' => $data['transaction_reference'],
                            'current_step' => 'processing',
                        ]);
                        
                        Notification::make()->title('Deposit Marked as Paid')->success()->send();
                        
                        // Trigger delivery creation if not exists
                        // We can call the ApplicationWorkflowService or just create it here
                        // Let's use the DepositPaymentController logic or Workflow logic
                        // Reusing code from DepositPaymentController logic would be best but it's in a controller.
                        // We'll duplicate the simple creation logic here or call a service.
                        // Let's use the loop from workflow service manually
                        
                        self::createDeliveryTracking($record);
                    }),
                    
                // Action to initiate delivery if paid but missing
                Action::make('initiate_delivery')
                    ->label('Initiate Delivery')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->visible(fn (Model $record) => $record->deposit_paid && !$record->delivery()->exists())
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        self::createDeliveryTracking($record);
                        Notification::make()->title('Delivery Initiated')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaidDepositCredits::route('/'),
        ];
    }
    
    protected static function createDeliveryTracking(ApplicationState $application)
    {
        try {
            if ($application->delivery()->exists()) {
                return;
            }

            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            
            // Extract info
            $clientName = trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['surname'] ?? ''));
            $product = $formData['business'] ?? $formData['category'] ?? 'N/A';
            
             // Create delivery tracking record
            DeliveryTracking::create([
                'application_state_id' => $application->id,
                'recipient_name' => $clientName ?: 'Customer',
                'recipient_phone' => $formResponses['mobile'] ?? $formResponses['cellNumber'] ?? '',
                'client_national_id' => $formResponses['nationalIdNumber'] ?? '',
                'product_type' => $product,
                'delivery_depot' => 'Pending Assignment',
                'courier_type' => 'Pending Assignment',
                'status' => 'pending', // or processing
                'status_history' => json_encode([
                    [
                        'status' => 'pending',
                        'notes' => 'Delivery initiated via Admin Panel (Paid Deposit Credits)',
                        'updated_at' => now()->toIso8601String(),
                        'updated_by' => auth()->user()->name ?? 'System',
                    ]
                ]),
            ]);
            
        } catch (\Exception $e) {
            Notification::make()->title('Error creating delivery')->body($e->getMessage())->danger()->send();
        }
    }
}
