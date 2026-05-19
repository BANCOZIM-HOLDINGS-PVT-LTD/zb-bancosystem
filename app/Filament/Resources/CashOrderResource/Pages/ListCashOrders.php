<?php

namespace App\Filament\Resources\CashOrderResource\Pages;

use App\Filament\Resources\CashOrderResource;
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
        return [
            'all' => Tab::make('All Orders'),

            'pending' => Tab::make('Pending Payment')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('payments', fn ($q) =>
                    $q->where('provider', 'cash')->where('status', 'pending')
                ))
                ->badge(fn () => CashOrderResource::getEloquentQuery()
                    ->whereHas('payments', fn ($q) => $q->where('provider', 'cash')->where('status', 'pending'))
                    ->count()
                )
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('payments', fn ($q) =>
                    $q->where('provider', 'cash')->where('status', 'paid')
                ))
                ->badge(fn () => CashOrderResource::getEloquentQuery()
                    ->whereHas('payments', fn ($q) => $q->where('provider', 'cash')->where('status', 'paid'))
                    ->doesntHave('delivery')
                    ->count()
                )
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
