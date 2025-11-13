<?php

namespace App\Filament\Resources\LoanTermsResource\Pages;

use App\Filament\Resources\LoanTermsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanTerms extends EditRecord
{
    protected static string $resource = LoanTermsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
