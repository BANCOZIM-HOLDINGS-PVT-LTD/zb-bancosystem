<?php

namespace App\Filament\Agent\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\CashPurchase;
use Illuminate\Database\Eloquent\Builder;

class CashPurchaseReferralsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $agent = Auth::guard('agent')->user();

        return $table
            ->heading('Cash Purchase Referrals')
            ->description('Clients who made cash purchases through your referral link')
            ->query(
                CashPurchase::query()
                    ->where('agent_id', $agent?->id)
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Purchase #')
                    ->copyable()
                    ->copyMessage('Purchase number copied')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->product_name),

                Tables\Columns\TextColumn::make('purchase_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'personal' => 'success',
                        'microbiz' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Delivery')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'collected' => 'success',
                        'ready_for_collection' => 'info',
                        'dispatched' => 'primary',
                        'processing' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->getStateUsing(function (CashPurchase $record) {
                        // Calculate commission from related commission model or inline
                        $commission = $record->commissions()->first();
                        return $commission ? $commission->amount : 0;
                    })
                    ->money('USD')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Delivery Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'dispatched' => 'Dispatched',
                        'ready_for_collection' => 'Ready for Collection',
                        'collected' => 'Collected',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('purchase_type')
                    ->label('Purchase Type')
                    ->options([
                        'personal' => 'Personal',
                        'microbiz' => 'MicroBiz',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No Cash Purchase Referrals Yet')
            ->emptyStateDescription('When clients make cash purchases using your referral link, they will appear here.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->paginated([10, 25, 50]);
    }
}
