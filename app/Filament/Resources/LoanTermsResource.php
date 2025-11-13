<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanTermsResource\Pages;
use App\Models\LoanTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class LoanTermsResource extends Resource
{
    protected static ?string $model = LoanTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Loan Terms';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Standard 12-Month Term'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Detailed description of this loan term...'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Loan Configuration')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('duration_months')
                                    ->label('Duration (Months)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(360),
                                Forms\Components\TextInput::make('interest_rate')
                                    ->label('Interest Rate (%)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->required(),
                                Forms\Components\Select::make('payment_frequency')
                                    ->label('Payment Frequency')
                                    ->options([
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Bi-weekly',
                                        'monthly' => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'annually' => 'Annually',
                                    ])
                                    ->default('monthly')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('interest_type')
                                    ->label('Interest Type')
                                    ->options([
                                        'simple' => 'Simple Interest',
                                        'compound' => 'Compound Interest',
                                        'flat' => 'Flat Rate',
                                        'reducing' => 'Reducing Balance',
                                        'custom' => 'Custom Formula',
                                    ])
                                    ->default('reducing')
                                    ->required()
                                    ->reactive(),
                                Forms\Components\Select::make('calculation_method')
                                    ->label('Calculation Method')
                                    ->options([
                                        'standard' => 'Standard Calculation',
                                        'custom_formula' => 'Custom Formula',
                                        'tiered' => 'Tiered Rates',
                                        'percentage_of_income' => 'Percentage of Income',
                                    ])
                                    ->default('standard')
                                    ->required()
                                    ->reactive(),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Amount Limits')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('minimum_amount')
                                    ->label('Minimum Loan Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('maximum_amount')
                                    ->label('Maximum Loan Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Fees & Charges')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('processing_fee')
                                    ->label('Processing Fee')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0),
                                Forms\Components\Select::make('processing_fee_type')
                                    ->label('Fee Type')
                                    ->options([
                                        'fixed' => 'Fixed Amount ($)',
                                        'percentage' => 'Percentage (%)',
                                    ])
                                    ->default('fixed')
                                    ->required(),
                                Forms\Components\TextInput::make('insurance_rate')
                                    ->label('Insurance Rate (%)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->default(0),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('insurance_required')
                                    ->label('Insurance Required'),
                                Forms\Components\TextInput::make('early_payment_penalty')
                                    ->label('Early Payment Penalty (%)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->default(0),
                                Forms\Components\TextInput::make('late_payment_penalty')
                                    ->label('Late Payment Penalty (%)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->default(0),
                            ]),
                        Forms\Components\TextInput::make('grace_period_days')
                            ->label('Grace Period (Days)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Custom Formula')
                    ->schema([
                        Forms\Components\Textarea::make('custom_formula')
                            ->label('Custom Calculation Formula')
                            ->placeholder('e.g., ({amount} * {rate} / 100 / 12) + {processing_fee}')
                            ->helperText('Available variables: {amount}, {rate}, {months}, {processing_fee}')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('calculation_method') === 'custom_formula'),
                    ])
                    ->columns(1)
                    ->visible(fn (Forms\Get $get) => $get('calculation_method') === 'custom_formula'),

                Forms\Components\Section::make('Status & Availability')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Term for Product'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('effective_date')
                                    ->label('Effective From'),
                                Forms\Components\DateTimePicker::make('expiry_date')
                                    ->label('Expires On'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Terms & Conditions')
                    ->schema([
                        Forms\Components\KeyValue::make('conditions')
                            ->label('Additional Conditions')
                            ->keyLabel('Condition')
                            ->valueLabel('Requirement'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Term Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Duration')
                    ->suffix(' months')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Interest Rate')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'simple' => 'gray',
                        'compound' => 'warning',
                        'flat' => 'info',
                        'reducing' => 'success',
                        'custom' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_frequency')
                    ->label('Frequency')
                    ->badge(),
                Tables\Columns\TextColumn::make('minimum_amount')
                    ->label('Min Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maximum_amount')
                    ->label('Max Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name'),
                SelectFilter::make('interest_type')
                    ->options([
                        'simple' => 'Simple Interest',
                        'compound' => 'Compound Interest',
                        'flat' => 'Flat Rate',
                        'reducing' => 'Reducing Balance',
                        'custom' => 'Custom Formula',
                    ]),
                SelectFilter::make('calculation_method')
                    ->options([
                        'standard' => 'Standard Calculation',
                        'custom_formula' => 'Custom Formula',
                        'tiered' => 'Tiered Rates',
                        'percentage_of_income' => 'Percentage of Income',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Terms'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('calculate_payment')
                    ->label('Calculate Payment')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('loan_amount')
                            ->label('Loan Amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(10000),
                    ])
                    ->action(function ($record, array $data): void {
                        $loanAmount = $data['loan_amount'];
                        $calculation = $record->calculateTotalCost($loanAmount);
                        
                        $details = "Loan Amount: $" . number_format($calculation['loan_amount'], 2) . "\n";
                        $details .= "Monthly Payment: $" . number_format($calculation['monthly_payment'], 2) . "\n";
                        $details .= "Total Interest: $" . number_format($calculation['total_interest'], 2) . "\n";
                        $details .= "Processing Fee: $" . number_format($calculation['processing_fee'], 2) . "\n";
                        $details .= "Total Cost: $" . number_format($calculation['total_cost'], 2);
                        
                        Notification::make()
                            ->title('Payment Calculation')
                            ->body($details)
                            ->info()
                            ->persistent()
                            ->send();
                    })
                    ->modalHeading('Calculate Loan Payment')
                    ->modalDescription('Enter a loan amount to see the payment calculation.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Loan Terms Activated')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Loan Terms Deactivated')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListLoanTerms::route('/'),
            'create' => Pages\CreateLoanTerms::route('/create'),
            'view' => Pages\ViewLoanTerms::route('/{record}'),
            'edit' => Pages\EditLoanTerms::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
