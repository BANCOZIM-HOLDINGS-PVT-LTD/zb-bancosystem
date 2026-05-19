<?php

namespace App\Filament\Resources\BoosterItemResource\Pages;

use App\Filament\Resources\BoosterItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoosterItems extends ListRecords
{
    protected static string $resource = BoosterItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
