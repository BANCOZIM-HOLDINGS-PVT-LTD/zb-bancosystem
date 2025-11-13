<?php

namespace App\Filament\Resources\DeliveryTrackingResource\Pages;

use App\Filament\Resources\DeliveryTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryTracking extends EditRecord
{
    protected static string $resource = DeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
