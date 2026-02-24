<?php

namespace App\Filament\Resources\MicrobizCategoryResource\Pages;

use App\Filament\Resources\MicrobizCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMicrobizCategories extends ListRecords
{
    protected static string $resource = MicrobizCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
