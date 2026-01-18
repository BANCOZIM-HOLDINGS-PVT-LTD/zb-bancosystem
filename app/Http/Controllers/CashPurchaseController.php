<?php

namespace App\Http\Controllers;

use App\Models\CashPurchase;
use App\Models\Product;
use App\Services\PaynowService;
use App\Services\ZimbabweanIDValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CashPurchaseController extends Controller
{
    private PaynowService $paynowService;

    public function __construct(PaynowService $paynowService)
    {
        $this->paynowService = $paynowService;
    }

    /**
     * Show the cash purchase wizard
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type', 'personal'); // personal or microbiz
        $language = $request->query('language', 'en');
        $currency = $request->query('currency', 'USD');

        return Inertia::render('CashPurchase', [
            'purchaseType' => $type,
            'language' => $language,
            'currency' => $currency,
        ]);
    }

    /**
     * Store a new cash purchase (API endpoint)
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming data
            $validator = Validator::make($request->all(), [
                // Cart Items
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer', // Removed exists:products,id to allow fallback/dynamic products
                'items.*.name' => 'required|string',
                'items.*.cashPrice' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.category' => 'nullable|string',

                // Delivery
                'delivery.type' => ['required', Rule::in(['zimpost', 'gain_outlet'])],
                'delivery.depot' => 'required|string',
                'delivery.depotName' => 'nullable|string',
                'delivery.address' => 'required_if:delivery.type,zimpost|nullable|string',
                'delivery.city' => 'required_if:delivery.type,zimpost|nullable|string',
                'delivery.region' => 'required_if:delivery.type,gain_outlet|nullable|string',
                'delivery.includesMESystem' => 'nullable|boolean',
                'delivery.includesTraining' => 'nullable|boolean',

                // Customer
                'customer.nationalId' => 'required|string',
                'customer.fullName' => 'required|string|max:255',
                'customer.phone' => 'required|string|regex:/^(\+263|0)[0-9]{9}$/',
                'customer.email' => 'nullable|email',

                // Payment
                'payment.method' => 'required|string',
                'payment.amount' => 'required|numeric|min:0', // This should match calculated total
                'payment.transactionId' => 'nullable|string',
                'payment.currency' => 'nullable|string',

                // Purchase type
                'purchaseType' => ['required', Rule::in(['personal', 'microbiz'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Validate Zimbabwean ID
            $idValidation = ZimbabweanIDValidator::validate($data['customer']['nationalId']);
            if (!$idValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Zimbabwean National ID format',
                    'errors' => ['customer.nationalId' => ['Invalid ID format']],
                ], 422);
            }

            // Update user's National ID if authenticated and missing
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (empty($user->national_id)) {
                    $user->update(['national_id' => $idValidation['formatted']]);
                }
            }

            // Calculate totals
            $itemsTotal = 0;
            $items = $data['items'];
            foreach ($items as $item) {
                $itemsTotal += ($item['cashPrice'] * $item['quantity']);
            }

            // Calculate delivery fee
            $deliveryFee = $data['delivery']['type'] === 'zimpost' ? 10.00 : 0.00;

            // Calculate M&E and Training fees (Applied on total or per item? Assuming Total for now relative to hardware cost)
            // But wait, user said "add items so they can pay". Usually fees are fixed or % of total.
            // Existing logic: 10% of cash price.
            $isMicrobiz = $data['purchaseType'] === 'microbiz';
            $includesMESystem = $isMicrobiz && ($data['delivery']['includesMESystem'] ?? false);
            $includesTraining = $isMicrobiz && ($data['delivery']['includesTraining'] ?? false);

            $meSystemFee = $includesMESystem ? ($itemsTotal * 0.10) : 0.00;
            $trainingFee = $includesTraining ? ($itemsTotal * 0.055) : 0.00;

            $calculatedTotal = $itemsTotal + $deliveryFee + $meSystemFee + $trainingFee;
            
            // Verify payment amount matches (allow small diff for rounding?)
            // For now, let's trust the frontend passed amount but maybe log if different.
            // Actually, best to use calculated amount for security.
            
            // Create the cash purchase (Header)
            $cashPurchase = CashPurchase::create([
                'purchase_type' => $data['purchaseType'],

                // Product Summary (First item or Generic)
                'product_id' => null, // Multiple items
                'product_name' => count($items) . ' Items (' . $items[0]['name'] . '...)',
                'cash_price' => $itemsTotal, // Subtotal
                'loan_price' => 0,
                'category' => 'Mixed',

                // Customer
                'national_id' => $idValidation['formatted'],
                'full_name' => $data['customer']['fullName'],
                'phone' => $data['customer']['phone'],
                'email' => $data['customer']['email'] ?? null,

                // Delivery
                'delivery_type' => $data['delivery']['type'],
                'depot' => $data['delivery']['depot'],
                'depot_name' => $data['delivery']['depotName'] ?? null,
                'delivery_address' => $data['delivery']['address'] ?? null,
                'city' => $data['delivery']['city'] ?? null,
                'region' => $data['delivery']['region'] ?? null,
                'delivery_fee' => $deliveryFee,

                // MicroBiz Add-ons
                'includes_me_system' => $includesMESystem,
                'me_system_fee' => $meSystemFee,
                'includes_training' => $includesTraining,
                'training_fee' => $trainingFee,

                // Payment
                'payment_method' => $data['payment']['method'],
                'amount_paid' => $calculatedTotal, // Use calculated to be safe, or data['payment']['amount']
                'transaction_id' => $data['payment']['transactionId'] ?? null,
                'payment_status' => 'pending',

                // Status
                'status' => 'pending',
            ]);

            // Save Items
            foreach ($items as $item) {
                $cashPurchase->items()->create([
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'category' => $item['category'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['cashPrice'],
                    'total_price' => $item['cashPrice'] * $item['quantity'],
                    'metadata' => [
                        'original_loan_price' => $item['loanPrice'] ?? 0
                    ]
                ]);
            }

            // Payment Logic
            $redirectUrl = route('cash.purchase.success', ['purchase' => $cashPurchase->purchase_number]);
            $paymentMethod = $data['payment']['method'];
            $isPaynow = in_array($paymentMethod, ['paynow', 'ecocash', 'onemoney']);

            if ($isPaynow) {
                $description = "Order {$cashPurchase->purchase_number}: " . count($items) . " Items";
                
                $paynowResult = $this->paynowService->createPayment(
                    $cashPurchase->purchase_number,
                    $cashPurchase->amount_paid,
                    $cashPurchase->email ?? 'no-email@example.com',
                    $description
                );

                if ($paynowResult['success']) {
                    $redirectUrl = $paynowResult['redirectUrl'];
                    Log::info('Paynow transaction initiated', [
                        'purchase' => $cashPurchase->purchase_number,
                        'redirect' => $redirectUrl
                    ]);
                    
                    if (in_array($paymentMethod, ['ecocash', 'onemoney'])) {
                        $this->paynowService->initiateMobile(
                            $paynowResult['pollUrl'],
                            $cashPurchase->phone,
                            $paymentMethod
                        );
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to initiate payment: ' . ($paynowResult['error'] ?? 'Unknown error'),
                    ], 500);
                }
            }

            // Log the purchase creation
            Log::info('Cash purchase created', [
                'purchase_number' => $cashPurchase->purchase_number,
                'items_count' => count($items),
                'amount' => $cashPurchase->amount_paid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully',
                'data' => [
                    'purchase_number' => $cashPurchase->purchase_number,
                    'redirect_url' => $redirectUrl,
                    'is_redirect' => $isPaynow ?? false,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Cash purchase creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show success page with purchase details
     */
    public function success(string $purchaseNumber): Response
    {
        $purchase = CashPurchase::where('purchase_number', $purchaseNumber)->firstOrFail();

        return Inertia::render('CashPurchaseSuccess', [
            'purchase' => [
                'purchase_number' => $purchase->purchase_number,
                'purchase_type' => $purchase->purchase_type,

                // Product
                'product_name' => $purchase->product_name,
                'category' => $purchase->category,
                'cash_price' => $purchase->cash_price,

                // Customer
                'national_id' => $purchase->national_id,
                'full_name' => $purchase->full_name,
                'phone' => $purchase->phone,
                'email' => $purchase->email,

                // Delivery
                'delivery_type' => $purchase->delivery_type,
                'depot_name' => $purchase->depot_name,
                'region' => $purchase->region,
                'city' => $purchase->city,
                'delivery_address' => $purchase->delivery_address,

                // Payment
                'amount_paid' => $purchase->amount_paid,
                'transaction_id' => $purchase->transaction_id,
                'payment_status' => $purchase->payment_status,

                // Timestamps
                'created_at' => $purchase->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Show error page
     */
    public function error(Request $request): Response
    {
        $errorMessage = $request->query('message', 'We encountered an issue processing your purchase');
        $errorCode = $request->query('code', 'PURCHASE_FAILED');
        $type = $request->query('type', 'personal');

        return Inertia::render('CashPurchaseError', [
            'error' => [
                'message' => $errorMessage,
                'code' => $errorCode,
                'details' => 'Please try again or contact our support team for assistance.',
            ],
            'type' => $type,
        ]);
    }

    /**
     * Track purchase by National ID (API endpoint)
     */
    public function track(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'National ID is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nationalId = $request->input('national_id');

        // Validate ID format
        $idValidation = ZimbabweanIDValidator::validate($nationalId);
        if (!$idValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid National ID format',
            ], 422);
        }

        // Find purchases by national ID
        $purchases = CashPurchase::where('national_id', $idValidation['formatted'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'purchase_number' => $purchase->purchase_number,
                    'purchase_type' => $purchase->purchase_type_label,
                    'product_name' => $purchase->product_name,
                    'amount_paid' => $purchase->formatted_amount_paid,
                    'status' => $purchase->status,
                    'status_label' => $purchase->status_label,
                    'delivery_type' => $purchase->delivery_type_label,
                    'payment_status' => $purchase->payment_status,
                    'payment_status_label' => $purchase->payment_status_label,
                    'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                ];
            });

        if ($purchases->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No purchases found for this National ID',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    /**
     * Show purchase details by purchase number (API endpoint)
     */
    public function show(string $purchaseNumber)
    {
        $purchase = CashPurchase::where('purchase_number', $purchaseNumber)->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_number' => $purchase->purchase_number,
                'purchase_type' => $purchase->purchase_type_label,
                'product_name' => $purchase->product_name,
                'category' => $purchase->category,
                'cash_price' => $purchase->cash_price,
                'amount_paid' => $purchase->amount_paid,
                'customer' => [
                    'national_id' => $purchase->national_id,
                    'full_name' => $purchase->full_name,
                    'phone' => $purchase->phone,
                    'email' => $purchase->email,
                ],
                'delivery' => [
                    'type' => $purchase->delivery_type,
                    'type_label' => $purchase->delivery_type_label,
                    'depot_name' => $purchase->depot_name,
                    'region' => $purchase->region,
                    'city' => $purchase->city,
                    'address' => $purchase->delivery_address,
                    'swift_tracking_number' => $purchase->swift_tracking_number,
                ],
                'status' => $purchase->status,
                'status_label' => $purchase->status_label,
                'payment_status' => $purchase->payment_status,
                'payment_status_label' => $purchase->payment_status_label,
                'transaction_id' => $purchase->transaction_id,
                'status_history' => $purchase->status_history,
                'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                'paid_at' => $purchase->paid_at?->format('Y-m-d H:i:s'),
                'dispatched_at' => $purchase->dispatched_at?->format('Y-m-d H:i:s'),
                'delivered_at' => $purchase->delivered_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}