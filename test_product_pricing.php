<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "--- Testing Product Pricing Logic ---\n";

// 1. Create a dummy product with Small Truck ($20)
$p1 = new Product([
    'name' => 'Test Cement',
    'base_price' => 100.00,
    'markup_percentage' => 0,
    'transport_method' => 'small_truck',
]);

echo "\nTest 1: Small Truck ($20)\n";
echo "Base Price: {$p1->base_price}\n";
echo "TS Cost: {$p1->ts_cost}\n";
echo "TC Cost: {$p1->tc_cost} (10% of 100)\n";
echo "Selling Price: {$p1->selling_price}\n";
$expected1 = 100 + 0 + 20 + 10; // 130
if ($p1->selling_price == $expected1) {
    echo "✅ PASS: Expected $expected1, got {$p1->selling_price}\n";
} else {
    echo "❌ FAIL: Expected $expected1, got {$p1->selling_price}\n";
}

// 2. Test InDrive ($5) + Markup
$p2 = new Product([
    'name' => 'Test Phone',
    'base_price' => 200.00,
    'markup_percentage' => 10, // $20
    'transport_method' => 'indrive',
]);

echo "\nTest 2: InDrive ($5) + 10% Markup\n";
echo "Base Price: {$p2->base_price}\n";
echo "Markup: " . ($p2->base_price * 0.10) . "\n";
echo "TS Cost: {$p2->ts_cost}\n";
echo "TC Cost: {$p2->tc_cost} (10% of 200)\n";
echo "Selling Price: {$p2->selling_price}\n";
$expected2 = 200 + 20 + 5 + 20; // 245
if ($p2->selling_price == $expected2) {
    echo "✅ PASS: Expected $expected2, got {$p2->selling_price}\n";
} else {
    echo "❌ FAIL: Expected $expected2, got {$p2->selling_price}\n";
}

// 3. Test Starlink (Heavy) - Check seeder data if possible
$starlink = Product::where('name', 'like', '%Starlink%')->first();
if ($starlink) {
    echo "\nTest 3: Seeder Data (Starlink)\n";
    echo "Name: {$starlink->name}\n";
    echo "Base: {$starlink->base_price}\n";
    echo "Transport: {$starlink->transport_method}\n";
    echo "TS: {$starlink->ts_cost}\n";
    echo "Selling: {$starlink->selling_price}\n";
} else {
    echo "\nTest 3: Starlink not found in DB\n";
}
