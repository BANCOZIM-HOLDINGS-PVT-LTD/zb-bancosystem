<?php

namespace App\Filament\Resources\PaidDepositCreditsResource\Pages;

use App\Filament\Resources\PaidDepositCreditsResource;
use Filament\Resources\Pages\ListRecords;

class ListPaidDepositCredits extends ListRecords
{
    protected static string $resource = PaidDepositCreditsResource::class;

    protected function getActions(): array
    {
        return [];
    }
}
