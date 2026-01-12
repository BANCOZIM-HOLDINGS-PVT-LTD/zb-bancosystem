<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceSMSController extends Controller
{
    private SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate invoice number from national ID (remove dashes)
     */
    private function generateInvoiceNumber(string $nationalId): string
    {
        return preg_replace('/-/', '', $nationalId);
    }

    /**
     * Send SMS notification for hire purchase application
     * Called when user proceeds to form from ApplicationSummary
     */
    public function sendHirePurchaseSMS(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string',
            'phone' => 'required|string',
            'product_name' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $nationalId = $request->input('national_id');
        $phone = $request->input('phone');
        $productName = $request->input('product_name');
        $amount = $request->input('amount');
        $currency = $request->input('currency', 'USD');

        $invoiceNumber = $this->generateInvoiceNumber($nationalId);
        
        // Format amount with currency
        $formattedAmount = $currency === 'ZiG' 
            ? "ZiG" . number_format($amount, 2)
            : "$" . number_format($amount, 2);

        $message = "You are about to apply for a purchase of {$productName} Invoice Number {$invoiceNumber} costing {$formattedAmount}";

        try {
            $sent = $this->smsService->sendSMS($phone, $message);

            Log::info('Hire purchase SMS notification sent', [
                'phone' => $phone,
                'invoice_number' => $invoiceNumber,
                'product' => $productName,
                'amount' => $amount,
                'sent' => $sent
            ]);

            return response()->json([
                'success' => true,
                'invoice_number' => $invoiceNumber,
                'message' => 'SMS notification sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send hire purchase SMS', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber, // Still return invoice number even if SMS fails
                'message' => 'SMS sending failed, but invoice number generated'
            ]);
        }
    }

    /**
     * Send SMS notification for cash purchase
     * Called when user proceeds to payment from CheckoutStep
     */
    public function sendCashPurchaseSMS(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string',
            'phone' => 'required|string',
            'product_name' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $nationalId = $request->input('national_id');
        $phone = $request->input('phone');
        $productName = $request->input('product_name');
        $amount = $request->input('amount');
        $currency = $request->input('currency', 'USD');

        $invoiceNumber = $this->generateInvoiceNumber($nationalId);
        
        // Format amount with currency
        $formattedAmount = $currency === 'ZiG' 
            ? "ZiG" . number_format($amount, 2)
            : "$" . number_format($amount, 2);

        $message = "You are about to purchase {$productName} Invoice Number {$invoiceNumber} costing {$formattedAmount}";

        try {
            $sent = $this->smsService->sendSMS($phone, $message);

            Log::info('Cash purchase SMS notification sent', [
                'phone' => $phone,
                'invoice_number' => $invoiceNumber,
                'product' => $productName,
                'amount' => $amount,
                'sent' => $sent
            ]);

            return response()->json([
                'success' => true,
                'invoice_number' => $invoiceNumber,
                'message' => 'SMS notification sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send cash purchase SMS', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber, // Still return invoice number even if SMS fails
                'message' => 'SMS sending failed, but invoice number generated'
            ]);
        }
    }
}
