<?php

namespace App\Filament\Resources\MicrobizCategoryResource\Pages;

use App\Filament\Resources\MicrobizCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMicrobizCategory extends CreateRecord
{
    protected static string $resource = MicrobizCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['domain'] = 'microbiz';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
