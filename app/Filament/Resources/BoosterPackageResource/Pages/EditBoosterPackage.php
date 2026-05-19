<?php

namespace App\Filament\Resources\BoosterPackageResource\Pages;

use App\Filament\Resources\BoosterPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoosterPackage extends EditRecord
{
    protected static string $resource = BoosterPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
