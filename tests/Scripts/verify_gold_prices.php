<?php

use App\Models\MicrobizPackage;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Output to file to avoid truncation issues
$outputFile = __DIR__ . '/../../verification_results.txt';
$fp = fopen($outputFile, 'w');

function logOutput($fp, $message) {
    fwrite($fp, $message);
    echo $message; // Keep echo for local dev if needed
}

logOutput($fp, "\n--- START VERIFICATION ---\n");
logOutput($fp, "Verifying Gold Package Prices...\n");

$goldPackages = MicrobizPackage::where('tier', 'gold')->get();
$incorrect = 0;
$totalGold = 0;

foreach ($goldPackages as $pkg) {
    $totalGold++;
    if ((float)$pkg->price !== 1700.00) {
        logOutput($fp, "❌ Package [{$pkg->id}] {$pkg->name} (Subcat: {$pkg->microbiz_subcategory_id}) has price {$pkg->price}, expected 1700.00\n");
        $incorrect++;
    }
}

// Check ProductPackageSize for 'Gold Package'
$goldSizes = DB::table('product_package_sizes')->where('name', 'Gold Package')->get();
foreach ($goldSizes as $size) {
    $totalGold++;
    if ((float)$size->custom_price !== 1700.00) {
        logOutput($fp, "❌ Product Package Size [{$size->id}] for Product {$size->product_id} has price {$size->custom_price}, expected 1700.00\n");
        $incorrect++;
    }
}

if ($incorrect === 0 && $totalGold > 0) {
    logOutput($fp, "✅ SUCCESS: All $totalGold Gold Packages/Sizes are priced at $1700.00\n");
} elseif ($totalGold === 0) {
    logOutput($fp, "⚠️ WARNING: No Gold Packages found at all!\n");
} else {
    logOutput($fp, "❌ FAILURE: Found $incorrect Gold Packages with incorrect prices.\n");
}

logOutput($fp, "\nChecking for Product Duplicates...\n");
// simplistic check: group by name and subcategory
$duplicates = DB::table('products')
    ->select('name', 'product_sub_category_id', DB::raw('count(*) as total'))
    ->groupBy('name', 'product_sub_category_id')
    ->having('total', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    logOutput($fp, "✅ SUCCESS: No duplicate products found.\n");
} else {
    logOutput($fp, "❌ FAILURE: Found duplicates:\n");
    foreach ($duplicates as $dup) {
        logOutput($fp, "- {$dup->name} (SubCat ID: {$dup->product_sub_category_id}): {$dup->total} records\n");
    }
}

logOutput($fp, "\nTotal Products: " . Product::count() . "\n");
logOutput($fp, "--- END VERIFICATION ---\n");
fclose($fp);
