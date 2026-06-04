<?php

namespace App\Jobs;

use App\Models\ApplicationState;
use App\Models\PaymentReminder;
use App\Services\GmailMailService;
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
    public function handle(NotificationService $notificationService, GmailMailService $mailService): void
    {
        Log::info('Starting SendPaymentReminderJob');

        $intervals = config('reminders.deposit_intervals', [
            '3_days' => 3,
            '7_days' => 7,
            '14_days' => 14,
        ]);

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
                    $this->sendReminder($app, $stage, $notificationService, $mailService);
                }
            }
        }

        Log::info('Finished SendPaymentReminderJob');
    }

    private function sendReminder(
        ApplicationState $app,
        string $stage,
        NotificationService $notificationService,
        GmailMailService $mailService
    ): void
    {
        $formData = $app->form_data ?? [];
        $phone = $formData['formResponses']['phone'] ?? $formData['formResponses']['phoneNumber'] ?? $formData['formResponses']['mobile'] ?? null;
        $name = $formData['formResponses']['firstName'] ?? 'Customer';

        $link = url("/application/resume/{$app->session_id}");
        $message = "Dear {$name}, your application is awaiting deposit payment. Please complete your payment here: {$link}";
        $channels = config("reminders.deposit_escalation_channels.{$stage}", ['sms']);

        foreach ($channels as $channel) {
            $success = match ($channel) {
                'sms' => $phone ? $notificationService->sendSMS($phone, $message) : false,
                'email' => $mailService->sendPaymentReminderEmail($app, $stage, $link),
                'whatsapp' => $this->logWhatsAppReminder($app, $message),
                'admin_alert' => $this->logAdminAlert($app, $stage),
                default => false,
            };

            PaymentReminder::create([
                'application_state_id' => $app->id,
                'reminder_type' => 'deposit_pending',
                'reminder_stage' => $stage,
                'channel' => $channel,
                'delivery_status' => $success ? 'sent' : 'failed',
                'metadata' => [
                    'phone' => $channel === 'sms' ? $phone : null,
                    'resume_link' => $link,
                ],
                'sent_at' => now(),
            ]);
        }
    }

    private function logWhatsAppReminder(ApplicationState $app, string $message): bool
    {
        Log::info('WhatsApp payment reminder queued for manual/provider dispatch', [
            'application_id' => $app->id,
            'message' => $message,
        ]);

        return true;
    }

    private function logAdminAlert(ApplicationState $app, string $stage): bool
    {
        Log::warning('Payment reminder escalated to admin', [
            'application_id' => $app->id,
            'stage' => $stage,
        ]);

        return true;
    }
}
