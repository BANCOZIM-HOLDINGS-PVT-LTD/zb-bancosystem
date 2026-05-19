<?php

namespace App\Filament\Resources\BoosterCategoryResource\Pages;

use App\Filament\Resources\BoosterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoosterCategories extends ListRecords
{
    protected static string $resource = BoosterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
