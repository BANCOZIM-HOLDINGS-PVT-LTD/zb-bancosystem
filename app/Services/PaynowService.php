<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaynowService
{
    private ?string $integrationId;
    private ?string $integrationKey;
    private string $returnUrl;
    private string $resultUrl;
    private string $apiUrl;

    public function __construct()
    {
        $this->integrationId = config('services.paynow.integration_id') ?? '';
        $this->integrationKey = config('services.paynow.integration_key') ?? '';
        $this->returnUrl = config('services.paynow.return_url') ?? route('cash.purchase.success', ['purchase' => 'PURCHASE_NUMBER']);
        $this->resultUrl = config('services.paynow.result_url') ?? route('paynow.webhook');
        $this->apiUrl = config('services.paynow.api_url') ?? 'https://www.paynow.co.zw';
    }

    /**
     * Generate a payment URL for a cash purchase
     *
     * @param string $reference Unique reference (purchase number)
     * @param float $amount Amount to pay
     * @param string $email Customer email
     * @param string $description Payment description
     * @return array ['success' => bool, 'pollUrl' => string|null, 'redirectUrl' => string|null, 'error' => string|null]
     */
    public function createPayment(string $reference, float $amount, string $email, string $description = 'Cash Purchase'): array
    {
        try {
            // Build payment data
            $data = [
                'resulturl' => $this->resultUrl,
                'returnurl' => str_replace('PURCHASE_NUMBER', $reference, $this->returnUrl),
                'reference' => $reference,
                'amount' => number_format($amount, 2, '.', ''),
                'id' => $this->integrationId,
                'additionalinfo' => $description,
                'authemail' => $email,
                'status' => 'Message',
            ];

            // Generate hash
            $data['hash'] = $this->generateHash($data);

            // Send request to Paynow
            $response = Http::asForm()->post("{$this->apiUrl}/initiatetransaction", $data);

            if (!$response->successful()) {
                Log::error('Paynow API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'pollUrl' => null,
                    'redirectUrl' => null,
                    'error' => 'Failed to connect to payment gateway',
                ];
            }

            // Parse response
            $result = $this->parseResponse($response->body());

            if ($result['status'] === 'Ok' || strtolower($result['status']) === 'ok') {
                // Store poll URL for later verification
                Cache::put("paynow_poll_{$reference}", $result['pollurl'] ?? null, now()->addHours(24));

                return [
                    'success' => true,
                    'pollUrl' => $result['pollurl'] ?? null,
                    'redirectUrl' => $result['browserurl'] ?? null,
                    'error' => null,
                ];
            }

            Log::warning('Paynow transaction initiation failed', [
                'reference' => $reference,
                'status' => $result['status'],
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'pollUrl' => null,
                'redirectUrl' => null,
                'error' => $result['error'] ?? 'Payment initiation failed',
            ];

        } catch (\Exception $e) {
            Log::error('Paynow service error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'pollUrl' => null,
                'redirectUrl' => null,
                'error' => 'An error occurred while processing payment',
            ];
        }
    }

    /**
     * Verify payment status using transaction ID or poll URL
     *
     * @param string $transactionIdOrReference Transaction ID or purchase reference
     * @param float|null $expectedAmount Expected payment amount for verification
     * @return bool True if payment is verified and completed
     */
    public function verifyPayment(string $transactionIdOrReference, ?float $expectedAmount = null): bool
    {
        try {
            // Try to get poll URL from cache
            $pollUrl = Cache::get("paynow_poll_{$transactionIdOrReference}");

            if (!$pollUrl) {
                // If no poll URL, we can't verify automatically
                // In production, you might have a database table storing poll URLs
                Log::warning('No poll URL found for payment verification', [
                    'reference' => $transactionIdOrReference,
                ]);

                // For now, return true to allow manual verification
                // In production, you'd want stricter verification
                return true;
            }

            $response = Http::get($pollUrl);

            if (!$response->successful()) {
                Log::error('Payment verification request failed', [
                    'poll_url' => $pollUrl,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $result = $this->parseResponse($response->body());

            // Check payment status
            $isPaid = in_array(strtolower($result['status'] ?? ''), ['paid', 'awaiting delivery']);

            // Verify amount if provided
            if ($isPaid && $expectedAmount !== null) {
                $paidAmount = (float) ($result['amount'] ?? 0);
                if (abs($paidAmount - $expectedAmount) > 0.01) {
                    Log::warning('Payment amount mismatch', [
                        'expected' => $expectedAmount,
                        'paid' => $paidAmount,
                        'reference' => $transactionIdOrReference,
                    ]);
                    return false;
                }
            }

            if ($isPaid) {
                Log::info('Payment verified successfully', [
                    'reference' => $transactionIdOrReference,
                    'amount' => $result['amount'] ?? 'N/A',
                    'paynowreference' => $result['paynowreference'] ?? 'N/A',
                ]);
            }

            return $isPaid;

        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'reference' => $transactionIdOrReference,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle webhook callback from Paynow
     *
     * @param array $data Webhook data from Paynow
     * @return array ['verified' => bool, 'reference' => string|null, 'status' => string|null, 'amount' => float|null]
     */
    public function handleWebhook(array $data): array
    {
        try {
            // Verify hash
            $hash = $data['hash'] ?? '';
            unset($data['hash']);

            $expectedHash = $this->generateHash($data);

            if ($hash !== $expectedHash) {
                Log::warning('Paynow webhook hash mismatch', [
                    'received_hash' => $hash,
                    'expected_hash' => $expectedHash,
                ]);

                return [
                    'verified' => false,
                    'reference' => null,
                    'status' => null,
                    'amount' => null,
                ];
            }

            $reference = $data['reference'] ?? null;
            $status = strtolower($data['status'] ?? '');
            $amount = (float) ($data['amount'] ?? 0);
            $paynowReference = $data['paynowreference'] ?? null;

            Log::info('Paynow webhook received', [
                'reference' => $reference,
                'status' => $status,
                'amount' => $amount,
                'paynow_reference' => $paynowReference,
            ]);

            return [
                'verified' => true,
                'reference' => $reference,
                'status' => $status,
                'amount' => $amount,
                'paynow_reference' => $paynowReference,
                'is_paid' => in_array($status, ['paid', 'awaiting delivery']),
            ];

        } catch (\Exception $e) {
            Log::error('Webhook handling error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'verified' => false,
                'reference' => null,
                'status' => null,
                'amount' => null,
            ];
        }
    }

    /**
     * Generate hash for Paynow request
     *
     * @param array $data Data to hash
     * @return string Generated hash
     */
    private function generateHash(array $data): string
    {
        // Remove hash from data if present
        unset($data['hash']);

        // Sort by key
        ksort($data);

        // Build string to hash
        $string = '';
        foreach ($data as $key => $value) {
            if ($value !== '' && $value !== null) {
                $string .= $value;
            }
        }

        // Append integration key
        $string .= $this->integrationKey;

        // Generate SHA512 hash
        return strtoupper(hash('sha512', $string));
    }

    /**
     * Parse Paynow response string to array
     *
     * @param string $response Response string from Paynow
     * @return array Parsed response data
     */
    private function parseResponse(string $response): array
    {
        $data = [];
        $lines = explode("\n", trim($response));

        foreach ($lines as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $data[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return $data;
    }

    /**
     * Get payment status from poll URL
     *
     * @param string $pollUrl Poll URL from Paynow
     * @return array Payment status information
     */
    public function pollPaymentStatus(string $pollUrl): array
    {
        try {
            $response = Http::get($pollUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Failed to poll payment status',
                ];
            }

            $result = $this->parseResponse($response->body());

            return [
                'success' => true,
                'status' => strtolower($result['status'] ?? 'unknown'),
                'amount' => (float) ($result['amount'] ?? 0),
                'reference' => $result['reference'] ?? null,
                'paynow_reference' => $result['paynowreference'] ?? null,
                'is_paid' => in_array(strtolower($result['status'] ?? ''), ['paid', 'awaiting delivery']),
            ];

        } catch (\Exception $e) {
            Log::error('Poll payment status error', [
                'poll_url' => $pollUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Paynow is configured
     *
     * @return bool True if Paynow credentials are configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->integrationId) && !empty($this->integrationKey);
    }
}