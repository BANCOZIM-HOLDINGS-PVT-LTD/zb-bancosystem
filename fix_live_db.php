<?php
// 1. Delete Egg Hatchery from microbiz tables
$eggIds = DB::table('microbiz_subcategories')->where('name', 'Egg Hatchery')->pluck('id');
if ($eggIds->count() > 0) {
    $deleted = DB::table('microbiz_packages')->whereIn('microbiz_subcategory_id', $eggIds)->delete();
    DB::table('microbiz_subcategories')->whereIn('id', $eggIds)->delete();
    echo "Deleted Egg Hatchery: {$deleted} packages removed\n";
} else {
    echo "Egg Hatchery not found in microbiz_subcategories\n";
}

// 2. Also delete from old product tables
$sub = DB::table('product_sub_categories')->where('name', 'Egg Hatchery')->first();
if ($sub) {
    DB::table('product_package_sizes')->whereIn('product_id', DB::table('products')->where('product_sub_category_id', $sub->id)->pluck('id'))->delete();
    DB::table('products')->where('product_sub_category_id', $sub->id)->delete();
    DB::table('product_sub_categories')->where('id', $sub->id)->delete();
    echo "Deleted Egg Hatchery from old product tables\n";
}

// 3. Update ALL microbiz_packages prices to new values
$updates = [
    'Lite Package'       => 260.00,
    'Standard Package'   => 455.00,
    'Full House Package' => 864.00,
    'Gold Package'       => 2210.00,
];

foreach ($updates as $name => $price) {
    $count = DB::table('microbiz_packages')->where('name', $name)->update(['price' => $price]);
    echo "Updated {$name} to \${$price}: {$count} rows\n";
}

// 4. Also update old product_package_sizes prices
$oldUpdates = [
    'Lite Package'       => 260.00,
    'Standard Package'   => 455.00,
    'Full House Package' => 864.00,
    'Gold Package'       => 2210.00,
];

foreach ($oldUpdates as $name => $price) {
    $count = DB::table('product_package_sizes')->where('name', $name)->update(['custom_price' => $price]);
    echo "Updated old table {$name} to \${$price}: {$count} rows\n";
}

// 5. Update base_price on products table
DB::table('products')->where('base_price', 280.00)->update(['base_price' => 260.00]);
echo "Updated products base_price from 280 to 260\n";

echo "\nDone!\n";
