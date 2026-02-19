<?php

namespace App\Filament\Resources\ServiceTierResource\Pages;

use App\Filament\Resources\ServiceTierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceTier extends ViewRecord
{
    protected static string $resource = ServiceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
