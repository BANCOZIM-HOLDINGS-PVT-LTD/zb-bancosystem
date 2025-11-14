<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TwilioSmsService
{
    protected $accountSid;

    protected $authToken;

    protected $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->fromNumber = config('services.twilio.from');
    }

    /**
     * Send SMS using Twilio
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            // Initialize Twilio client
            $client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);

            $response = $client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );

            return [
                'success' => true,
                'message_sid' => $response->sid,
                'status' => $response->status,
                'message' => 'SMS sent successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Twilio SMS Error: '.$e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to send SMS',
            ];
        }
    }

    /**
     * Send new account application confirmation SMS (Note 28)
     */
    public function sendNewAccountConfirmation(string $phoneNumber): array
    {
        $message = 'Thank you for applying for your ZB individual Account. We will inform you of your account number when its open, at which time you will then be able to apply for a credit facility after your salary has been deposited at least once.';

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send application confirmation with tracking number (Note 29)
     */
    public function sendApplicationConfirmation(string $phoneNumber, string $applicationNumber): array
    {
        $message = "Thank you for your application. Your application number is {$applicationNumber}. You can use it to track the progress of your application";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send SMS for application status update
     */
    public function sendStatusUpdate(string $phoneNumber, string $applicationNumber, string $status): array
    {
        $statusMessages = [
            'approved' => "Great news! Your application {$applicationNumber} has been approved. We will contact you soon with next steps.",
            'rejected' => "We regret to inform you that your application {$applicationNumber} was not approved at this time. Please contact us for more details.",
            'pending_documents' => "Your application {$applicationNumber} is pending additional documents. Please check your email or contact us for details.",
            'processing' => "Your application {$applicationNumber} is being processed. We will update you on the progress soon.",
            'delivered' => "Your order for application {$applicationNumber} has been delivered. Thank you for choosing us!",
        ];

        $message = $statusMessages[$status] ?? "Your application {$applicationNumber} status has been updated to: {$status}";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send SMS notification to agent about new application
     */
    public function notifyAgent(string $agentPhone, string $applicationNumber, string $clientName): array
    {
        $message = "New application received: {$applicationNumber} from {$clientName}. Please check your dashboard for details.";

        return $this->sendSms($agentPhone, $message);
    }

    /**
     * Send bulk SMS to multiple recipients
     */
    public function sendBulkSms(array $recipients, string $message): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $phoneNumber) {
            $result = $this->sendSms($phoneNumber, $message);
            $results[] = [
                'phone' => $phoneNumber,
                'result' => $result,
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'total_sent' => count($recipients),
            'successful' => $successCount,
            'failed' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * Format phone number for Twilio (ensure it starts with +263)
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // If it starts with 0, replace with 263
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '263'.substr($cleaned, 1);
        }

        // If it doesn't start with 263, add it
        if (substr($cleaned, 0, 3) !== '263') {
            $cleaned = '263'.$cleaned;
        }

        // Add the + prefix
        return '+'.$cleaned;
    }

    /**
     * Validate Zimbabwe phone number
     */
    public function isValidZimbabweNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);

        // Should be +263 followed by 9 digits (7XXXXXXXX)
        return preg_match('/^\+2637\d{8}$/', $formatted) === 1;
    }

    /**
     * Schedule SMS for later sending
     */
    public function scheduleSms(string $to, string $message, \DateTime $sendAt): array
    {
        try {
            $client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);

            $response = $client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                    'sendAt' => $sendAt->format('c'), // ISO 8601 format
                    'scheduleType' => 'fixed',
                ]
            );

            return [
                'success' => true,
                'message_sid' => $response->sid,
                'scheduled_time' => $sendAt->format('Y-m-d H:i:s'),
                'message' => 'SMS scheduled successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Twilio Scheduled SMS Error: '.$e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'send_at' => $sendAt->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to schedule SMS',
            ];
        }
    }
}
