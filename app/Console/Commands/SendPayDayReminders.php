<?php

namespace App\Console\Commands;

use App\Models\ApplicationState;
use App\Jobs\SendSmsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPayDayReminders extends Command
{
    protected $signature = 'payday:send-reminders {--dry-run : Show what would be sent without actually sending}';
    protected $description = 'Send SMS reminders to approved clients 4 days before their pay day';

    /**
     * Map payDayRange to reference day of the month
     */
    private function getReferenceDay(string $range): int
    {
        return match ($range) {
            'week1' => 7,
            'week2' => 15,
            'week3' => 21,
            'week4' => 28,
            default => 0,
        };
    }

    public function handle(): int
    {
        $today = now();
        $dayOfMonth = (int) $today->format('j');
        $daysInMonth = (int) $today->format('t');

        $this->info("Running Pay Day SMS Reminders for {$today->format('Y-m-d')}");
        $this->info("Day of month: {$dayOfMonth}, Days in month: {$daysInMonth}");

        // Determine which payDayRange groups should receive reminders today
        // We send 4 days before their reference pay day
        $targetRanges = [];
        $ranges = ['week1', 'week2', 'week3', 'week4'];

        foreach ($ranges as $range) {
            $refDay = $this->getReferenceDay($range);

            // For week4 (28th), if the month has fewer than 28 days, use last day
            if ($range === 'week4') {
                $refDay = min($refDay, $daysInMonth);
            }

            $reminderDay = $refDay - 4;

            // Handle wrap-around for early-month pay days
            if ($reminderDay <= 0) {
                // The reminder day falls in the previous month
                $prevMonthDays = (int) $today->copy()->subMonth()->format('t');
                $reminderDay = $prevMonthDays + $reminderDay;
                // Only match if we're at end of previous month
                // This is a simplified check — for robustness we skip wrap-around
                continue;
            }

            if ($dayOfMonth === $reminderDay) {
                $targetRanges[] = $range;
                $this->info("  ✓ Match: {$range} (reference day {$refDay}, reminder day {$reminderDay})");
            } else {
                $this->line("  · No match: {$range} (reference day {$refDay}, reminder day {$reminderDay})");
            }
        }

        if (empty($targetRanges)) {
            $this->info('No pay day groups match today. No reminders to send.');
            return self::SUCCESS;
        }

        // Query approved applications with matching payDayRange
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';

        $applications = ApplicationState::query()
            ->whereIn('current_step', ['approved', 'completed'])
            ->where(function ($query) use ($isPgsql, $targetRanges) {
                foreach ($targetRanges as $range) {
                    $query->orWhere(function ($q) use ($isPgsql, $range) {
                        if ($isPgsql) {
                            $q->whereRaw("form_data->'formResponses'->>'payDayRange' = ?", [$range]);
                        } else {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.payDayRange')) = ?", [$range]);
                        }
                    });
                }
            })
            ->get();

        $this->info("Found {$applications->count()} applications to notify.");

        if ($applications->isEmpty()) {
            return self::SUCCESS;
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($applications as $app) {
            $formResponses = $app->form_data['formResponses'] ?? [];
            $phone = $formResponses['mobile']
                ?? $formResponses['phoneNumber']
                ?? $formResponses['cellNumber']
                ?? null;

            $firstName = $formResponses['firstName'] ?? 'Customer';

            if (!$phone || strlen(preg_replace('/\D/', '', $phone)) < 9) {
                $skippedCount++;
                $this->warn("  Skipped (no phone): App #{$app->id}");
                continue;
            }

            $message = "Dear {$firstName}, your pay day is approaching. Please ensure your ZB BancoSystem account has sufficient funds for your loan repayment. Thank you!";

            if ($this->option('dry-run')) {
                $this->info("  [DRY RUN] Would send to {$phone}: {$message}");
            } else {
                try {
                    SendSmsJob::dispatch($phone, $message, $app->reference_code);
                    $sentCount++;
                    $this->info("  ✓ Dispatched to {$phone}");
                } catch (\Exception $e) {
                    $skippedCount++;
                    $this->error("  ✗ Failed for {$phone}: {$e->getMessage()}");
                    Log::error('PayDay SMS reminder failed', [
                        'app_id' => $app->id,
                        'phone' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info("Summary: {$sentCount} sent, {$skippedCount} skipped.");

        Log::info('PayDay SMS reminders completed', [
            'target_ranges' => $targetRanges,
            'total_apps' => $applications->count(),
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'dry_run' => $this->option('dry-run'),
        ]);

        return self::SUCCESS;
    }
}
