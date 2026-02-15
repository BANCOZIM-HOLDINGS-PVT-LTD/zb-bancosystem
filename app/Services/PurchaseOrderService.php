<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    /**
     * Create a Purchase Order from an approved application.
     */
    public function createFromApplication(ApplicationState $application): ?PurchaseOrder
    {
        return DB::transaction(function () use ($application) {
            // check if PO already exists
            // Ideally we should have a link, but for now check by reference/metadata?
            // Or just proceed.

            $formData = $application->form_data;
            
            // Extract items from form data
            // Structure depends on form type (SSB vs Account Holder)
            // e.g., 'cartItems', 'products', 'selectedProduct'
            
            $items = $this->extractItems($formData);
            
            // Filter out items without product_id
            $validItems = array_filter($items, fn($item) => !empty($item['product_id']));

            if (empty($validItems)) {
                // If we have items but no product IDs (e.g. Cash Loan), we skip PO creation
                // but we don't treat it as an error.
                if (!empty($items)) {
                    Log::info('Skipping PO creation for application without product-based items (likely Cash Loan)', ['session_id' => $application->session_id]);
                    return null;
                }
                
                Log::warning('No items found for PO creation', ['session_id' => $application->session_id]);
                return null;
            }

            // Calculate totals
            $totalAmount = 0;
            foreach ($validItems as $item) {
                $totalAmount += ($item['price'] * $item['quantity']);
            }

            // Create PO
            $po = PurchaseOrder::create([
                'supplier_id' => null, // Needs logic to determine supplier
                'order_date' => now(),
                'status' => 'pending', // Initial status
                'total_amount' => $totalAmount,
                'notes' => 'Auto-generated from Application ' . $application->reference_code,
                'metadata' => [
                    'application_id' => $application->id,
                    'reference_code' => $application->reference_code,
                    'session_id' => $application->session_id,
                ]
            ]);

            // Create Items
            foreach ($validItems as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'], // Now guaranteed to exist
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                    // 'sku' => $item['sku'] ?? null,
                ]);
            }

            Log::info('Purchase Order created automatically', ['po_id' => $po->id, 'application_id' => $application->id]);

            return $po;
        });
    }

    private function extractItems(array $formData): array
    {
        $items = [];

        // case 1: 'cartItems' (Accessory/Grocery?)
        if (!empty($formData['cartItems']) && is_array($formData['cartItems'])) {
            foreach ($formData['cartItems'] as $item) {
                $items[] = [
                    'product_id' => $item['id'] ?? null, // Try to get ID
                    'name' => $item['name'] ?? 'Unknown Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                ];
            }
        }
        // case 2: Single product selection (SSB Loan often is cash or specific product)
        elseif (!empty($formData['selectedProduct'])) {
             // Logic to parse selectedProduct
             // Assuming selectedProduct might have an ID if it's a real product
             if (is_array($formData['selectedProduct'])) {
                 $items[] = [
                    'product_id' => $formData['selectedProduct']['id'] ?? null,
                    'name' => $formData['selectedProduct']['name'] ?? 'Selected Product',
                    'quantity' => 1,
                    'price' => $formData['selectedProduct']['price'] ?? ($formData['loanAmount'] ?? 0),
                 ];
             }
        }
        // case 3: 'business' or 'category' indicating a loan product
        elseif (!empty($formData['loanAmount'])) {
            // Cash loan?
             $items[] = [
                'product_id' => null, // No product ID for cash loans
                'name' => 'Cash Loan - ' . ($formData['loanPurpose'] ?? 'General'),
                'quantity' => 1,
                'price' => $formData['loanAmount'],
            ];
        }

        return $items;
    }
}
