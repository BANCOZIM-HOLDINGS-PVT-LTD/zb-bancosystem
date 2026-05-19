<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashOrderResource\Pages;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CashOrderResource extends BaseResource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Cash Orders';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Cash Order';

    protected static ?string $pluralModelLabel = 'Cash Orders';

    protected static ?string $slug = 'cash-orders';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('payments', fn (Builder $q) => $q->where('provider', 'cash'))
            ->with(['payments', 'cashPayments', 'delivery']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        trim(
                            (data_get($record->form_data, 'formResponses.firstName') ?? '') . ' ' .
                            (data_get($record->form_data, 'formResponses.surname') ?? '')
                        ) ?: 'N/A'
                    ),

                Tables\Columns\TextColumn::make('product')
                    ->label('Product')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        data_get($record->form_data, 'business')
                        ?? data_get($record->form_data, 'selectedBusiness.name')
                        ?? data_get($record->form_data, 'category')
                        ?? 'N/A'
                    )
                    ->limit(30),

                Tables\Columns\TextColumn::make('payment_amount')
                    ->label('Amount')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        '$' . number_format(
                            $record->payments->where('provider', 'cash')->first()?->amount ?? 0, 2
                        )
                    ),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        $record->payments->where('provider', 'cash')->first()?->status ?? 'pending'
                    )
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'processing',
                        'success' => 'paid',
                        'danger'  => 'failed',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\BadgeColumn::make('delivery_status')
                    ->label('Delivery')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        $record->delivery?->status ?? 'not_dispatched'
                    )
                    ->colors([
                        'gray'    => fn ($state) => in_array($state, ['not_dispatched', 'pending']),
                        'blue'    => 'processing',
                        'indigo'  => 'dispatched',
                        'purple'  => 'in_transit',
                        'warning' => 'out_for_delivery',
                        'success' => 'delivered',
                        'danger'  => fn ($state) => in_array($state, ['failed', 'returned']),
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'not_dispatched'   => 'Not Dispatched',
                        'pending'          => 'Pending',
                        'processing'       => 'Processing',
                        'dispatched'       => 'Dispatched',
                        'in_transit'       => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered'        => 'Delivered',
                        'failed'           => 'Failed',
                        'returned'         => 'Returned',
                        default            => ucfirst(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('cashier_ref')
                    ->label('Cashier Ref')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        $record->cashPayments->first()?->cashier_reference ?? '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->getStateUsing(fn (ApplicationState $record): string =>
                        $record->payments->where('provider', 'cash')->first()?->paid_at?->format('d M Y H:i') ?? '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ordered')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending'   => 'Pending',
                        'paid'      => 'Paid',
                        'failed'    => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('payments', fn ($q) => $q
                                ->where('provider', 'cash')
                                ->where('status', $data['value'])
                            );
                        }
                    }),

                Tables\Filters\SelectFilter::make('delivery_status')
                    ->label('Delivery Status')
                    ->options([
                        'not_dispatched'   => 'Not Dispatched',
                        'pending'          => 'Pending',
                        'dispatched'       => 'Dispatched',
                        'in_transit'       => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered'        => 'Delivered',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            if ($data['value'] === 'not_dispatched') {
                                $query->doesntHave('delivery');
                            } else {
                                $query->whereHas('delivery', fn ($q) => $q->where('status', $data['value']));
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download_invoice')
                    ->label('Invoice PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (ApplicationState $record): bool =>
                        $record->payments->where('provider', 'cash')->first()?->status === 'paid'
                    )
                    ->action(function (ApplicationState $record) {
                        try {
                            $pdfGenerator = app(PDFGeneratorService::class);
                            $pdfPath = $pdfGenerator->generateReceiptPDF($record);
                            $filename = "invoice_{$record->reference_code}.pdf";

                            return response()->download(
                                storage_path("app/public/{$pdfPath}"),
                                $filename,
                                ['Content-Type' => 'application/pdf']
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Invoice generation failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_code')
                            ->label('Reference Code')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('session_id')
                            ->label('Session ID (Delivery Tracking)')
                            ->copyable()
                            ->helperText('Client uses this to track their delivery'),
                        Infolists\Components\TextEntry::make('client_name')
                            ->label('Client Name')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                trim(
                                    (data_get($record->form_data, 'formResponses.firstName') ?? '') . ' ' .
                                    (data_get($record->form_data, 'formResponses.surname') ?? '')
                                ) ?: 'N/A'
                            ),
                        Infolists\Components\TextEntry::make('client_phone')
                            ->label('Phone')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                data_get($record->form_data, 'formResponses.mobile') ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('client_id')
                            ->label('National ID')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                data_get($record->form_data, 'formResponses.nationalIdNumber') ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('product')
                            ->label('Product')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                data_get($record->form_data, 'business')
                                ?? data_get($record->form_data, 'selectedBusiness.name')
                                ?? data_get($record->form_data, 'category')
                                ?? 'N/A'
                            ),
                    ])->columns(3),

                Infolists\Components\Section::make('Payment Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->payments->where('provider', 'cash')->first()?->status ?? 'pending'
                            )
                            ->color(fn ($state) => match ($state) {
                                'paid'    => 'success',
                                'pending' => 'gray',
                                'failed'  => 'danger',
                                default   => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('payment_amount')
                            ->label('Amount')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                '$' . number_format(
                                    $record->payments->where('provider', 'cash')->first()?->amount ?? 0, 2
                                )
                            ),
                        Infolists\Components\TextEntry::make('cashier_reference')
                            ->label('Cashier Reference')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->cashPayments->first()?->cashier_reference ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('receipt_number')
                            ->label('Receipt Number')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->payments->where('provider', 'cash')->first()?->receipt_number ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('Paid At')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->payments->where('provider', 'cash')->first()?->paid_at?->format('d M Y H:i') ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('verified_by')
                            ->label('Verified By')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->cashPayments->first()?->verifier?->name ?? '—'
                            ),
                    ])->columns(3),

                Infolists\Components\Section::make('Delivery Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('delivery_status')
                            ->label('Status')
                            ->badge()
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->delivery?->status ?? 'not_dispatched'
                            )
                            ->color(fn ($state) => match ($state) {
                                'delivered'        => 'success',
                                'dispatched'       => 'indigo',
                                'in_transit'       => 'purple',
                                'out_for_delivery' => 'warning',
                                'not_dispatched'   => 'gray',
                                'failed'           => 'danger',
                                default            => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'not_dispatched'   => 'Not Dispatched Yet',
                                'pending'          => 'Pending',
                                'processing'       => 'Processing',
                                'dispatched'       => 'Dispatched',
                                'in_transit'       => 'In Transit',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered'        => 'Delivered',
                                default            => ucfirst(str_replace('_', ' ', $state)),
                            }),
                        Infolists\Components\TextEntry::make('courier_type')
                            ->label('Courier')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->delivery?->courier_type ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('delivery_depot')
                            ->label('Depot / Address')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->delivery?->delivery_depot ?? $record->delivery?->delivery_address ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('dispatched_at')
                            ->label('Dispatched At')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->delivery?->dispatched_at?->format('d M Y H:i') ?? '—'
                            ),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Delivered At')
                            ->getStateUsing(fn (ApplicationState $record): string =>
                                $record->delivery?->delivered_at?->format('d M Y H:i') ?? '—'
                            ),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashOrders::route('/'),
            'view'  => Pages\ViewCashOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getEloquentQuery()
                ->whereHas('payments', fn ($q) => $q->where('provider', 'cash')->where('status', 'pending'))
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
