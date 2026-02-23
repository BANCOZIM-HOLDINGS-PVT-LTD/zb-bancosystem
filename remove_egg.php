<?php
$sub = \Illuminate\Support\Facades\DB::table('product_sub_categories')->where('name', 'Egg Hatchery')->first();
if ($sub) {
    \Illuminate\Support\Facades\DB::table('product_package_sizes')
        ->whereIn('product_id', \Illuminate\Support\Facades\DB::table('products')->where('product_sub_category_id', $sub->id)->pluck('id'))
        ->delete();
    \Illuminate\Support\Facades\DB::table('products')->where('product_sub_category_id', $sub->id)->delete();
    \Illuminate\Support\Facades\DB::table('product_sub_categories')->where('id', $sub->id)->delete();
}
$msub = \App\Models\MicrobizSubcategory::where('name', 'Egg Hatchery')->first();
if ($msub) {
    \App\Models\MicrobizPackage::where('microbiz_subcategory_id', $msub->id)->delete();
    $msub->delete();
}
echo "Done\n";
