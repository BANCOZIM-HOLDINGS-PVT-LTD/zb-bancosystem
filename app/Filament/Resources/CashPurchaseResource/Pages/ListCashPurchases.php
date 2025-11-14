<?php

namespace App\Filament\Resources\CashPurchaseResource\Pages;

use App\Filament\Resources\CashPurchaseResource;
use Filament\Resources\Pages\ListRecords;

class ListCashPurchases extends ListRecords
{
    protected static string $resource = CashPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - cash purchases are auto-populated from frontend
        ];
    }
}
