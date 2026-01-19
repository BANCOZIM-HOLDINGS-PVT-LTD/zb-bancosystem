<?php

namespace App\Filament\Hr\Resources\AgentManagementResource\Pages;

use App\Filament\Hr\Resources\AgentManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentManagement extends EditRecord
{
    protected static string $resource = AgentManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
