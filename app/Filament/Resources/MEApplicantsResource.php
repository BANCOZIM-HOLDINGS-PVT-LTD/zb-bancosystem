<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MEApplicantsResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MEApplicantsResource extends Resource
{
    protected static ?string $model = ApplicationState::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'M&E Applicants';
    
    protected static ?string $navigationGroup = 'Communications';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $modelLabel = 'M&E Applicant';
    
    protected static ?string $pluralModelLabel = 'M&E Applicants';
    
    protected static ?string $slug = 'me-applicants';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
    
    public static function getEloquentQuery(): Builder
    {
        // Filter for approved/completed applications with M&E and Training products
        return parent::getEloquentQuery()
            ->whereIn('current_step', ['approved', 'completed'])
            ->where('is_archived', false)
            ->where(function (Builder $query) {
                // Filter applications that include M&E and Training
                // This looks for products with M&E in the business name or product features
                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'selectedBusiness' ILIKE '%M&E%'")
                        ->orWhereRaw("form_data->>'selectedBusiness' ILIKE '%training%'")
                        ->orWhereRaw("form_data->>'selectedBusiness' ILIKE '%monitoring%'")
                        ->orWhereRaw("form_data->'selectedBusiness'->>'name' ILIKE '%M&E%'")
                        ->orWhereRaw("form_data->'selectedBusiness'->>'name' ILIKE '%training%'");
                } else {
                    $query->whereRaw("JSON_EXTRACT(form_data, '$.selectedBusiness') LIKE '%M&E%'")
                        ->orWhereRaw("JSON_EXTRACT(form_data, '$.selectedBusiness') LIKE '%training%'")
                        ->orWhereRaw("JSON_EXTRACT(form_data, '$.selectedBusiness') LIKE '%monitoring%'")
                        ->orWhereRaw("JSON_EXTRACT(form_data, '$.selectedBusiness.name') LIKE '%M&E%'")
                        ->orWhereRaw("JSON_EXTRACT(form_data, '$.selectedBusiness.name') LIKE '%training%'");
                }
            });
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Applicant Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('current_step')
                            ->label('Status')
                            ->disabled(),
                        Forms\Components\TextInput::make('channel')
                            ->label('Channel')
                            ->disabled(),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Application Data')
                    ->schema([
                        Forms\Components\ViewField::make('form_data')
                            ->label('Full Application Data')
                            ->view('filament.forms.components.application-data'),
                    ]),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('App #')
                    ->formatStateUsing(fn ($state) => 'ZB' . date('Y') . str_pad($state, 6, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('National ID')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function (Model $record) {
                        $firstName = data_get($record->form_data, 'formResponses.firstName', '');
                        $lastName = data_get($record->form_data, 'formResponses.lastName', '');
                        return trim($firstName . ' ' . $lastName) ?: 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        
                        if ($isPgsql) {
                            return $query->whereRaw("form_data->'formResponses'->>'firstName' ILIKE ?", ["%{$search}%"])
                                ->orWhereRaw("form_data->'formResponses'->>'lastName' ILIKE ?", ["%{$search}%"]);
                        }
                        
                        return $query->whereRaw("JSON_EXTRACT(form_data, '$.formResponses.firstName') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.lastName') LIKE ?", ["%{$search}%"]);
                    }),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->getStateUsing(function (Model $record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? $formData;
                        return $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellphone'] ?? 'N/A';
                    })
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('business_type')
                    ->label('Product/Business')
                    ->getStateUsing(fn (Model $record) => data_get($record->form_data, 'selectedBusiness.name', 'N/A'))
                    ->wrap()
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Model $record) => '$' . number_format(data_get($record->form_data, 'finalPrice', 0))),
                    
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['completed', 'approved']),
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('delivery_scheduled')
                    ->label('Delivery')
                    ->getStateUsing(function (Model $record) {
                        return $record->deliveryTracking()->exists();
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_step')
                    ->label('Status')
                    ->options([
                        'approved' => 'Approved',
                        'completed' => 'Completed',
                    ]),
                    
                Tables\Filters\Filter::make('has_delivery')
                    ->label('Has Delivery Tracking')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereHas('deliveryTracking')),
                    
                Tables\Filters\Filter::make('no_delivery')
                    ->label('Pending Delivery')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('deliveryTracking')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('schedule_delivery')
                    ->label('Schedule Delivery')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (Model $record) => !$record->deliveryTracking()->exists())
                    ->url(fn (Model $record) => route('filament.admin.resources.delivery-trackings.create', [
                        'application_state_id' => $record->id,
                    ])),
                    
                Action::make('view_delivery')
                    ->label('View Delivery')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (Model $record) => $record->deliveryTracking()->exists())
                    ->url(fn (Model $record) => route('filament.admin.resources.delivery-trackings.view', [
                        'record' => $record->deliveryTracking()->first()?->id,
                    ])),
                    
                Action::make('send_sms')
                    ->label('Send SMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->default('Dear {name}, your M&E and Training package is ready for delivery. We will contact you shortly to schedule delivery.'),
                    ])
                    ->action(function (array $data, Model $record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? $formData;
                        $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellphone'] ?? null;
                        $name = trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? '')) ?: 'Customer';
                        
                        if (!$phone) {
                            \Filament\Notifications\Notification::make()
                                ->title('No Phone Number')
                                ->body('Could not find a phone number for this applicant.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $message = str_replace('{name}', $name, $data['message']);
                        
                        try {
                            app(\App\Services\SMSService::class)->sendSMS($phone, $message);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('SMS Sent')
                                ->body("Message sent to {$phone}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('SMS Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_csv')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        $csv = "App #,National ID,Name,Phone,Product,Amount,Status,Applied Date\n";
                        
                        foreach ($records as $record) {
                            $formData = $record->form_data ?? [];
                            $formResponses = $formData['formResponses'] ?? $formData;
                            $firstName = $formResponses['firstName'] ?? '';
                            $lastName = $formResponses['lastName'] ?? '';
                            $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? '';
                            
                            $csv .= implode(',', [
                                'ZB' . date('Y') . str_pad($record->id, 6, '0', STR_PAD_LEFT),
                                $record->reference_code ?? 'N/A',
                                '"' . trim($firstName . ' ' . $lastName) . '"',
                                $phone,
                                '"' . data_get($formData, 'selectedBusiness.name', 'N/A') . '"',
                                data_get($formData, 'finalPrice', 0),
                                $record->current_step,
                                $record->created_at->format('Y-m-d'),
                            ]) . "\n";
                        }
                        
                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'me_applicants_' . now()->format('Y-m-d') . '.csv');
                    }),
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
            'index' => Pages\ListMEApplicants::route('/'),
            'view' => Pages\ViewMEApplicant::route('/{record}'),
        ];
    }
}
