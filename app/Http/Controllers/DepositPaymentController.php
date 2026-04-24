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

        // Get application
        $application = ApplicationState::where('reference_code', $referenceCode)->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => "Application with reference {$referenceCode} not found",
            ], 404);
        }

        // Determine payment type with better priority
        $formData = is_array($application->form_data) ? $application->form_data : json_decode($application->form_data, true);

        // Priority: 1. URL/Request param, 2. Database column, 3. Form Data, 4. Default 'credit'
        $paymentType = $request->input('payment_type') 
            ?? $application->payment_type 
            ?? $formData['paymentType'] 
            ?? 'credit';

        $creditType = $formData['creditType'] ?? null;

        // Log for debugging
        Log::info('Payment initiation check', [
            'ref' => $referenceCode,
            'detected_type' => $paymentType,
            'db_type' => $application->payment_type,
            'form_type' => $formData['paymentType'] ?? 'not set'
        ]);

        if ($paymentType !== 'cash' && (!$creditType || (!str_starts_with($creditType, 'PDC') && $creditType !== 'PDC'))) {
            return response()->json([
                'success' => false,
                'message' => "This application does not require a payment at this stage (Detected Type: {$paymentType})",
            ], 400);
        }

        // Check if payment is already paid
        if ($application->deposit_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment has already been received for this application',
            ], 400);
        }

        // Get amount (deposit for credit, total for cash)
        $amount = 0;
        if ($paymentType === 'cash') {
            $amount = $formData['finalPrice'] ?? $formData['amount'] ?? 0;
            // Update the model if it wasn't set
            if (!$application->deposit_amount || $application->deposit_amount <= 0) {
                $application->deposit_amount = $amount;
                $application->payment_type = 'cash';
                $application->save();
            }
        } else {
            $amount = $application->deposit_amount;
        }

        if (!$amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount not found or invalid',
            ], 400);
        }

        try {
            // Create Paynow payment
            $email = $formData['formResponses']['email'] ?? $formData['formResponses']['emailAddress'] ?? 'no-email@example.com';
            $description = $paymentType === 'cash' 
                ? "Full Payment for Order {$referenceCode}"
                : "Deposit Payment for Application {$referenceCode}";

            $paynowResult = $this->paynowService->createPayment(
                $referenceCode,
                $amount,
                $email,
                $description
            );

            if ($paynowResult['success']) {
                // Store payment method
                $application->deposit_payment_method = $paymentMethod;
                $application->save();

                Log::info('Payment initiated', [
                    'reference_code' => $referenceCode,
                    'amount' => $amount,
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
            $paymentType = $application->payment_type;
            $nextStatus = $paymentType === 'cash' ? 'paid' : 'processing';

            // Mark as paid
            $application->update([
                'deposit_paid' => true,
                'deposit_paid_at' => now(),
                'deposit_transaction_id' => $paynowReference,
                'status' => $nextStatus,
                'current_step' => 'processing',
            ]);

            Log::info('Payment successful', [
                'reference_code' => $referenceCode,
                'transaction_id' => $paynowReference,
                'payment_type' => $paymentType
            ]);

            // 1. Create Purchase Order(s)
            try {
                $poService = app(\App\Services\PurchaseOrderService::class);
                $poService->createFromApplication($application);
                Log::info("Created Purchase Orders for paid application", ['reference' => $referenceCode]);
            } catch (\Exception $e) {
                Log::error("Failed to create Purchase Orders for paid application", [
                    'reference' => $referenceCode,
                    'error' => $e->getMessage()
                ]);
            }

            // 2. Create Delivery Tracking record
            try {
                // Parse form data to extract delivery details
                $formData = is_array($application->form_data) ? $application->form_data : json_decode($application->form_data, true);
                $responses = $formData['formResponses'] ?? [];

                // Determine recipient name
                $firstName = $responses['firstName'] ?? $responses['name'] ?? '';
                $surname = $responses['surname'] ?? '';
                $recipientName = trim("$firstName $surname");
                if (empty($recipientName)) {
                    $recipientName = $responses['businessName'] ?? 'Valued Customer';
                }

                // Determine address
                $address = $responses['residentialAddress'] ?? $responses['businessAddress'] ?? $formData['deliveryDetails']['deliveryAddress'] ?? 'Address requires update';
                $city = $responses['city'] ?? $responses['town'] ?? $formData['deliveryDetails']['city'] ?? '';
                if ($city) {
                    $address .= ", $city";
                }

                // Determine product info
                $productType = $formData['selectedCategory']['name'] ?? $formData['category'] ?? 'General Product';
                
                // Create the tracking record
                $tracking = \App\Models\DeliveryTracking::create([
                    'application_state_id' => $application->id,
                    'status' => 'processing', // Start as processing
                    'product_type' => $productType,
                    'courier_type' => 'Pending Assignment', // To be updated by admin
                    'delivery_address' => $address,
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $responses['mobile'] ?? $responses['phoneNumber'] ?? $responses['phone'] ?? null,
                    'client_national_id' => $responses['nationalIdNumber'] ?? null,
                    'status_history' => [
                        [
                            'status' => 'processing',
                            'notes' => $paymentType === 'cash' ? 'Full cash payment confirmed. Order processing started.' : 'Deposit payment confirmed. Order processing started.',
                            'updated_at' => now()->toISOString(),
                            'metadata' => [
                                'payment_ref' => $paynowReference,
                                'amount' => $application->deposit_amount,
                                'payment_type' => $paymentType
                            ]
                        ]
                    ]
                ]);

                Log::info("Created DeliveryTracking record", ['tracking_id' => $tracking->id, 'reference' => $referenceCode]);

                // Send Confirmation SMS
                try {
                     $smsService = app(\App\Services\SMSService::class);
                     $phone = $tracking->recipient_phone;
                     if ($phone) {
                         $msg = "Payment Received! Your BancoZim order ({$referenceCode}) is now being processed. You will be notified when it is dispatched.";
                         $smsService->sendSMS($phone, $msg);
                     }
                } catch (\Exception $e) {
                    Log::error("Failed to send payment confirmation SMS: " . $e->getMessage());
                }

            } catch (\Exception $e) {
                Log::error("Failed to initiate delivery for paid application", [
                    'reference' => $referenceCode,
                    'error' => $e->getMessage(),
                ]);
            }

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
    public function checkStatus(string $referenceCode)
    {
        return $this->getPaymentStatus($referenceCode);
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
