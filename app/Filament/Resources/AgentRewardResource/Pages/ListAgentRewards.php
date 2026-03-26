<?php

namespace App\Filament\Resources\AgentRewardResource\Pages;

use App\Filament\Resources\AgentRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentRewards extends ListRecords
{
    protected static string $resource = AgentRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
