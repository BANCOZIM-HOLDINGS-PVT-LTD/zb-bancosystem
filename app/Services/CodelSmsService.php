<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CodelSmsService implements SmsProviderInterface
{
    protected $baseUrl = 'https://2wcapi.codel.tech/2wc';
    protected $token;
    protected $senderId;

    public function __construct()
    {
        $this->token = config('services.codel.token');
        $this->senderId = config('services.codel.sender_id', 'MicroBiz');
    }

    /**
     * Send a single SMS
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            // Use v1 API which uses the account's Default Sender ID
            $url = "{$this->baseUrl}/single-sms/v1/api";
            
            // Format number to 12 digits (263...) without +
            $destination = $this->formatPhoneNumberForApi($to);

            $payload = [
                'token' => $this->token,
                // 'sender_id' => $this->senderId, // v1 uses default sender ID
                'destination' => $destination,
                'messageText' => $message,
                'messageReference' => uniqid('msg_'),
                'messageDate' => '',
                'messageValidity' => '',
                'sendDateTime' => ''
            ];

            Log::info("Sending Codel SMS to {$destination}", ['payload' => $payload]);

            $response = Http::post($url, $payload);
            $result = $response->json();
            
            Log::info("Codel SMS Response", ['response' => $result]);

            // Check for success based on response structure
            // Sample success: {"status": "PENDING", ...} or similar. 
            // The docs show sample response: {"originator": "CODEL", ..., "status": "PENDING", ...}
            $success = isset($result['status']) && in_array($result['status'], ['PENDING', 'SENT', 'DELIVERED', 'Success']);

            return [
                'success' => $success,
                'message_sid' => $result['messageId'] ?? $result['message_id'] ?? null,
                'status' => $result['status'] ?? 'unknown',
                'message' => $success ? 'SMS sent successfully' : 'Failed to send SMS',
                'raw_response' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Codel SMS Error: ' . $e->getMessage(), [
                'to' => $to,
                'message' => $message
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to send SMS'
            ];
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSms(array $recipients, string $message): array
    {
        try {
            $url = "{$this->baseUrl}/multiple-sms/v1/api";
            $batchNumber = uniqid('batch_');

            $messagesList = [];
            foreach ($recipients as $recipient) {
                $messagesList[] = [
                    'destination' => $this->formatPhoneNumberForApi($recipient),
                    'messageText' => $message,
                    'messageReference' => uniqid('ref_')
                ];
            }

            $payload = [
                'data' => [
                    'auth' => [
                        'token' => $this->token,
                        'senderID' => $this->senderId
                    ],
                    'payload' => [
                        'batchNumber' => $batchNumber,
                        'messages' => $messagesList
                    ]
                ]
            ];

            Log::info("Sending Codel Bulk SMS", ['count' => count($recipients)]);

            $response = Http::post($url, $payload);
            $result = $response->json();

            // Docs say response is a list, typically containing status object
            // [{"status": {"error-code": "000", "error-status": "Success", ...}, ...}]
            
            $success = false;
            $successCount = 0;
            $failedCount = 0;

            if (is_array($result) && !empty($result)) {
                $firstItem = $result[0];
                if (isset($firstItem['status']['error-code']) && $firstItem['status']['error-code'] === '000') {
                    $success = true;
                    // Try to parse success details if available
                    if (isset($firstItem['sms-response-details'][0]['success-count'])) {
                        $successCount = (int)$firstItem['sms-response-details'][0]['success-count'];
                    } else {
                        $successCount = count($recipients); // Assume all sent if main status is success
                    }
                }
            }

            return [
                'total_sent' => count($recipients),
                'successful' => $successCount,
                'failed' => count($recipients) - $successCount,
                'results' => $result,
                'success' => $success
            ];

        } catch (\Exception $e) {
            Log::error('Codel Bulk SMS Error: ' . $e->getMessage());
            
            return [
                'total_sent' => count($recipients),
                'successful' => 0,
                'failed' => count($recipients),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for Codel API (263...)
     */
    private function formatPhoneNumberForApi(string $phone): string
    {
        // Remove non-digits
        $cleaned = preg_replace('/\D/', '', $phone);
        
        // Ensure it starts with 263
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '263' . substr($cleaned, 1);
        } elseif (substr($cleaned, 0, 3) !== '263') {
            $cleaned = '263' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Format phone number for general use (e.g. +263...)
     * This keeps consistency with the Interface expectation
     */
    public function formatPhoneNumber(string $phone): string
    {
        $apiFormat = $this->formatPhoneNumberForApi($phone);
        return '+' . $apiFormat;
    }

    /**
     * Validate Zimbabwe number
     */
    public function isValidZimbabweNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);
        return preg_match('/^\+2637\d{8}$/', $formatted) === 1;
    }
}
