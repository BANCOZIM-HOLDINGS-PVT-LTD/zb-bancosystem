<?php

namespace App\Filament\Stores\Resources\DeliveryUpdateResource\Pages;

use App\Filament\Stores\Resources\DeliveryUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryUpdate extends EditRecord
{
    protected static string $resource = DeliveryUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
