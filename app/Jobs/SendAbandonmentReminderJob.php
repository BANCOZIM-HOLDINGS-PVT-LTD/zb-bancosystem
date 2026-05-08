<?php

namespace App\Jobs;

use App\Models\ApplicationState;
use App\Models\PaymentReminder;
use App\Services\DandemutandeMailService;
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
    public function handle(NotificationService $notificationService, DandemutandeMailService $mailService): void
    {
        Log::info('Starting SendAbandonmentReminderJob');

        $intervals = config('reminders.abandonment_intervals', [
            '24_hours' => 24,
            '48_hours' => 48,
            '7_days' => 168,
        ]);

        foreach ($intervals as $stage => $hours) {
            $applications = ApplicationState::where('current_step', '!=', 'completed')
                ->where(function ($query) {
                    $query->where('status', '!=', 'awaiting_deposit')
                        ->orWhereNull('status');
                })
                ->where('updated_at', '<=', Carbon::now()->subHours($hours))
                ->where('updated_at', '>=', Carbon::now()->subHours($hours + 2))
                ->get();

            foreach ($applications as $app) {
                $alreadySent = PaymentReminder::where('application_state_id', $app->id)
                    ->where('reminder_type', 'abandonment')
                    ->where('reminder_stage', $stage)
                    ->exists();

                if (!$alreadySent) {
                    $this->sendReminder($app, $stage, $notificationService, $mailService);
                }
            }
        }

        Log::info('Finished SendAbandonmentReminderJob');
    }

    private function sendReminder(
        ApplicationState $app,
        string $stage,
        NotificationService $notificationService,
        DandemutandeMailService $mailService
    ): void
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

        $link = url("/application/resume/{$app->session_id}");
        $message = "Hi {$name}, we noticed you didn't finish your application. You can resume right where you left off here: {$link}";
        $channels = config("reminders.abandonment_escalation_channels.{$stage}", ['sms']);

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
                'reminder_type' => 'abandonment',
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
        Log::info('WhatsApp abandonment reminder queued for manual/provider dispatch', [
            'application_id' => $app->id,
            'message' => $message,
        ]);

        return true;
    }

    private function logAdminAlert(ApplicationState $app, string $stage): bool
    {
        Log::warning('Abandonment reminder escalated to admin', [
            'application_id' => $app->id,
            'stage' => $stage,
        ]);

        return true;
    }
}
