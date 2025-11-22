<?php

namespace App\Http\Controllers;

use App\Services\EcocashPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EcocashWebhookController extends Controller
{
    private EcocashPaymentService $ecocashService;

    public function __construct(EcocashPaymentService $ecocashService)
    {
        $this->ecocashService = $ecocashService;
    }

    /**
     * Handle Ecocash payment webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Log incoming webhook
        Log::info('Ecocash webhook received', [
            'ip' => $request->ip(),
            'data' => $request->all(),
        ]);

        try {
            $webhookData = $request->all();

            // Process webhook
            $result = $this->ecocashService->processWebhook($webhookData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Webhook processed successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Webhook processing failed',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Ecocash webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Initiate payment (called from frontend)
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        try {
            $application = \App\Models\ApplicationState::where('reference_code', $request->reference_code)->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'error' => 'Application not found',
                ], 404);
            }

            $result = $this->ecocashService->initiateBlacklistReportPayment($application);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Ecocash payment initiation failed', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment initiation failed',
            ], 500);
        }
    }

    /**
     * Check payment status (for polling)
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'required|string',
        ]);

        try {
            $result = $this->ecocashService->checkPaymentStatus($request->transaction_reference);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Ecocash status check failed', [
                'transaction_reference' => $request->transaction_reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Status check failed',
            ], 500);
        }
    }

    /**
     * Simulate successful payment (TEST MODE ONLY)
     */
    public function simulatePayment(Request $request): JsonResponse
    {
        if (config('app.env') === 'production') {
            return response()->json([
                'success' => false,
                'error' => 'Not available in production',
            ], 403);
        }

        $request->validate([
            'reference_code' => 'required|string',
            'blacklist_institutions' => 'nullable|array',
        ]);

        try {
            $application = \App\Models\ApplicationState::where('reference_code', $request->reference_code)->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'error' => 'Application not found',
                ], 404);
            }

            // Get payment reference from metadata
            $metadata = $application->metadata ?? [];
            $paymentRef = $metadata['zb_data']['payment_reference'] ?? 'BL-' . $request->reference_code . '-' . time();

            // Simulate webhook callback
            $webhookData = [
                'reference' => $paymentRef,
                'status' => 'success',
                'transaction_id' => 'ECOCASH-TEST-' . time(),
                'amount' => 5.00,
                'currency' => 'USD',
                'blacklist_institutions' => $request->blacklist_institutions ?? ['CBZ', 'FBC', 'Barclays'],
            ];

            $result = $this->ecocashService->processWebhook($webhookData);

            return response()->json([
                'success' => true,
                'message' => 'TEST MODE: Payment simulated successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment simulation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Simulation failed',
            ], 500);
        }
    }
}
