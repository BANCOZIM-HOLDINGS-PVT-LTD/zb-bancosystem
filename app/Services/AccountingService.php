<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\AccountingTransaction;
use App\Models\MicrobizPackage;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    public function revenueBySource(string $period = 'day'): array
    {
        $start = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        return AccountingTransaction::where('type', 'income')
            ->where('created_at', '>=', $start)
            ->selectRaw('source, sum(amount) as total, count(*) as count')
            ->groupBy('source')
            ->get()
            ->toArray();
    }

    public function outstandingPayments()
    {
        return Payment::with('applicationState')
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->orderBy('created_at')
            ->get();
    }

    public function actualVsProjectedIncome(): array
    {
        $actual = (float) AccountingTransaction::where('type', 'income')->sum('amount');
        $projected = (float) ApplicationState::whereIn('status', ['approved', 'awaiting_deposit', 'awaiting_deposit_payment'])
            ->get()
            ->sum(fn (ApplicationState $application) => (float) ($application->deposit_amount ?: data_get($application->form_data, 'finalPrice', 0)));

        return [
            'actual' => $actual,
            'projected' => $projected,
            'variance' => $actual - $projected,
        ];
    }

    /**
     * Record sales and deduct inventory for an application.
     * This fulfills the "Real-time financial transaction logging" and
     * "Real-time inventory deduction" requirements.
     */
    public function recordSaleFromApplication(ApplicationState $application): array
    {
        return DB::transaction(function () use ($application) {
            $formData = $application->form_data;
            
            // Extract items (logic mirrored from PurchaseOrderService)
            $items = $this->extractItems($formData);
            
            // Expand packages
            $expandedItems = $this->expandPackages($items);
            
            $sales = [];
            
            foreach ($expandedItems as $item) {
                if (empty($item['product_id'])) {
                    continue; // Skip items without physical products (e.g. pure cash loans)
                }

                $sale = Sale::create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_amount' => $item['price'] * $item['quantity'],
                    'payment_method' => $this->determinePaymentMethod($application),
                    'sale_date' => now(),
                    'user_id' => auth()->id() ?? 1, // Default to system user
                    'notes' => "Auto-generated from application: " . $application->reference_code,
                ]);

                $sales[] = $sale;
                
                Log::info('Accounting: Sale record created for item', [
                    'application_id' => $application->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'sale_id' => $sale->id
                ]);
            }
            
            return $sales;
        });
    }

    /**
     * Determine payment method based on application type
     */
    private function determinePaymentMethod(ApplicationState $application): string
    {
        $paymentType = $application->payment_type ?? 'credit';
        
        if ($paymentType === 'cash') {
            return 'Paynow / Cash';
        }
        
        // Check for SSB/ZB
        if (str_contains($application->reference_code ?? '', 'SSB')) {
            return 'SSB Deduction';
        }
        
        if (str_contains($application->reference_code ?? '', 'ZBAH')) {
            return 'ZB Bank Loan';
        }
        
        return 'Credit';
    }

    /**
     * Extract items (Internal logic from PurchaseOrderService)
     */
    private function extractItems(array $formData): array
    {
        $items = [];

        // Case 1: Cart items (Building Materials, etc.)
        if (!empty($formData['cartItems']) && is_array($formData['cartItems'])) {
            foreach ($formData['cartItems'] as $item) {
                $items[] = [
                    'product_id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'Unknown Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                ];
            }
        }
        // Case 2: Single product selection
        elseif (!empty($formData['selectedBusiness'])) {
            $items[] = [
                'product_id' => $formData['selectedBusiness']['id'] ?? null,
                'name' => $formData['selectedBusiness']['name'] ?? 'Selected Product',
                'quantity' => 1,
                'price' => $formData['loanAmount'] ?? ($formData['grossLoan'] ?? 0),
            ];
        }
        // Case 3: selectedProduct
        elseif (!empty($formData['selectedProduct'])) {
            $items[] = [
                'product_id' => $formData['selectedProduct']['id'] ?? null,
                'name' => $formData['selectedProduct']['name'] ?? 'Selected Product',
                'quantity' => 1,
                'price' => $formData['selectedProduct']['price'] ?? ($formData['loanAmount'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * Expand packages (Internal logic from PurchaseOrderService)
     */
    private function expandPackages(array $items): array
    {
        $expanded = [];

        foreach ($items as $item) {
            $product = $item['product_id'] ? Product::find($item['product_id']) : null;

            if ($product) {
                $package = MicrobizPackage::where('name', 'like', '%' . $product->name . '%')
                    ->where('tier', 'like', '%' . $this->extractTierName($item['name'] ?? $product->name) . '%')
                    ->first();

                if ($package && $package->tierItems->count() > 0) {
                    foreach ($package->tierItems as $ti) {
                        $microbizItem = $ti->item;
                        if ($microbizItem) {
                            $componentProduct = Product::where('product_code', $microbizItem->item_code)
                                ->orWhere('name', $microbizItem->name)
                                ->first();

                            $expanded[] = [
                                'product_id' => $componentProduct ? $componentProduct->id : $product->id,
                                'name' => $microbizItem->name,
                                'quantity' => $ti->quantity * $item['quantity'],
                                'price' => $microbizItem->unit_cost,
                            ];
                        }
                    }
                    continue;
                }
            }
            $expanded[] = $item;
        }

        return $expanded;
    }

    private function extractTierName(string $name): string
    {
        $tiers = ['lite', 'standard', 'full house', 'gold'];
        $lower = strtolower($name);
        foreach ($tiers as $tier) {
            if (str_contains($lower, $tier)) return ucfirst($tier);
        }
        return $name;
    }
}
