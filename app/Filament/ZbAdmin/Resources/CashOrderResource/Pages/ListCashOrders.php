<?php

namespace App\Filament\ZbAdmin\Resources\CashOrderResource\Pages;

use App\Filament\ZbAdmin\Resources\CashOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashOrders extends ListRecords
{
    protected static string $resource = CashOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions like bulk export could go here
        ];
    }
}
