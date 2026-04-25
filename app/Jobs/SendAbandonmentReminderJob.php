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

class SendAbandonmentReminderJob implements ShouldQueue
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
        Log::info('Starting SendAbandonmentReminderJob');

        // Look for applications abandoned in the last 2 to 24 hours
        // and that haven't reached completion or awaiting deposit status
        $applications = ApplicationState::where('current_step', '!=', 'completed')
            ->where(function($query) {
                $query->where('status', '!=', 'awaiting_deposit')
                      ->orWhereNull('status');
            })
            ->where('updated_at', '<=', Carbon::now()->subHours(2))
            ->where('updated_at', '>=', Carbon::now()->subHours(24))
            ->get();

        foreach ($applications as $app) {
            // Check if we already sent an abandonment reminder
            $alreadySent = PaymentReminder::where('application_state_id', $app->id)
                ->where('reminder_type', 'abandonment')
                ->exists();

            if (!$alreadySent) {
                $this->sendReminder($app, $notificationService);
            }
        }

        Log::info('Finished SendAbandonmentReminderJob');
    }

    private function sendReminder(ApplicationState $app, NotificationService $notificationService)
    {
        $formData = $app->form_data ?? [];
        
        // Extract phone number from various possible locations in form_data
        $phone = data_get($formData, 'formResponses.mobile')
            ?? data_get($formData, 'formResponses.cellNumber')
            ?? data_get($formData, 'formResponses.whatsApp')
            ?? data_get($formData, 'formResponses.phoneNumber')
            ?? data_get($formData, 'contact.phone')
            ?? data_get($app->metadata, 'phone_number');
            
        $name = data_get($formData, 'formResponses.firstName') ?? 'there';

        if (!$phone) {
            // No phone number, cannot send SMS reminder
            return;
        }

        $link = url("/application/resume/{$app->session_id}");
        $message = "Hi {$name}, we noticed you didn't finish your application. You can resume right where you left off here: {$link}";

        Log::info("Sending abandonment reminder to {$phone} for app {$app->id}");

        $success = $notificationService->sendSMS($phone, $message);

        if ($success) {
            PaymentReminder::create([
                'application_state_id' => $app->id,
                'reminder_type' => 'abandonment',
                'reminder_stage' => '2_hours',
                'sent_at' => now(),
            ]);
        }
    }
}
