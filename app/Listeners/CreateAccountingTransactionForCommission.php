<?php

namespace App\Listeners;

use App\Events\CommissionCalculated;
use App\Models\AccountingTransaction;

class CreateAccountingTransactionForCommission
{
    public function handle(CommissionCalculated $event): void
    {
        $commission = $event->commission;

        AccountingTransaction::updateOrCreate(
            ['reference' => 'COM-' . $commission->id],
            [
                'type' => 'commission',
                'source' => 'commission',
                'amount' => $commission->commission_amount ?? $commission->amount ?? 0,
                'application_state_id' => $commission->application_id ?? null,
                'metadata' => ['commission_id' => $commission->id],
            ]
        );
    }
}
