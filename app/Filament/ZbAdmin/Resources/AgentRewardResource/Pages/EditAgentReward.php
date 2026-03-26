<?php

namespace App\Filament\ZbAdmin\Resources\AgentRewardResource\Pages;

use App\Filament\ZbAdmin\Resources\AgentRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentReward extends EditRecord
{
    protected static string $resource = AgentRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
