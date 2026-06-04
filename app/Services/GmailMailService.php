<?php

namespace App\Services;

use App\Contracts\MailProviderInterface;
use App\Models\ApplicationState;
use App\Models\EmailDeliveryLog;
use App\Mail\ApplicationReceived;
use App\Mail\ApplicationStatusUpdated;
use App\Mail\PaymentReminderMail;
use App\Mail\PaymentReceipt;
use App\Mail\WelcomeEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GmailMailService implements MailProviderInterface
{
    /**
     * Send email when an application is received
     */
    public function sendApplicationReceivedEmail(ApplicationState $application): bool
    {
        try {
            $email = $this->getApplicantEmail($application);

            if (!$email) {
                Log::warning("Cannot send ApplicationReceived email: No email found for application {$application->session_id}");
                return false;
            }

            return $this->queue($email, new ApplicationReceived($application), $application);
        } catch (\Exception $e) {
            Log::error("Failed to send ApplicationReceived email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email when application status is updated
     */
    public function sendApplicationStatusUpdatedEmail(ApplicationState $application): bool
    {
        try {
            $email = $this->getApplicantEmail($application);

            if (!$email) {
                Log::warning("Cannot send ApplicationStatusUpdated email: No email found for application {$application->session_id}");
                return false;
            }

            return $this->queue($email, new ApplicationStatusUpdated($application), $application);
        } catch (\Exception $e) {
            Log::error("Failed to send ApplicationStatusUpdated email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment receipt email
     */
    public function sendPaymentReceiptEmail(ApplicationState $application, array $paymentData): bool
    {
        try {
            $email = $this->getApplicantEmail($application);

            if (!$email) {
                Log::warning("Cannot send PaymentReceipt email: No email found for application {$application->session_id}");
                return false;
            }

            return $this->queue($email, new PaymentReceipt($application, $paymentData), $application, [
                'payment' => $paymentData,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send PaymentReceipt email: " . $e->getMessage());
            return false;
        }
    }

    public function sendPaymentReminderEmail(ApplicationState $application, string $stage, string $resumeLink): bool
    {
        try {
            $email = $this->getApplicantEmail($application);

            if (!$email) {
                Log::warning("Cannot send PaymentReminder email: No email found for application {$application->session_id}");
                return false;
            }

            return $this->queue($email, new PaymentReminderMail($application, $stage, $resumeLink), $application, [
                'stage' => $stage,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send PaymentReminder email: " . $e->getMessage());
            return false;
        }
    }

    public function sendWelcomeEmail(string $email, string $name): bool
    {
        try {
            return $this->queue($email, new WelcomeEmail($name), null, ['name' => $name]);
        } catch (\Exception $e) {
            Log::error("Failed to send Welcome email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to extract email from application state form data
     */
    private function getApplicantEmail(ApplicationState $application): ?string
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        return $formResponses['email'] ?? $formData['email'] ?? null;
    }

    private function queue(string $email, Mailable $mailable, ?ApplicationState $application = null, array $metadata = []): bool
    {
        $log = EmailDeliveryLog::create([
            'application_state_id' => $application?->id,
            'recipient' => $email,
            'mailable' => get_class($mailable),
            'subject' => method_exists($mailable, 'envelope') ? $mailable->envelope()->subject : null,
            'status' => 'queued',
            'attempts' => 1,
            'metadata' => $metadata,
        ]);

        try {
            Mail::to($email)->queue($mailable);

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Queued email {$log->mailable} to {$email}");
            return true;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            throw $e;
        }
    }
}
