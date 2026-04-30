<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Mail\ApplicationReceived;
use App\Mail\ApplicationStatusUpdated;
use App\Mail\PaymentReceipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DandemutandeMailService
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

            Mail::to($email)->send(new ApplicationReceived($application));
            
            Log::info("ApplicationReceived email sent to {$email} for application {$application->session_id}");
            return true;
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

            Mail::to($email)->send(new ApplicationStatusUpdated($application));
            
            Log::info("ApplicationStatusUpdated email sent to {$email} for application {$application->session_id}");
            return true;
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

            Mail::to($email)->send(new PaymentReceipt($application, $paymentData));
            
            Log::info("PaymentReceipt email sent to {$email} for application {$application->session_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send PaymentReceipt email: " . $e->getMessage());
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
}
