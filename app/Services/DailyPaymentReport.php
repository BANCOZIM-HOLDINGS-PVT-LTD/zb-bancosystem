<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyPaymentReport
{
    public function paymentsForDate(CarbonInterface|string|null $date = null): Collection
    {
        $date = $date ? Carbon::parse($date) : now();

        return Payment::query()
            ->with('applicationState')
            ->whereDate('created_at', $date->toDateString())
            ->orderBy('created_at')
            ->get();
    }

    public function summary(CarbonInterface|string|null $date = null): array
    {
        $payments = $this->paymentsForDate($date);

        return [
            'date' => ($date ? Carbon::parse($date) : now())->toDateString(),
            'total_count' => $payments->count(),
            'paid_count' => $payments->where('status', Payment::STATUS_PAID)->count(),
            'paid_amount' => (float) $payments->where('status', Payment::STATUS_PAID)->sum('amount'),
            'pending_count' => $payments->where('status', Payment::STATUS_PENDING)->count(),
            'failed_count' => $payments->whereIn('status', [
                Payment::STATUS_FAILED,
                Payment::STATUS_CANCELLED,
                Payment::STATUS_TIMEOUT,
                Payment::STATUS_INSUFFICIENT_FUNDS,
            ])->count(),
        ];
    }

    public function toCsv(CarbonInterface|string|null $date = null): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'reference',
            'provider',
            'method',
            'status',
            'amount',
            'currency',
            'receipt_number',
            'application_reference',
            'provider_reference',
            'created_at',
            'paid_at',
        ]);

        foreach ($this->paymentsForDate($date) as $payment) {
            fputcsv($handle, [
                $payment->reference,
                $payment->provider,
                $payment->method,
                $payment->status,
                $payment->amount,
                $payment->currency,
                $payment->receipt_number,
                $payment->applicationState?->reference_code,
                $payment->provider_reference,
                $payment->created_at?->toDateTimeString(),
                $payment->paid_at?->toDateTimeString(),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
