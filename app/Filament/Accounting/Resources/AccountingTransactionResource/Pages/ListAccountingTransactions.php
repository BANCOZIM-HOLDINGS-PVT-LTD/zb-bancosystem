<?php

namespace App\Filament\Accounting\Resources\AccountingTransactionResource\Pages;

use App\Filament\Accounting\Resources\AccountingTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountingTransactions extends ListRecords
{
    protected static string $resource = AccountingTransactionResource::class;
}
