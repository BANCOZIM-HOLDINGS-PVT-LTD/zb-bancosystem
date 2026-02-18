<?php

namespace App\Filament\Resources\MicrobizBusinessResource\Pages;

use App\Filament\Resources\MicrobizBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMicrobizBusiness extends EditRecord
{
    protected static string $resource = MicrobizBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
