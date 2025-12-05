<?php

namespace App\Filament\Resources\AgentApplicationResource\Pages;

use App\Filament\Resources\AgentApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentApplication extends EditRecord
{
    protected static string $resource = AgentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
