<?php

namespace App\Filament\Resources\MicrobizPackageResource\Pages;

use App\Filament\Resources\MicrobizPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMicrobizPackages extends ListRecords
{
    protected static string $resource = MicrobizPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
