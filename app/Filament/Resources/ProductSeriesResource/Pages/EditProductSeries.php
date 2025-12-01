<?php

namespace App\Filament\Resources\ProductSeriesResource\Pages;

use App\Filament\Resources\ProductSeriesResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditProductSeries extends EditRecord
{
    protected static string $resource = ProductSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
