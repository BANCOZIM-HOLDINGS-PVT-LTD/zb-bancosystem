<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SMSService
{
    private TwilioSmsService $twilioSmsService;

    public function __construct(TwilioSmsService $twilioSmsService)
    {
        $this->twilioSmsService = $twilioSmsService;
    }

    /**
     * Send SMS notification using Twilio
     */
    public function sendSMS(string $mobile, string $message): bool
    {
        try {
            // Format mobile number
            $mobile = $this->formatMobileNumber($mobile);

            // Send via Twilio
            $result = $this->twilioSmsService->sendSms($mobile, $message);

            if ($result['success']) {
                Log::info('SMS sent successfully via Twilio', [
                    'mobile' => $mobile,
                    'message_sid' => $result['message_sid'] ?? null,
                    'sent_at' => now()->toISOString(),
                ]);
                return true;
            } else {
                Log::warning('SMS sending failed via Twilio', [
                    'mobile' => $mobile,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk SMS notifications
     */
    public function sendBulkSMS(array $recipients): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($recipients as $recipient) {
            $mobile = $recipient['mobile'] ?? null;
            $message = $recipient['message'] ?? null;

            if (!$mobile || !$message) {
                $results['failed']++;
                continue;
            }

            if ($this->sendSMS($mobile, $message)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Format mobile number to international format
     */
    private function formatMobileNumber(string $mobile): string
    {
        return $this->twilioSmsService->formatPhoneNumber($mobile);
    }

    /**
     * Validate mobile number format
     */
    public function isValidMobileNumber(string $mobile): bool
    {
        return $this->twilioSmsService->isValidZimbabweNumber($mobile);
    }
}
