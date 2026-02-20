<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MicrobizPackage;
use App\Models\MicrobizSubcategory;

echo "--- Debugging Database Connection ---\n";

$hosts = ['127.0.0.1', 'localhost'];
foreach ($hosts as $host) {
    echo "Testing connection to {$host}...\n";
    config(['database.connections.mysql.host' => $host]);
    try {
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::connection()->getPdo();
        echo "✅ Connected to {$host} successfully!\n";
        
        echo "--- Checking Microbiz Layers Data ---\n";
        $subcategory = MicrobizSubcategory::where('name', 'Layers')->first();
        if ($subcategory) {
            echo "Found Subcategory: {$subcategory->name} (ID: {$subcategory->id})\n";
            $count = MicrobizPackage::where('microbiz_subcategory_id', $subcategory->id)->count();
            echo "Found {$count} packages for Layers.\n";
            if ($count > 0) {
                $packages = MicrobizPackage::where('microbiz_subcategory_id', $subcategory->id)->get();
                foreach ($packages as $pkg) {
                    echo "- Package: {$pkg->name} (Tier: {$pkg->tier})\n";
                }
            }
        } else {
            echo "Subcategory 'Layers' NOT found.\n";
            $catCount = \App\Models\MicrobizCategory::count();
            echo "Total Categories: {$catCount}\n";
        }
        break; // Stop after successful connection
    } catch (\Exception $e) {
        echo "❌ Failed to connect to {$host}: " . $e->getMessage() . "\n";
    }
}
