<?php

namespace App\Filament\Resources\AgentApplicationResource\Pages;

use App\Filament\Resources\AgentApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgentApplication extends ViewRecord
{
    protected static string $resource = AgentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
