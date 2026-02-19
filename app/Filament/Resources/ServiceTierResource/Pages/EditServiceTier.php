<?php

namespace App\Filament\Resources\ServiceTierResource\Pages;

use App\Filament\Resources\ServiceTierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceTier extends EditRecord
{
    protected static string $resource = ServiceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
