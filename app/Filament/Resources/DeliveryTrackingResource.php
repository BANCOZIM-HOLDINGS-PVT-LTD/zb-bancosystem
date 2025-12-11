<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryTrackingResource\Pages;
use App\Models\DeliveryTracking;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;

class DeliveryTrackingResource extends BaseResource
{
    protected static ?string $model = DeliveryTracking::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Delivery Management';

    protected static ?string $navigationGroup = null; // Show in main menu without group

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->description('Client information and reference')
                    ->schema([
                        Forms\Components\Select::make('application_state_id')
                            ->label('Application')
                            ->relationship(
                                'applicationState',
                                'session_id',
                                fn (Builder $query) => $query->whereIn('current_step', ['approved', 'completed'])
                            )
                            ->getOptionLabelFromRecordUsing(fn (ApplicationState $record) =>
                                "{$record->reference_code} - " .
                                (data_get($record->form_data, 'formResponses.firstName') ?? '') . ' ' .
                                (data_get($record->form_data, 'formResponses.surname') ?? 'N/A')
                            )
                            ->searchable(['session_id', 'reference_code'])
                            ->required()
                            ->reactive()
                            ->disabled(fn ($record) => $record !== null) // Disable if editing existing record
                            ->dehydrated(fn ($record) => $record === null) // Only hydrate if creating
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $app = ApplicationState::find($state);
                                    if ($app) {
                                        $formData = $app->form_data ?? [];
                                        $formResponses = $formData['formResponses'] ?? [];
                                        $deliverySelection = $formData['deliverySelection'] ?? [];

                                        // Set client name
                                        $clientName = trim(
                                            ($formResponses['firstName'] ?? '') . ' ' .
                                            ($formResponses['surname'] ?? '')
                                        );
                                        $set('recipient_name', $clientName);
                                        $set('recipient_phone', $formResponses['mobile'] ?? $formResponses['cellNumber'] ?? '');
                                        $set('client_national_id', $formResponses['nationalIdNumber'] ?? $formResponses['idNumber'] ?? '');

                                        // Set product information
                                        $product = $formData['business'] ?? $formData['category'] ?? 'N/A';
                                        $set('product_type', $product);

                                        // Set delivery depot from deliverySelection
                                        $depot = '';
                                        if (!empty($deliverySelection['city'])) {
                                            $depot = $deliverySelection['city'] . ' (' . ($deliverySelection['agent'] ?? 'Swift') . ')';
                                        } elseif (!empty($deliverySelection['depot'])) {
                                            $depot = $deliverySelection['depot'];
                                        }
                                        $set('delivery_depot', $depot);

                                        // Auto-assign courier based on deliverySelection agent
                                        $agent = $deliverySelection['agent'] ?? 'Swift';
                                        $set('courier_type', $agent);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('client_info')
                            ->label('Client Name')
                            ->content(fn (Get $get) => $get('recipient_name') ?: 'Select an application first')
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('ref_number')
                            ->label('Reference (National ID)')
                            ->content(fn (Get $get) => $get('client_national_id') ?: 'N/A')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recipient Information')
                    ->description('Auto-populated from application - Not editable')
                    ->schema([
                        Forms\Components\TextInput::make('recipient_name')
                            ->label('Recipient Name (Client)')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('recipient_phone')
                            ->label('Phone Number')
                            ->disabled()
                            ->dehydrated()
                            ->tel()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('client_national_id')
                            ->label('National ID')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('product_type')
                            ->label('Product Applied For')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('delivery_depot')
                            ->label('Delivery Depot')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Assigned To')
                    ->description('Select courier/delivery service')
                    ->schema([
                        Forms\Components\Select::make('courier_type')
                            ->label('Delivery Service')
                            ->options([
                                'Swift' => 'Swift',
                                'Gain Cash & Carry' => 'Gain Cash & Carry',
                                'Bancozim' => 'Bancozim',
                                'Bus Courier' => 'Bus Courier',
                                'Zim Post Office' => 'Zim Post Office',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpanFull(),

                        // Swift Details
                        Forms\Components\TextInput::make('swift_tracking_number')
                            ->label('Swift Tracking Number')
                            ->placeholder('Enter Swift tracking number')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Swift')
                            ->required(fn (Get $get) => $get('courier_type') === 'Swift')
                            ->columnSpanFull(),

                        // Post Office Details
                        Forms\Components\TextInput::make('post_office_tracking_number')
                            ->label('Serial Number')
                            ->placeholder('Enter Serial Number')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Zim Post Office')
                            ->required(fn (Get $get) => $get('courier_type') === 'Zim Post Office')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('post_office_vehicle_registration')
                            ->label('Truck Reg Number')
                            ->placeholder('Enter vehicle registration')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Zim Post Office')
                            ->required(fn (Get $get) => $get('courier_type') === 'Zim Post Office')
                            ->columnSpanFull(),

                        // Gain Outlet Details
                        Forms\Components\TextInput::make('gain_voucher_number')
                            ->label('Gain Voucher Number')
                            ->placeholder('Enter Gain voucher number')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Gain Cash & Carry')
                            ->required(fn (Get $get) => $get('courier_type') === 'Gain Cash & Carry')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('gain_depot_location')
                            ->label('Gain Depot Location')
                            ->placeholder('Depot name and location')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Gain Outlet')
                            ->columnSpanFull(),

                        // Bus Courier Details
                        Forms\Components\TextInput::make('bus_registration_number')
                            ->label('Bus Registration Number')
                            ->placeholder('e.g., ABC-1234')
                            ->maxLength(50)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->required(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('bus_driver_name')
                            ->label('Bus Driver Name')
                            ->placeholder('Driver full name')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->required(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('bus_driver_phone')
                            ->label('Bus Driver Phone')
                            ->placeholder('Driver contact number')
                            ->tel()
                            ->maxLength(50)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->required(fn (Get $get) => $get('courier_type') === 'Bus Courier')
                            ->columnSpan(1),

                        // Bancozim Details
                        Forms\Components\TextInput::make('bancozim_agent_name')
                            ->label('Bancozim Agent Name')
                            ->placeholder('Agent full name')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bancozim')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('bancozim_agent_phone')
                            ->label('Bancozim Agent Phone')
                            ->placeholder('Agent contact number')
                            ->tel()
                            ->maxLength(50)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bancozim')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('bancozim_location')
                            ->label('Bancozim Location')
                            ->placeholder('Collection point location')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('courier_type') === 'Bancozim')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Delivery Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Delivery Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'dispatched' => 'Dispatched',
                                'in_transit' => 'In Transit',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered' => 'Delivered',
                                'failed' => 'Failed',
                                'returned' => 'Returned',
                            ])
                            ->required()
                            ->default('pending')
                            ->reactive()
                            ->columnSpanFull()
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        if (in_array($value, ['dispatched', 'in_transit', 'out_for_delivery'])) {
                                            $courierType = $get('courier_type');

                                            // Validate Swift
                                            if ($courierType === 'Swift' && empty($get('swift_tracking_number'))) {
                                                $fail('Swift tracking number is required before dispatching.');
                                            }

                                            // Validate Gain Outlet
                                            if ($courierType === 'Gain Outlet' && empty($get('gain_voucher_number'))) {
                                                $fail('Gain voucher number is required before dispatching.');
                                            }

                                            // Validate Bus Courier
                                            if ($courierType === 'Bus Courier') {
                                                if (empty($get('bus_registration_number'))) {
                                                    $fail('Bus registration number is required before dispatching.');
                                                }
                                                if (empty($get('bus_driver_name'))) {
                                                    $fail('Bus driver name is required before dispatching.');
                                                }
                                                if (empty($get('bus_driver_phone'))) {
                                                    $fail('Bus driver phone is required before dispatching.');
                                                }
                                            }
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Admin/Staff Assigned')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Delivery Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('dispatched_at')
                            ->label('Dispatched At'),

                        Forms\Components\DateTimePicker::make('estimated_delivery_date')
                            ->label('Estimated Delivery Date'),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Delivered At')
                            ->visible(fn (Get $get) => $get('status') === 'delivered'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notes & Documentation')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('Internal notes about this delivery')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->placeholder('Notes from courier or delivery person')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('delivery_note')
                            ->label('Upload or Scan Delivery Note')
                            ->image()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('delivery-notes')
                            ->visibility('private')
                            ->helperText('Upload scanned delivery note or proof document')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('delivery_photo')
                            ->label('Proof of Delivery Photo')
                            ->image()
                            ->directory('delivery-photos')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicationState.reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),

                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_national_id')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product_type')
                    ->label('Product')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('courier_type')
                    ->label('Courier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Swift' => 'success',
                        'Gain Outlet' => 'warning',
                        'Bancozim' => 'info',
                        'Bus Courier' => 'primary',
                        default => 'secondary',
                    })
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'primary' => 'processing',
                        'info' => 'dispatched',
                        'warning' => fn ($state) => in_array($state, ['in_transit', 'out_for_delivery']),
                        'success' => 'delivered',
                        'danger' => fn ($state) => in_array($state, ['failed', 'returned']),
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('swift_tracking_number')
                    ->label('Swift Tracking')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('gain_voucher_number')
                    ->label('Gain Voucher')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('bus_registration_number')
                    ->label('Bus Reg')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('delivery_depot')
                    ->label('Depot')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('recipient_phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('dispatched_at')
                    ->label('Dispatched')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estimated_delivery_date')
                    ->label('Est. Delivery')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned Staff')
                    ->toggleable()
                    ->placeholder('Unassigned'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'dispatched' => 'Dispatched',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'returned' => 'Returned',
                    ]),

                Tables\Filters\SelectFilter::make('courier_type')
                    ->label('Courier Service')
                    ->options([
                        'Swift' => 'Swift',
                        'Gain Outlet' => 'Gain Outlet',
                        'Bancozim' => 'Bancozim',
                        'Bus Courier' => 'Bus Courier',
                    ]),

                Tables\Filters\Filter::make('dispatched')
                    ->query(fn (Builder $query) => $query->whereNotNull('dispatched_at'))
                    ->label('Dispatched Only'),

                Tables\Filters\Filter::make('pending_dispatch')
                    ->query(fn (Builder $query) => $query->whereNull('dispatched_at')->where('status', '!=', 'delivered'))
                    ->label('Pending Dispatch'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('New Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'dispatched' => 'Dispatched',
                                'in_transit' => 'In Transit',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered' => 'Delivered',
                                'failed' => 'Failed',
                                'returned' => 'Returned',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Status Update Notes')
                            ->rows(3),
                    ])
                    ->action(function (DeliveryTracking $record, array $data) {
                        // Validate required fields before dispatching
                        if (in_array($data['status'], ['dispatched', 'in_transit', 'out_for_delivery'])) {
                            $errors = [];

                            if ($record->courier_type === 'Swift' && empty($record->swift_tracking_number)) {
                                $errors[] = 'Swift tracking number is required before dispatching.';
                            }

                            if ($record->courier_type === 'Gain Outlet' && empty($record->gain_voucher_number)) {
                                $errors[] = 'Gain voucher number is required before dispatching.';
                            }

                            if ($record->courier_type === 'Bus Courier') {
                                if (empty($record->bus_registration_number)) {
                                    $errors[] = 'Bus registration number is required before dispatching.';
                                }
                                if (empty($record->bus_driver_name)) {
                                    $errors[] = 'Bus driver name is required before dispatching.';
                                }
                                if (empty($record->bus_driver_phone)) {
                                    $errors[] = 'Bus driver phone is required before dispatching.';
                                }
                            }

                            if (!empty($errors)) {
                                throw new \Exception(implode(' ', $errors));
                            }
                        }

                        $record->addStatusUpdate($data['status'], $data['notes'] ?? null);

                        if ($data['status'] === 'delivered') {
                            $record->update(['delivered_at' => now()]);
                        } elseif ($data['status'] === 'dispatched') {
                            $record->update(['dispatched_at' => now()]);
                        }
                    })
                    ->successNotificationTitle('Status updated successfully'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make('forceDelete')
                        ->label('Delete'),
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
            'index' => Pages\ListDeliveryTrackings::route('/'),
            'edit' => Pages\EditDeliveryTracking::route('/{record}/edit'),
            'view' => Pages\ViewDeliveryTracking::route('/{record}'),
        ];
    }
}