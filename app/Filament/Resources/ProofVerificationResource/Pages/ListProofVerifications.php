<?php

namespace App\Filament\Resources\ProofVerificationResource\Pages;

use App\Filament\Resources\ProofVerificationResource;
use Filament\Resources\Pages\ListRecords;

class ListProofVerifications extends ListRecords
{
    protected static string $resource = ProofVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
