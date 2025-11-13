<?php

namespace App\Filament\Resources\InventoryManagementResource\Pages;

use App\Filament\Resources\InventoryManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryManagement extends ViewRecord
{
    protected static string $resource = InventoryManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
