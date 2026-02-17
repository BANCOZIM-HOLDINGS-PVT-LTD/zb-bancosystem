<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\MicrobizPackage;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    /**
     * Create Purchase Order(s) from an approved application.
     * Groups items by supplier and creates one PO per supplier.
     * Expands MicroBiz packages into their constituent products.
     *
     * @return PurchaseOrder[]|null
     */
    public function createFromApplication(ApplicationState $application): ?array
    {
        return DB::transaction(function () use ($application) {
            $formData = $application->form_data;

            // Extract raw items from form data
            $rawItems = $this->extractItems($formData);

            // Filter out items without product_id (e.g., cash loans)
            $validItems = array_filter($rawItems, fn($item) => !empty($item['product_id']));

            if (empty($validItems)) {
                if (!empty($rawItems)) {
                    Log::info('Skipping PO creation for application without product-based items (likely Cash Loan)', [
                        'session_id' => $application->session_id,
                    ]);
                    return null;
                }

                Log::warning('No items found for PO creation', [
                    'session_id' => $application->session_id,
                ]);
                return null;
            }

            // Expand MicroBiz packages into component products
            $expandedItems = $this->expandPackages($validItems);

            // Group items by supplier
            $grouped = $this->groupBySupplier($expandedItems);

            $purchaseOrders = [];

            foreach ($grouped as $supplierId => $items) {
                // Calculate totals
                $totalAmount = 0;
                foreach ($items as $item) {
                    $totalAmount += ($item['price'] * $item['quantity']);
                }

                // Create PO for this supplier
                $po = PurchaseOrder::create([
                    'supplier_id' => $supplierId ?: null,
                    'order_date' => now(),
                    'status' => 'pending',
                    'subtotal' => $totalAmount,
                    'tax_amount' => 0,
                    'shipping_cost' => 0,
                    'total_amount' => $totalAmount,
                    'notes' => 'Auto-generated from Application ' . $application->reference_code,
                    'metadata' => [
                        'application_id' => $application->id,
                        'reference_code' => $application->reference_code,
                        'session_id' => $application->session_id,
                    ],
                ]);

                // Create items
                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'application_id' => $application->id,
                        'quantity_ordered' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'total_price' => $item['price'] * $item['quantity'],
                        'notes' => $item['product_code'] ? "Code: {$item['product_code']}" : null,
                    ]);
                }

                $purchaseOrders[] = $po;

                Log::info('Purchase Order created', [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_id' => $supplierId,
                    'items_count' => count($items),
                    'application_id' => $application->id,
                ]);
            }

            return $purchaseOrders;
        });
    }

    /**
     * Extract items from form data.
     * Handles cart items (Building Materials), single product, and loan-based flows.
     */
    private function extractItems(array $formData): array
    {
        $items = [];

        // Case 1: Cart items (Building Materials, etc.)
        if (!empty($formData['cartItems']) && is_array($formData['cartItems'])) {
            foreach ($formData['cartItems'] as $item) {
                $productId = $item['id'] ?? null;
                $product = $productId ? Product::find($productId) : null;

                $items[] = [
                    'product_id' => $productId,
                    'product_code' => $product->product_code ?? ($item['product_code'] ?? null),
                    'name' => $item['name'] ?? 'Unknown Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'supplier_id' => $product->supplier_id ?? null,
                ];
            }
        }
        // Case 2: Single product selection (selectedBusiness from wizard)
        elseif (!empty($formData['selectedBusiness'])) {
            $selectedBusiness = $formData['selectedBusiness'];
            $productId = $selectedBusiness['id'] ?? null;
            $product = $productId ? Product::find($productId) : null;

            $items[] = [
                'product_id' => $productId,
                'product_code' => $product->product_code ?? ($formData['productCode'] ?? ($selectedBusiness['product_code'] ?? null)),
                'name' => $selectedBusiness['name'] ?? 'Selected Product',
                'quantity' => 1,
                'price' => $formData['loanAmount'] ?? ($formData['grossLoan'] ?? 0),
                'supplier_id' => $product->supplier_id ?? null,
            ];
        }
        // Case 3: selectedProduct (legacy/SSB)
        elseif (!empty($formData['selectedProduct'])) {
            if (is_array($formData['selectedProduct'])) {
                $productId = $formData['selectedProduct']['id'] ?? null;
                $product = $productId ? Product::find($productId) : null;

                $items[] = [
                    'product_id' => $productId,
                    'product_code' => $product->product_code ?? null,
                    'name' => $formData['selectedProduct']['name'] ?? 'Selected Product',
                    'quantity' => 1,
                    'price' => $formData['selectedProduct']['price'] ?? ($formData['loanAmount'] ?? 0),
                    'supplier_id' => $product->supplier_id ?? null,
                ];
            }
        }
        // Case 4: Cash loan (no physical product)
        elseif (!empty($formData['loanAmount'])) {
            $items[] = [
                'product_id' => null,
                'product_code' => null,
                'name' => 'Cash Loan - ' . ($formData['loanPurpose'] ?? 'General'),
                'quantity' => 1,
                'price' => $formData['loanAmount'],
                'supplier_id' => null,
            ];
        }

        return $items;
    }

    /**
     * Expand MicroBiz packages into their constituent products.
     * If a product is a MicroBiz package, replace it with the individual products.
     */
    private function expandPackages(array $items): array
    {
        $expanded = [];

        foreach ($items as $item) {
            $product = $item['product_id'] ? Product::find($item['product_id']) : null;

            // Check if this product has a MicroBiz package
            if ($product) {
                $package = MicrobizPackage::whereHas('products', function ($q) use ($product) {
                    $q->where('products.id', $product->id);
                })->first();

                // Alternative: check by name pattern (Lite Package, Standard Package, etc.)
                if (!$package) {
                    $package = MicrobizPackage::where('name', 'like', '%' . $this->extractTierName($product->name) . '%')->first();
                }

                if ($package && $package->packageProducts->count() > 0) {
                    // Expand: replace the package with its constituent products
                    foreach ($package->packageProducts as $pp) {
                        $componentProduct = $pp->product;
                        if ($componentProduct) {
                            $expanded[] = [
                                'product_id' => $componentProduct->id,
                                'product_code' => $componentProduct->product_code,
                                'name' => $componentProduct->name,
                                'quantity' => $pp->quantity * $item['quantity'],
                                'price' => $pp->unit_cost ?? $componentProduct->unit_price,
                                'supplier_id' => $componentProduct->supplier_id,
                            ];
                        }
                    }

                    Log::info('Expanded MicroBiz package', [
                        'package' => $package->name,
                        'components' => $package->packageProducts->count(),
                    ]);

                    continue; // Skip adding the package itself
                }
            }

            // Not a package — keep as-is
            $expanded[] = $item;
        }

        return $expanded;
    }

    /**
     * Extract tier name from product name (e.g., "MicroBiz Lite Package" → "Lite")
     */
    private function extractTierName(string $name): string
    {
        $tiers = ['lite', 'standard', 'full house', 'gold'];
        $lower = strtolower($name);

        foreach ($tiers as $tier) {
            if (str_contains($lower, $tier)) {
                return ucfirst($tier);
            }
        }

        return $name;
    }

    /**
     * Group items by supplier ID.
     * Items without a supplier are grouped under key 0.
     */
    private function groupBySupplier(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $supplierId = $item['supplier_id'] ?? 0;
            $grouped[$supplierId][] = $item;
        }

        return $grouped;
    }
}
