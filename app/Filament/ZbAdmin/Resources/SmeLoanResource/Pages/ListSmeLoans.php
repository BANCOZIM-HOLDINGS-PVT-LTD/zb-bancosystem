<?php

namespace App\Filament\ZbAdmin\Resources\SmeLoanResource\Pages;

use App\Filament\ZbAdmin\Resources\SmeLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListSmeLoans extends ListRecords
{
    protected static string $resource = SmeLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for view-only resource
        ];
    }
}
