<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\LoanTerm;
use App\Models\ProductInventory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all existing products
        $products = Product::all();

        foreach ($products as $product) {
            // Create inventory record for each product
            ProductInventory::create([
                'product_id' => $product->id,
                'stock_quantity' => rand(50, 500),
                'reserved_quantity' => 0,
                'minimum_stock_level' => 10,
                'maximum_stock_level' => 1000,
                'reorder_point' => 25,
                'reorder_quantity' => 100,
                'cost_price' => $product->base_price * 0.7, // 30% markup
                'selling_price' => $product->base_price,
                'markup_percentage' => 30.00,
                'is_active' => true,
                'is_featured' => rand(0, 1) == 1,
                'availability_status' => 'available',
                'warehouse_location' => 'Main Warehouse - Section ' . chr(65 + rand(0, 5)),
                'notes' => 'Auto-generated inventory record for ' . $product->name,
            ]);

            // Create multiple loan terms for each product
            $this->createLoanTermsForProduct($product);
        }
    }

    private function createLoanTermsForProduct(Product $product): void
    {
        $basePrice = $product->base_price;
        $productName = $product->name;

        // Standard loan terms based on product price range
        $loanTermsData = [];

        if ($basePrice <= 1000) {
            // Short-term, lower amount products
            $loanTermsData = [
                [
                    'name' => 'Quick 6-Month Plan',
                    'description' => 'Fast approval, 6-month repayment for ' . $productName,
                    'duration_months' => 6,
                    'interest_rate' => 15.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.5,
                    'maximum_amount' => $basePrice * 2,
                    'processing_fee' => 50.00,
                    'is_default' => true,
                ],
                [
                    'name' => 'Standard 12-Month Plan',
                    'description' => 'Standard repayment plan for ' . $productName,
                    'duration_months' => 12,
                    'interest_rate' => 12.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.5,
                    'maximum_amount' => $basePrice * 3,
                    'processing_fee' => 75.00,
                ],
                [
                    'name' => 'Extended 18-Month Plan',
                    'description' => 'Lower monthly payments for ' . $productName,
                    'duration_months' => 18,
                    'interest_rate' => 14.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.5,
                    'maximum_amount' => $basePrice * 2.5,
                    'processing_fee' => 100.00,
                ],
            ];
        } elseif ($basePrice <= 5000) {
            // Medium-term, medium amount products
            $loanTermsData = [
                [
                    'name' => 'Standard 12-Month Plan',
                    'description' => 'Standard financing for ' . $productName,
                    'duration_months' => 12,
                    'interest_rate' => 10.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.3,
                    'maximum_amount' => $basePrice * 2,
                    'processing_fee' => 150.00,
                    'is_default' => true,
                ],
                [
                    'name' => 'Extended 24-Month Plan',
                    'description' => 'Extended repayment for ' . $productName,
                    'duration_months' => 24,
                    'interest_rate' => 12.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.3,
                    'maximum_amount' => $basePrice * 2.5,
                    'processing_fee' => 200.00,
                ],
                [
                    'name' => 'Premium 36-Month Plan',
                    'description' => 'Low monthly payments for ' . $productName,
                    'duration_months' => 36,
                    'interest_rate' => 14.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.3,
                    'maximum_amount' => $basePrice * 3,
                    'processing_fee' => 250.00,
                ],
            ];
        } else {
            // Long-term, high amount products
            $loanTermsData = [
                [
                    'name' => 'Business 24-Month Plan',
                    'description' => 'Business financing for ' . $productName,
                    'duration_months' => 24,
                    'interest_rate' => 8.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.2,
                    'maximum_amount' => $basePrice * 2,
                    'processing_fee' => 300.00,
                    'is_default' => true,
                ],
                [
                    'name' => 'Extended 36-Month Plan',
                    'description' => 'Extended business financing for ' . $productName,
                    'duration_months' => 36,
                    'interest_rate' => 10.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.2,
                    'maximum_amount' => $basePrice * 2.5,
                    'processing_fee' => 400.00,
                ],
                [
                    'name' => 'Long-term 48-Month Plan',
                    'description' => 'Long-term financing for ' . $productName,
                    'duration_months' => 48,
                    'interest_rate' => 12.00,
                    'interest_type' => 'reducing',
                    'minimum_amount' => $basePrice * 0.2,
                    'maximum_amount' => $basePrice * 3,
                    'processing_fee' => 500.00,
                ],
            ];
        }

        // Create loan terms for this product
        foreach ($loanTermsData as $termData) {
            LoanTerm::create(array_merge([
                'product_id' => $product->id,
                'calculation_method' => 'standard',
                'payment_frequency' => 'monthly',
                'processing_fee_type' => 'fixed',
                'insurance_rate' => 2.00,
                'insurance_required' => true,
                'early_payment_penalty' => 1.00,
                'late_payment_penalty' => 5.00,
                'grace_period_days' => 7,
                'is_active' => true,
                'effective_date' => now(),
                'conditions' => [
                    'minimum_age' => 18,
                    'employment_required' => true,
                    'credit_check' => true,
                    'collateral_required' => $basePrice > 5000,
                ],
                'metadata' => [
                    'auto_generated' => true,
                    'product_category' => $product->subCategory->name ?? 'General',
                    'risk_level' => $basePrice > 5000 ? 'medium' : 'low',
                ],
            ], $termData));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all auto-generated loan terms and inventory
        LoanTerm::where('metadata->auto_generated', true)->delete();
        ProductInventory::truncate();
    }
};
