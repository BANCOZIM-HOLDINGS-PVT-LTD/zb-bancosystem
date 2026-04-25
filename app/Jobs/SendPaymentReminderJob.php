<?php

namespace App\Jobs;

use App\Models\ApplicationState;
use App\Models\PaymentReminder;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        Log::info('Starting SendPaymentReminderJob');

        $intervals = [
            '3_days' => 3,
            '7_days' => 7,
            '14_days' => 14,
        ];

        foreach ($intervals as $stage => $days) {
            $targetDate = Carbon::today()->subDays($days);

            $applications = ApplicationState::where('status', 'awaiting_deposit')
                ->whereDate('updated_at', $targetDate)
                ->get();

            foreach ($applications as $app) {
                // Check if we already sent this specific reminder
                $alreadySent = PaymentReminder::where('application_state_id', $app->id)
                    ->where('reminder_stage', $stage)
                    ->exists();

                if (!$alreadySent) {
                    $this->sendReminder($app, $stage, $notificationService);
                }
            }
        }

        Log::info('Finished SendPaymentReminderJob');
    }

    private function sendReminder(ApplicationState $app, string $stage, NotificationService $notificationService)
    {
        $formData = $app->form_data ?? [];
        $phone = $formData['formResponses']['phone'] ?? $formData['formResponses']['phoneNumber'] ?? $formData['formResponses']['mobile'] ?? null;
        $name = $formData['formResponses']['firstName'] ?? 'Customer';

        if (!$phone) {
            Log::warning("Cannot send payment reminder for app {$app->id}: No phone number found.");
            return;
        }

        $link = url("/application/resume/{$app->session_id}");
        $message = "Dear {$name}, your application is awaiting deposit payment. Please complete your payment here: {$link}";

        Log::info("Sending {$stage} payment reminder to {$phone} for app {$app->id}");

        $success = $notificationService->sendSMS($phone, $message);

        if ($success) {
            PaymentReminder::create([
                'application_state_id' => $app->id,
                'reminder_stage' => $stage,
                'sent_at' => now(),
            ]);
        }
    }
}
