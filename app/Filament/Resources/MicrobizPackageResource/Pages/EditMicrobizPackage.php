<?php

namespace App\Filament\Resources\MicrobizPackageResource\Pages;

use App\Filament\Resources\MicrobizPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMicrobizPackage extends EditRecord
{
    protected static string $resource = MicrobizPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
