<?php

namespace App\Filament\Resources\MicrobizPackageResource\Pages;

use App\Filament\Resources\MicrobizPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMicrobizPackage extends ViewRecord
{
    protected static string $resource = MicrobizPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
