<?php

namespace App\Filament\Resources\AccountOpeningResource\Pages;

use App\Filament\Resources\AccountOpeningResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountOpenings extends ListRecords
{
    protected static string $resource = AccountOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
