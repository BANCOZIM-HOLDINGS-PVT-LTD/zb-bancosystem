<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

use App\Contracts\SmsProviderInterface;

class SMSService
{
    private SmsProviderInterface $smsProvider;

    public function __construct(SmsProviderInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    /**
     * Send SMS notification using Twilio
     */
    public function sendSMS(string $mobile, string $message): bool
    {
        try {
            // Format mobile number
            $mobile = $this->formatMobileNumber($mobile);

            // Send via SMS Provider
            $result = $this->smsProvider->sendSms($mobile, $message);

            if ($result['success']) {
                Log::info('SMS sent successfully via Provider', [
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
        return $this->smsProvider->formatPhoneNumber($mobile);
    }

    /**
     * Validate mobile number format
     */
    public function isValidMobileNumber(string $mobile): bool
    {
        return $this->smsProvider->isValidZimbabweNumber($mobile);
    }
}
