<?php

namespace App\Http\Controllers;

use App\Services\PaynowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaynowController extends Controller
{
    private PaynowService $paynowService;

    public function __construct(PaynowService $paynowService)
    {
        $this->paynowService = $paynowService;
    }

    /**
     * Initiate a mobile payment (EcoCash/OneMoney)
     */
    public function initiateMobile(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'email' => 'required|email',
            'method' => 'required|in:ecocash,onemoney',
            'reference' => 'nullable|string',
        ]);

        $reference = $request->reference ?? 'PAY-' . time();

        // 1. Create Payment Transaction
        $initResult = $this->paynowService->createPayment(
            $reference,
            $request->amount,
            $request->email,
            'Mobile Payment via ' . ucfirst($request->method)
        );

        if (!$initResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $initResult['error'] ?? 'Failed to initiate transaction',
            ], 400);
        }

        $pollUrl = $initResult['pollUrl'];

        // 2. Trigger Mobile Prompt
        $mobileResult = $this->paynowService->initiateMobile(
            $pollUrl, 
            $request->phone, 
            $request->method
        );

        if (!$mobileResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $mobileResult['error'] ?? 'Failed to trigger mobile prompt',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'poll_url' => $pollUrl,
            'instructions' => $mobileResult['instructions'] ?? 'Please check your phone for the payment prompt.',
            'reference' => $reference,
        ]);
    }

    /**
     * Check payment status via Poll URL
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'poll_url' => 'required|url',
        ]);

        $status = $this->paynowService->pollPaymentStatus($request->poll_url);

        return response()->json($status);
    }
}
