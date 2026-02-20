<?php

use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Debug for Layers Project Seeder...\n";

try {
    DB::beginTransaction();

    // 1. Check Category
    $category = MicrobizCategory::firstOrCreate(
        ['name' => 'Chicken Projects'],
        ['emoji' => '🐔', 'domain' => 'microbiz']
    );
    echo "Category: {$category->name} (ID: {$category->id})\n";

    // 2. Check Subcategory
    $subcategory = MicrobizSubcategory::firstOrCreate(
        ['microbiz_category_id' => $category->id, 'name' => 'Layers'],
        ['image_url' => 'microbiz/layers_production.jpg']
    );
    echo "Subcategory: {$subcategory->name} (ID: {$subcategory->id})\n";

    // 3. Define Items
    $itemsData = [
        ['code' => 'MCPL025', 'name' => 'Birds (Day Old Chicks)', 'unit_cost' => 1.00],
    ];

    $items = [];
    foreach ($itemsData as $data) {
        $items[$data['code']] = MicrobizItem::updateOrCreate(
            ['item_code' => $data['code']],
            [
                'microbiz_subcategory_id' => $subcategory->id,
                'name' => $data['name'],
                'unit_cost' => $data['unit_cost'],
                'markup_percentage' => 40.00,
                'unit' => 'unit',
            ]
        );
        echo "Item Created: {$data['name']} (ID: {$items[$data['code']]->id})\n";
    }

    // 4. Define Packages
    $packages = [
        'lite' => [
            'name' => 'Lite Package',
            'price' => 263.20,
            'items' => [
                'MCPL025' => 50,
            ],
        ],
    ];

    foreach ($packages as $tier => $pkgData) {
        $package = MicrobizPackage::updateOrCreate(
            [
                'microbiz_subcategory_id' => $subcategory->id,
                'tier' => $tier,
            ],
            [
                'name' => $pkgData['name'],
                'price' => $pkgData['price'],
                'transport_method' => null,
                'courier' => null,
            ]
        );
        echo "Package: {$package->name} (Tier: {$tier}, ID: {$package->id})\n";

        $package->items()->detach();
        echo "Detached items.\n";

        foreach ($pkgData['items'] as $code => $qty) {
            if (isset($items[$code])) {
                $package->items()->attach($items[$code]->id, ['quantity' => $qty, 'is_delivered' => true]);
                echo "Attached Item: {$code} (Qty: {$qty})\n";
            } else {
                echo "ERROR: Item code not found: $code\n";
            }
        }
    }
    
    DB::commit(); // Rollback for test? No, commit to verify.
    echo "✅ Debug Script Completed Successfully.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
