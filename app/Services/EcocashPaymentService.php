<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EcocashPaymentService
{
    private string $merchantCode;
    private string $merchantKey;
    private string $apiUrl;
    private bool $isProduction;

    public function __construct()
    {
        $this->merchantCode = config('services.ecocash.merchant_code', '');
        $this->merchantKey = config('services.ecocash.merchant_key', '');
        $this->apiUrl = config('services.ecocash.api_url', '');
        $this->isProduction = config('app.env') === 'production';
    }

    /**
     * Initiate Ecocash payment for blacklist report
     */
    public function initiateBlacklistReportPayment(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $mobile = $formResponses['mobile'] ?? null;

        if (!$mobile) {
            return [
                'success' => false,
                'error' => 'Mobile number not found',
            ];
        }

        // Format mobile number for Ecocash (remove +263, keep 07...)
        $ecoCashNumber = $this->formatEcocashNumber($mobile);

        // Generate unique transaction reference
        $transactionRef = 'BL-' . $application->reference_code . '-' . time();

        $paymentData = [
            'amount' => 5.00, // $5 USD
            'currency' => 'USD',
            'mobile' => $ecoCashNumber,
            'reference' => $transactionRef,
            'description' => 'ZB Blacklist Report - Ref: ' . $application->reference_code,
            'callback_url' => route('ecocash.webhook'),
        ];

        try {
            if ($this->isProduction) {
                // Production: Make actual Ecocash API call
                $response = $this->makeEcocashAPICall($paymentData);

                if ($response['success']) {
                    // Store payment reference in metadata
                    $metadata = $application->metadata ?? [];
                    $metadata['zb_data'] = $metadata['zb_data'] ?? [];
                    $metadata['zb_data']['payment_initiated'] = true;
                    $metadata['zb_data']['payment_reference'] = $transactionRef;
                    $metadata['zb_data']['ecocash_transaction_id'] = $response['transaction_id'] ?? null;
                    $metadata['zb_data']['payment_initiated_at'] = now()->toISOString();

                    $application->update(['metadata' => $metadata]);

                    Log::info('Ecocash payment initiated', [
                        'reference' => $application->reference_code,
                        'transaction_ref' => $transactionRef,
                        'mobile' => $ecoCashNumber,
                    ]);

                    return [
                        'success' => true,
                        'transaction_reference' => $transactionRef,
                        'ecocash_transaction_id' => $response['transaction_id'] ?? null,
                        'amount' => 5.00,
                        'currency' => 'USD',
                        'mobile' => $ecoCashNumber,
                        'message' => 'Payment initiated. Check your phone for Ecocash prompt.',
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $response['error'] ?? 'Payment initiation failed',
                    ];
                }

            } else {
                // Development/Testing: Simulate payment
                $metadata = $application->metadata ?? [];
                $metadata['zb_data'] = $metadata['zb_data'] ?? [];
                $metadata['zb_data']['payment_initiated'] = true;
                $metadata['zb_data']['payment_reference'] = $transactionRef;
                $metadata['zb_data']['payment_initiated_at'] = now()->toISOString();

                $application->update(['metadata' => $metadata]);

                Log::info('Ecocash payment initiated (TEST MODE)', [
                    'reference' => $application->reference_code,
                    'transaction_ref' => $transactionRef,
                    'mobile' => $ecoCashNumber,
                ]);

                return [
                    'success' => true,
                    'transaction_reference' => $transactionRef,
                    'amount' => 5.00,
                    'currency' => 'USD',
                    'mobile' => $ecoCashNumber,
                    'message' => 'TEST MODE: Payment initiated. Use webhook to simulate completion.',
                    'test_mode' => true,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Ecocash payment initiation failed', [
                'reference' => $application->reference_code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment initiation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Ecocash webhook notification
     */
    public function processWebhook(array $webhookData): array
    {
        try {
            // Validate webhook signature
            if ($this->isProduction && !$this->validateWebhookSignature($webhookData)) {
                Log::warning('Invalid Ecocash webhook signature', $webhookData);
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            // Extract payment details
            $transactionRef = $webhookData['reference'] ?? null;
            $status = $webhookData['status'] ?? 'failed';
            $ecoCashTransactionId = $webhookData['transaction_id'] ?? null;

            if (!$transactionRef) {
                return [
                    'success' => false,
                    'error' => 'Missing transaction reference',
                ];
            }

            // Extract reference code from transaction reference (format: BL-REFXXXXX-timestamp)
            if (!preg_match('/^BL-([A-Z0-9]+)-\d+$/', $transactionRef, $matches)) {
                return [
                    'success' => false,
                    'error' => 'Invalid transaction reference format',
                ];
            }

            $referenceCode = $matches[1];

            // Find application
            $application = ApplicationState::where('reference_code', $referenceCode)->first();

            if (!$application) {
                Log::warning('Application not found for Ecocash webhook', [
                    'reference' => $referenceCode,
                    'transaction_ref' => $transactionRef,
                ]);

                return [
                    'success' => false,
                    'error' => 'Application not found',
                ];
            }

            // Check if payment is successful
            if (strtolower($status) === 'success' || strtolower($status) === 'completed') {
                // Payment successful - process blacklist report
                $zbService = app(ZBStatusService::class);

                // Get blacklist institutions from webhook or use default
                $blacklistInstitutions = $webhookData['blacklist_institutions'] ?? [];

                $success = $zbService->processBlacklistReportPayment(
                    $application,
                    $ecoCashTransactionId ?? $transactionRef,
                    $blacklistInstitutions
                );

                if ($success) {
                    Log::info('Ecocash payment processed successfully', [
                        'reference' => $referenceCode,
                        'transaction_ref' => $transactionRef,
                        'ecocash_tx_id' => $ecoCashTransactionId,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Payment processed and blacklist report sent',
                    ];
                } else {
                    Log::error('Failed to process blacklist report payment', [
                        'reference' => $referenceCode,
                        'transaction_ref' => $transactionRef,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Failed to process payment',
                    ];
                }

            } else {
                // Payment failed
                Log::warning('Ecocash payment failed', [
                    'reference' => $referenceCode,
                    'transaction_ref' => $transactionRef,
                    'status' => $status,
                ]);

                return [
                    'success' => false,
                    'error' => 'Payment failed: ' . $status,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Ecocash webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $webhookData,
            ]);

            return [
                'success' => false,
                'error' => 'Webhook processing failed',
            ];
        }
    }

    /**
     * Make actual Ecocash API call (Production)
     */
    private function makeEcocashAPICall(array $paymentData): array
    {
        try {
            // Prepare Ecocash API request
            $requestData = [
                'merchant_code' => $this->merchantCode,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'mobile' => $paymentData['mobile'],
                'reference' => $paymentData['reference'],
                'description' => $paymentData['description'],
                'callback_url' => $paymentData['callback_url'],
                'timestamp' => now()->toIso8601String(),
            ];

            // Generate signature
            $requestData['signature'] = $this->generateSignature($requestData);

            // Make API call
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/initiate-payment', $requestData);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'status' => $data['status'] ?? 'pending',
                ];
            } else {
                Log::error('Ecocash API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'API call failed: ' . $response->status(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate signature for API request
     */
    private function generateSignature(array $data): string
    {
        // Remove signature field if exists
        unset($data['signature']);

        // Sort data alphabetically
        ksort($data);

        // Create signature string
        $signatureString = implode('|', $data) . '|' . $this->merchantKey;

        // Generate hash
        return hash('sha256', $signatureString);
    }

    /**
     * Validate webhook signature
     */
    private function validateWebhookSignature(array $webhookData): bool
    {
        $receivedSignature = $webhookData['signature'] ?? '';

        if (!$receivedSignature) {
            return false;
        }

        $expectedSignature = $this->generateSignature($webhookData);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Format mobile number for Ecocash
     * Ecocash expects: 0771234567 (starts with 0)
     */
    private function formatEcocashNumber(string $mobile): string
    {
        // Remove any spaces, dashes, plus signs
        $cleaned = preg_replace('/[\s\-\+]/', '', $mobile);

        // If starts with 263, replace with 0
        if (str_starts_with($cleaned, '263')) {
            return '0' . substr($cleaned, 3);
        }

        // If doesn't start with 0, add it
        if (!str_starts_with($cleaned, '0')) {
            return '0' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Check payment status (for polling)
     */
    public function checkPaymentStatus(string $transactionRef): array
    {
        if (!$this->isProduction) {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'TEST MODE: Use webhook to complete payment',
            ];
        }

        try {
            $requestData = [
                'merchant_code' => $this->merchantCode,
                'reference' => $transactionRef,
                'timestamp' => now()->toIso8601String(),
            ];

            $requestData['signature'] = $this->generateSignature($requestData);

            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/check-status', $requestData);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'transaction_id' => $data['transaction_id'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Status check failed',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
