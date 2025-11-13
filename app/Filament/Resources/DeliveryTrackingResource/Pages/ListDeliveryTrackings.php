<?php

namespace App\Filament\Resources\DeliveryTrackingResource\Pages;

use App\Filament\Resources\DeliveryTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryTrackings extends ListRecords
{
    protected static string $resource = DeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
