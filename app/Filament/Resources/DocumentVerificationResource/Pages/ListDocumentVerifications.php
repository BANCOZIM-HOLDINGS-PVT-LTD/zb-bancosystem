<?php

namespace App\Filament\Resources\DocumentVerificationResource\Pages;

use App\Filament\Resources\DocumentVerificationResource;
use Filament\Resources\Pages\ListRecords;

class ListDocumentVerifications extends ListRecords
{
    protected static string $resource = DocumentVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
