<?php

namespace App\Http\Controllers;

use App\Services\PaynowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    private PaynowService $paynowService;

    public function __construct(PaynowService $paynowService)
    {
        $this->paynowService = $paynowService;
    }

    /**
     * Initiate a loan deposit payment
     */
    public function initiateDeposit(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'email' => 'nullable|email',
                'phone' => 'nullable|string',
                'loanAmount' => 'nullable|numeric',
                'purchaseType' => 'nullable|string',
            ]);

            $amount = $request->input('amount');
            $email = $request->input('email', 'customer@example.com'); // Fallback if no email
            $reference = 'DEP-' . time() . '-' . rand(1000, 9999);

            // Create payment with Paynow
            $result = $this->paynowService->createPayment(
                $reference,
                $amount,
                $email,
                "Loan Deposit Payment"
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'redirectUrl' => $result['redirectUrl'],
                    'pollUrl' => $result['pollUrl'],
                    'reference' => $reference,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . ($result['error'] ?? 'Unknown error'),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Loan deposit initiation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
