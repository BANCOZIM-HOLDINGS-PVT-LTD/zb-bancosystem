<?php

namespace App\Filament\Resources\CashPurchaseResource\Pages;

use App\Filament\Resources\CashPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCashPurchase extends ViewRecord
{
    protected static string $resource = CashPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
