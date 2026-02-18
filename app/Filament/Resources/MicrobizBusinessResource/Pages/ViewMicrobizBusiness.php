<?php

namespace App\Filament\Resources\MicrobizBusinessResource\Pages;

use App\Filament\Resources\MicrobizBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMicrobizBusiness extends ViewRecord
{
    protected static string $resource = MicrobizBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
