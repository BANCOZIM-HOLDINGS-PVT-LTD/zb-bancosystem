<?php

namespace App\Filament\Stores\Resources\DeliveryUpdateResource\Pages;

use App\Filament\Stores\Resources\DeliveryUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryUpdates extends ListRecords
{
    protected static string $resource = DeliveryUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
