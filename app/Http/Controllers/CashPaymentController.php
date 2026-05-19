<?php

namespace App\Http\Controllers;

use App\Events\PaymentReceived;
use App\Models\ApplicationState;
use App\Models\CashPayment;
use App\Models\DeliveryTracking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashPaymentController extends Controller
{
    public function createIntent(Request $request)
    {
        $data = $request->validate([
            'reference_code' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $application = ApplicationState::where('reference_code', $data['reference_code'])->firstOrFail();
        $formData = $application->form_data ?? [];
        $amount = $data['amount']
            ?? $application->deposit_amount
            ?? $formData['finalPrice']
            ?? $formData['amount']
            ?? 0;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cash payment amount could not be determined.',
            ], 422);
        }

        $payment = Payment::updateOrCreate(
            ['reference' => $application->reference_code],
            [
                'application_state_id' => $application->id,
                'provider' => 'cash',
                'method' => 'cash',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => Payment::STATUS_PENDING,
                'metadata' => ['payment_type' => 'cash'],
            ]
        );

        $cashPayment = CashPayment::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'application_state_id' => $application->id,
                'received_amount' => $amount,
            ]
        );

        return response()->json([
            'success' => true,
            'payment_reference' => $payment->reference,
            'cashier_reference' => $cashPayment->cashier_reference,
            'amount' => $payment->amount,
        ]);
    }

    public function verify(Request $request, CashPayment $cashPayment)
    {
        $data = $request->validate([
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($cashPayment, $data) {
            $payment = $cashPayment->payment;
            $receiptNumber = $data['receipt_number'] ?? Payment::generateReceiptNumber();

            $cashPayment->update([
                'receipt_number' => $receiptNumber,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejected_at' => null,
                'notes' => $data['notes'] ?? $cashPayment->notes,
            ]);

            $payment->update(['receipt_number' => $receiptNumber]);
            $payment->markPaid($cashPayment->cashier_reference, ['verified_cash_payment_id' => $cashPayment->id]);

            $application = $cashPayment->applicationState;
            if ($application) {
                $application->update([
                    'payment_type' => 'cash',
                    'deposit_paid' => true,
                    'deposit_paid_at' => now(),
                    'deposit_transaction_id' => $cashPayment->cashier_reference,
                    'deposit_payment_method' => 'cash',
                    'status' => 'paid',
                    'current_step' => 'processing',
                ]);

                // Auto-create delivery tracking record if one doesn't exist yet
                if (!$application->delivery) {
                    $formData     = $application->form_data ?? [];
                    $responses    = $formData['formResponses'] ?? [];
                    $delivery     = $formData['deliverySelection'] ?? [];

                    $clientName = trim(
                        ($responses['firstName'] ?? '') . ' ' . ($responses['surname'] ?? '')
                    );

                    $depot = '';
                    if (!empty($delivery['city'])) {
                        $depot = $delivery['city'] . ' (' . ($delivery['agent'] ?? 'Zim Post Office') . ')';
                    } elseif (!empty($delivery['depot'])) {
                        $depot = $delivery['depot'];
                    }

                    $product = $formData['business']
                        ?? $formData['selectedBusiness']['name']
                        ?? $formData['category']
                        ?? 'Cash Order';

                    DeliveryTracking::create([
                        'application_state_id' => $application->id,
                        'status'               => 'pending',
                        'product_type'         => $product,
                        'recipient_name'       => $clientName ?: 'N/A',
                        'recipient_phone'      => $responses['mobile'] ?? $responses['cellNumber'] ?? '',
                        'client_national_id'   => $responses['nationalIdNumber'] ?? $responses['idNumber'] ?? '',
                        'delivery_depot'       => $depot,
                        'courier_type'         => $delivery['agent'] ?? null,
                        'admin_notes'          => 'Auto-created on cash payment verification. Cashier ref: ' . $cashPayment->cashier_reference,
                    ]);

                    Log::info('DeliveryTracking auto-created for cash order', [
                        'application_id'       => $application->id,
                        'reference_code'       => $application->reference_code,
                        'cashier_reference'    => $cashPayment->cashier_reference,
                    ]);
                }
            }

            event(new PaymentReceived($payment));
        });

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, CashPayment $cashPayment)
    {
        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $cashPayment->update([
            'rejected_at' => now(),
            'notes' => $data['notes'] ?? $cashPayment->notes,
        ]);

        $cashPayment->payment->markFailed(Payment::STATUS_FAILED, [
            'cash_payment_rejected_at' => now()->toISOString(),
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }
}
