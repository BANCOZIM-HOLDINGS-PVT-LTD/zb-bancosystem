<?php

namespace App\Filament\ZbAdmin\Resources\AccountOpeningResource\Pages;

use App\Filament\ZbAdmin\Resources\AccountOpeningResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountOpenings extends ListRecords
{
    protected static string $resource = AccountOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - account openings come from frontend
        ];
    }
}
