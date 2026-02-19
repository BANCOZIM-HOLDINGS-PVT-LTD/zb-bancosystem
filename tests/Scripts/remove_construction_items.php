<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Product;

// Broad search for construction-related terms
$terms = ['Construction', 'Building', 'Fencing', 'Cement', 'Hardware', 'Plumbing', 'Electrical', 'Paint', 'Timber', 'Roofing', 'Brick', 'Aggregates', 'Sand', 'Stone'];

$query = ProductCategory::query();
foreach ($terms as $term) {
    // Group OR conditions
    $query->orWhere('name', 'LIKE', "%{$term}%");
}
$categories = $query->get();

echo "Found " . $categories->count() . " categories to delete:\n";

foreach ($categories as $cat) {
    echo "- Deleting Category: {$cat->name} (ID: {$cat->id})\n";
    
    // Get subcategories
    $subCategories = ProductSubCategory::where('product_category_id', $cat->id)->get();
    foreach ($subCategories as $sub) {
        // Delete products
        $deletedProducts = Product::where('product_sub_category_id', $sub->id)->delete();
        echo "  - Deleted SubCategory: {$sub->name} (ID: {$sub->id}) & {$deletedProducts} products\n";
        
        $sub->delete();
    }
    $cat->delete();
}

echo "Deletion complete.\n";
