<?php

namespace App\Filament\Resources\InventoryManagementResource\Pages;

use App\Filament\Resources\InventoryManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryManagement extends ListRecords
{
    protected static string $resource = InventoryManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
