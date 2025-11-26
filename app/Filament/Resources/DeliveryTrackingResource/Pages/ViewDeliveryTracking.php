<?php

namespace App\Filament\Resources\DeliveryTrackingResource\Pages;

use App\Filament\Resources\DeliveryTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryTracking extends ViewRecord
{
    protected static string $resource = DeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
