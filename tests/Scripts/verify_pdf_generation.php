<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\View;

// 1. Fetch a MicroBiz Package (e.g. Broiler Production - Lite)
$package = MicrobizPackage::where('name', 'Lite Package')->first();

if (!$package) {
    echo "❌ Error: Lite Package not found. Did you run the seeder?\n";
    exit(1);
}

echo "✅ Found Package: {$package->name} - {$package->price}\n";

// 2. Mock Data for PDF Generation
$data = [
    'application_type' => 'microBiz',
    'first_name' => 'Test',
    'surname' => 'User',
    'id_number' => '12-345678-Z-90',
    'phone_number' => '0771234567',
    'date_of_birth' => '1990-01-01',
    'address' => '123 Test Street, Harare',
    'is_employed' => 'no',
    'business_type' => 'Broiler Production',
    'project_location' => 'Harare',
    
    // Product Details
    'product_name' => $package->name,
    'product_code' => $package->id, // For packages, ID is often used or empty
    'loan_amount' => $package->price,
    'credit_period' => 6,
    'monthly_payment' => $package->price / 6,
    
    // Crucial: define selected_package_id for Service to fetch items
    'selected_package_id' => $package->id,
];

// 3. Generate PDF View Output
try {
    
    // Re-create the logic from PDFGeneratorService for line items
    $lineItems = [];
    $items = $package->items;
    foreach ($items as $item) {
        $lineItems[] = [
            'name' => $item->name,
            'specification' => $item->specification,
            'code' => $item->item_code,
            'quantity' => $item->pivot->quantity,
            'price' => $item->pivot->quantity * $item->unit_cost // Just for estimation
        ];
    }
    
    $viewData = array_merge($data, ['lineItems' => $lineItems]);
    
    // Render Account Holders PDF
    $html = View::make('forms.account_holders_pdf', $viewData)->render();
    echo "✅ Account Holders PDF rendered successfully (Length: " . strlen($html) . " chars)\n";
    
    // Check key content in Account Holders PDF
    if (strpos($html, 'Birds (Day Old Chicks)') !== false) {
         echo "✅ 'Birds (Day Old Chicks)' found in Account Holders PDF.\n";
    } else {
         echo "❌ 'Birds (Day Old Chicks)' NOT found in Account Holders PDF.\n";
    }

    if (strpos($html, '(Qty: ') !== false) {
         echo "✅ Quantity found in Account Holders PDF.\n";
    } else {
         echo "❌ Quantity NOT found in Account Holders PDF.\n";
    }


    // Render SSB PDF
    $htmlSSB = View::make('forms.ssb_form_pdf', $viewData)->render();
    echo "✅ SSB PDF rendered successfully (Length: " . strlen($htmlSSB) . " chars)\n";
    
     // Check key content in SSB PDF
    if (strpos($htmlSSB, 'Birds (Day Old Chicks)') !== false) {
         echo "✅ 'Birds (Day Old Chicks)' found in SSB PDF.\n";
    } else {
         echo "❌ 'Birds (Day Old Chicks)' NOT found in SSB PDF.\n";
    }

} catch (\Exception $e) {
    echo "❌ PDF Generation Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
