<?php

namespace App\Filament\Resources\CashPurchaseResource\Pages;

use App\Filament\Resources\CashPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashPurchase extends EditRecord
{
    protected static string $resource = CashPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Cash purchase updated successfully';
    }
}
