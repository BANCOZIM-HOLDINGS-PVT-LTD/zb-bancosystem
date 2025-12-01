<?php

namespace App\Filament\Resources\ProductSeriesResource\Pages;

use App\Filament\Resources\ProductSeriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductSeries extends ListRecords
{
    protected static string $resource = ProductSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
