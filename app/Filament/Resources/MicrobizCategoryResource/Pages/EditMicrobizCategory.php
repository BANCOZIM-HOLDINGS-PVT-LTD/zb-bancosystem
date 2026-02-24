<?php

namespace App\Filament\Resources\MicrobizCategoryResource\Pages;

use App\Filament\Resources\MicrobizCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMicrobizCategory extends EditRecord
{
    protected static string $resource = MicrobizCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
