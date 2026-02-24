<?php

namespace App\Console\Commands;

use App\Models\ApplicationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLoanOfficerNotifications extends Command
{
    protected $signature = 'payday:notify-officers {--dry-run : Show what would be sent without actually sending}';
    protected $description = 'Send email to loan officer with clients whose pay day is in 4 days (for account holds)';

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

        $loanOfficerEmail = config('services.loan_officer.email', env('LOAN_OFFICER_EMAIL'));

        if (!$loanOfficerEmail) {
            $this->error('LOAN_OFFICER_EMAIL is not configured. Set it in your .env file.');
            return self::FAILURE;
        }

        $this->info("Running Loan Officer Notification for {$today->format('Y-m-d')}");
        $this->info("Loan Officer Email: {$loanOfficerEmail}");

        // Determine which payDayRange groups need account holds
        $targetRanges = [];
        $ranges = ['week1', 'week2', 'week3', 'week4'];

        foreach ($ranges as $range) {
            $refDay = $this->getReferenceDay($range);
            if ($range === 'week4') {
                $refDay = min($refDay, $daysInMonth);
            }

            $reminderDay = $refDay - 4;
            if ($reminderDay <= 0) continue;

            if ($dayOfMonth === $reminderDay) {
                $targetRanges[] = $range;
            }
        }

        if (empty($targetRanges)) {
            $this->info('No pay day groups match today. No notifications needed.');
            return self::SUCCESS;
        }

        $this->info('Matching ranges: ' . implode(', ', $targetRanges));

        // Query approved applications
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

        $this->info("Found {$applications->count()} clients for account holds.");

        if ($applications->isEmpty()) {
            $this->info('No clients found. No email to send.');
            return self::SUCCESS;
        }

        // Build client list
        $clients = [];
        foreach ($applications as $app) {
            $formResponses = $app->form_data['formResponses'] ?? [];
            $payDayRange = $formResponses['payDayRange'] ?? 'unknown';

            $clients[] = [
                'app_number' => $app->application_number,
                'name' => trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['surname'] ?? $formResponses['lastName'] ?? '')),
                'phone' => $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellNumber'] ?? '—',
                'id_number' => $formResponses['nationalIdNumber'] ?? '—',
                'loan_amount' => $formResponses['loanAmount'] ?? $app->form_data['finalPrice'] ?? '—',
                'monthly_payment' => $formResponses['monthlyPayment'] ?? '—',
                'pay_day_range' => $payDayRange,
                'employer' => $formResponses['employerName'] ?? $app->form_data['employer'] ?? '—',
            ];
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] Email would contain:');
            $this->table(
                ['App #', 'Name', 'Phone', 'ID', 'Loan Amount', 'Monthly Payment', 'Pay Day', 'Employer'],
                array_map(fn ($c) => array_values($c), $clients)
            );
            return self::SUCCESS;
        }

        // Send email
        try {
            Mail::send('emails.pay-day-hold-notification', [
                'clients' => $clients,
                'date' => $today->format('d F Y'),
                'targetRanges' => $targetRanges,
            ], function ($message) use ($loanOfficerEmail, $today) {
                $message->to($loanOfficerEmail)
                    ->subject("Pay Day Account Hold Notification - {$today->format('d M Y')}");
            });

            $this->info("✓ Email sent to {$loanOfficerEmail} with {$applications->count()} client(s).");

            Log::info('Loan officer pay day notification sent', [
                'email' => $loanOfficerEmail,
                'client_count' => $applications->count(),
                'target_ranges' => $targetRanges,
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to send email: {$e->getMessage()}");
            Log::error('Loan officer notification failed', [
                'email' => $loanOfficerEmail,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
