<?php

namespace App\Filament\Resources\ServiceTierResource\Pages;

use App\Filament\Resources\ServiceTierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceTiers extends ListRecords
{
    protected static string $resource = ServiceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
