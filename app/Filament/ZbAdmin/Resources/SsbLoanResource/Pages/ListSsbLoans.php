<?php

namespace App\Filament\ZbAdmin\Resources\SsbLoanResource\Pages;

use App\Filament\ZbAdmin\Resources\SsbLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListSsbLoans extends ListRecords
{
    protected static string $resource = SsbLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for view-only resource
        ];
    }
}
