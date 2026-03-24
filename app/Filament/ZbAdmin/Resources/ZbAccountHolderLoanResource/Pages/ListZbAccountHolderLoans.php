<?php

namespace App\Filament\ZbAdmin\Resources\ZbAccountHolderLoanResource\Pages;

use App\Filament\ZbAdmin\Resources\ZbAccountHolderLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZbAccountHolderLoans extends ListRecords
{
    protected static string $resource = ZbAccountHolderLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed
        ];
    }
}
