<?php

namespace App\Filament\Resources\InventoryManagementResource\Pages;

use App\Filament\Resources\InventoryManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryManagement extends EditRecord
{
    protected static string $resource = InventoryManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
