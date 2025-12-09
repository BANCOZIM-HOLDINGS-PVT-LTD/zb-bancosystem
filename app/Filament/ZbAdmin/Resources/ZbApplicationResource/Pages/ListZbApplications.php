<?php

namespace App\Filament\ZbAdmin\Resources\ZbApplicationResource\Pages;

use App\Filament\ZbAdmin\Resources\ZbApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListZbApplications extends ListRecords
{
    protected static string $resource = ZbApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
