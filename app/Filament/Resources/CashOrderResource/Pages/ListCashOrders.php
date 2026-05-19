<?php

namespace App\Filament\Resources\CashOrderResource\Pages;

use App\Filament\Resources\CashOrderResource;
use App\Models\ApplicationState;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCashOrders extends ListRecords
{
    protected static string $resource = CashOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $base = ApplicationState::whereHas('payments', fn (Builder $q) =>
            $q->where('provider', 'cash')
        );

        $pendingCount = (clone $base)
            ->whereHas('payments', fn (Builder $q) =>
                $q->where('provider', 'cash')->where('status', 'pending')
            )->count();

        $paidNotDispatched = (clone $base)
            ->whereHas('payments', fn (Builder $q) =>
                $q->where('provider', 'cash')->where('status', 'paid')
            )->doesntHave('delivery')->count();

        return [
            'all' => Tab::make('All Orders'),

            'pending' => Tab::make('Pending Payment')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('payments', fn ($q) =>
                    $q->where('provider', 'cash')->where('status', 'pending')
                ))
                ->badge($pendingCount > 0 ? $pendingCount : null)
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid — Awaiting Dispatch')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('payments', fn ($q) =>
                        $q->where('provider', 'cash')->where('status', 'paid')
                    )->doesntHave('delivery')
                )
                ->badge($paidNotDispatched > 0 ? $paidNotDispatched : null)
                ->badgeColor('success'),

            'dispatched' => Tab::make('Dispatched')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('delivery', fn ($q) =>
                    $q->whereIn('status', ['dispatched', 'in_transit', 'out_for_delivery'])
                )),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('delivery', fn ($q) =>
                    $q->where('status', 'delivered')
                )),
        ];
    }
}
