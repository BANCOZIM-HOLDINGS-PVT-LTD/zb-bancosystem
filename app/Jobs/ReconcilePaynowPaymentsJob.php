<?php

namespace App\Jobs;

use App\Events\PaymentReceived;
use App\Models\Payment;
use App\Services\PaynowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcilePaynowPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PaynowService $paynowService): void
    {
        Payment::where('provider', 'paynow')
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->whereNotNull('poll_url')
            ->chunkById(100, function ($payments) use ($paynowService) {
                foreach ($payments as $payment) {
                    $status = $paynowService->pollPaymentStatus($payment->poll_url);

                    if (!($status['success'] ?? false)) {
                        Log::warning('Paynow reconciliation status check failed', [
                            'payment_id' => $payment->id,
                            'reference' => $payment->reference,
                            'status' => $status,
                        ]);
                        continue;
                    }

                    if ($status['is_paid'] ?? false) {
                        $payment->markPaid($status['paynow_reference'] ?? null, ['reconciled_status' => $status]);
                        event(new PaymentReceived($payment));
                    } elseif (in_array($status['status'] ?? '', ['cancelled', 'failed', 'timeout', 'insufficient funds', 'insufficient_funds'])) {
                        $payment->markFailed($this->mapFailureStatus($status['status']), ['reconciled_status' => $status]);
                    }
                }
            });
    }

    private function mapFailureStatus(string $status): string
    {
        return match ($status) {
            'timeout' => Payment::STATUS_TIMEOUT,
            'insufficient funds', 'insufficient_funds' => Payment::STATUS_INSUFFICIENT_FUNDS,
            'cancelled' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };
    }
}
