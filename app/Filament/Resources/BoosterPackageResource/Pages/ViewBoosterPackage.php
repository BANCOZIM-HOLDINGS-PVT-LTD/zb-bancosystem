<?php

namespace App\Filament\Resources\BoosterPackageResource\Pages;

use App\Filament\Resources\BoosterPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBoosterPackage extends ViewRecord
{
    protected static string $resource = BoosterPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
