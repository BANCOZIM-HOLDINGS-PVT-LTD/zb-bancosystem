<?php

namespace App\Filament\Accounting\Resources\PayrollResource\Pages;

use App\Filament\Accounting\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollEntries extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
