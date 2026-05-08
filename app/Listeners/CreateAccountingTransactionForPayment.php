<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\AccountingTransaction;

class CreateAccountingTransactionForPayment
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        AccountingTransaction::updateOrCreate(
            ['reference' => 'PAY-' . $payment->reference],
            [
                'type' => 'income',
                'source' => $payment->provider,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'application_state_id' => $payment->application_state_id,
                'payment_id' => $payment->id,
                'metadata' => [
                    'method' => $payment->method,
                    'receipt_number' => $payment->receipt_number,
                    'provider_reference' => $payment->provider_reference,
                ],
            ]
        );
    }
}
