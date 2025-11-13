<?php

namespace App\Filament\Resources\LoanTermsResource\Pages;

use App\Filament\Resources\LoanTermsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanTerms extends ListRecords
{
    protected static string $resource = LoanTermsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
