<?php

namespace App\Filament\Resources\ApplicantDocumentsVerificationResource\Pages;

use App\Filament\Resources\ApplicantDocumentsVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApplicantDocumentsVerifications extends ListRecords
{
    protected static string $resource = ApplicantDocumentsVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // No creation allowed
        ];
    }
}
