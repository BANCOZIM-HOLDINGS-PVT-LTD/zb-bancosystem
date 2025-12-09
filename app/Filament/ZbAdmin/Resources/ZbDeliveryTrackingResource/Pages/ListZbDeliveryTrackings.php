<?php

namespace App\Filament\ZbAdmin\Resources\ZbDeliveryTrackingResource\Pages;

use App\Filament\ZbAdmin\Resources\ZbDeliveryTrackingResource;
use Filament\Resources\Pages\ListRecords;

class ListZbDeliveryTrackings extends ListRecords
{
    protected static string $resource = ZbDeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
