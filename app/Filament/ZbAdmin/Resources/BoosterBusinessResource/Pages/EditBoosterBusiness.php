<?php

namespace App\Filament\ZbAdmin\Resources\BoosterBusinessResource\Pages;

use App\Filament\ZbAdmin\Resources\BoosterBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoosterBusiness extends EditRecord
{
    protected static string $resource = BoosterBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
