<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use App\Services\PaynowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DepositPaymentController extends Controller
{
    private PaynowService $paynowService;

    public function __construct(PaynowService $paynowService)
    {
        $this->paynowService = $paynowService;
    }

    /**
     * Initiate deposit payment for an application
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_code' => 'required|string',
            'payment_method' => 'required|in:ecocash,smilecash,card,mastercard',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $referenceCode = $request->reference_code;
        $paymentMethod = $request->payment_method;

        // Find the application
        $application = ApplicationState::where('reference_code', $referenceCode)->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        // Check if application is approved and PDC
        $formData = is_array($application->form_data) ? $application->form_data : json_decode($application->form_data, true);
        $creditType = $formData['creditType'] ?? null;

        if ($creditType !== 'PDC') {
            return response()->json([
                'success' => false,
                'message' => 'This application does not require a deposit payment',
            ], 400);
        }

        // Check if deposit is already paid
        if ($application->deposit_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit has already been paid for this application',
            ], 400);
        }

        // Get deposit amount (assuming it's stored in metadata or calculate it)
        $depositAmount = $application->deposit_amount;

        if (!$depositAmount || $depositAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit amount not found or invalid',
            ], 400);
        }

        try {
            // Create Paynow payment
            $email = $formData['formResponses']['email'] ?? 'no-email@example.com';
            $description = "Deposit Payment for Application {$referenceCode}";

            $paynowResult = $this->paynowService->createPayment(
                $referenceCode,
                $depositAmount,
                $email,
                $description
            );

            if ($paynowResult['success']) {
                // Store payment method
                $application->deposit_payment_method = $paymentMethod;
                $application->save();

                Log::info('Deposit payment initiated', [
                    'reference_code' => $referenceCode,
                    'amount' => $depositAmount,
                    'method' => $paymentMethod,
                ]);

                // For mobile money (ecocash, smilecash), initiate mobile payment
                if (in_array($paymentMethod, ['ecocash', 'smilecash'])) {
                    $phone = $formData['formResponses']['mobile'] ?? $formData['formResponses']['phoneNumber'] ?? null;
                    if ($phone) {
                        $this->paynowService->initiateMobile(
                            $paynowResult['pollUrl'],
                            $phone,
                            $paymentMethod === 'smilecash' ? 'onemoney' : 'ecocash'
                        );
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'data' => [
                        'redirect_url' => $paynowResult['redirectUrl'],
                        'poll_url' => $paynowResult['pollUrl'],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initiate payment: ' . ($paynowResult['error'] ?? 'Unknown error'),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Deposit payment initiation failed', [
                'reference_code' => $referenceCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle payment callback from Paynow
     */
    public function paymentCallback(Request $request)
    {
        // Paynow will send a POST request with payment status
        $referenceCode = $request->reference ?? null;
        $status = strtolower($request->status ?? '');
        $paynowReference = $request->paynowreference ??null;

        if (!$referenceCode) {
            Log::warning('Deposit payment callback received without reference');
            return response('Invalid request', 400);
        }

        $application = ApplicationState::where('reference_code', $referenceCode)->first();

        if (!$application) {
            Log::warning('Deposit payment callback for unknown application', ['reference' => $referenceCode]);
            return response('Application not found', 404);
        }

        if ($status === 'paid' || $status === 'delivered') {
            // Mark deposit as paid
            $application->update([
                'deposit_paid' => true,
                'deposit_paid_at' => now(),
                'deposit_transaction_id' => $paynowReference,
            ]);

            Log::info('Deposit payment successful', [
                'reference_code' => $referenceCode,
                'transaction_id' => $paynowReference,
            ]);

            // TODO: Trigger delivery initiation logic here
            // You might want to update application status or send notification

            return response('OK', 200);
        }

        if ($status === 'cancelled' || $status === 'failed') {
            Log::warning('Deposit payment failed or cancelled', [
                'reference_code' => $referenceCode,
                'status' => $status,
            ]);

            return response('Payment failed', 200);
        }

        return response('OK', 200);
    }

    /**
     * Get deposit payment status for an application
     */
    public function getPaymentStatus(string $referenceCode)
    {
        $application = ApplicationState::where('reference_code', $referenceCode)->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'deposit_amount' => $application->deposit_amount,
                'deposit_paid' => $application->deposit_paid,
                'deposit_paid_at' => $application->deposit_paid_at?->toISOString(),
                'deposit_transaction_id' => $application->deposit_transaction_id,
                'deposit_payment_method' => $application->deposit_payment_method,
            ],
        ]);
    }
}
