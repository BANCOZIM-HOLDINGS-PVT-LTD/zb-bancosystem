<?php

namespace App\Filament\Resources\PersonalServiceResource\Pages;

use App\Filament\Resources\PersonalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonalServices extends ListRecords
{
    protected static string $resource = PersonalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Services are auto-created from approved applications
        ];
    }
}
