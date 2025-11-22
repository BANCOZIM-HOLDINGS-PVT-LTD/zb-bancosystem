<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalProductsSeeder extends Seeder
{
    /**
     * Run the database seeds for Personal Products (Hire Purchase)
     * 18 Main Categories as specified
     */
    public function run(): void
    {
        // 1. Cellphones
        $cellphonesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Cellphones',
            'emoji' => '📱',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Samsung Subcategory
        $samsungSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Samsung',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $samsungId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $samsungSubcategory,
            'name' => 'Samsung Phone',
            'base_price' => 250.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $samsungId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Itel Subcategory
        $itelSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Itel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itelId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $itelSubcategory,
            'name' => 'Itel Phone',
            'base_price' => 80.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $itelId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ZTE Subcategory
        $zteSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'ZTE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zteId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $zteSubcategory,
            'name' => 'ZTE Phone',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $zteId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Redmi Subcategory
        $redmiSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Redmi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $redmiId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $redmiSubcategory,
            'name' => 'Redmi Phone',
            'base_price' => 180.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $redmiId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tecno Subcategory
        $tecnoSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Tecno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tecnoId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tecnoSubcategory,
            'name' => 'Tecno Phone',
            'base_price' => 120.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $tecnoId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Huawei Subcategory
        $huaweiSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Huawei',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $huaweiId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $huaweiSubcategory,
            'name' => 'Huawei Phone',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $huaweiId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // iPhone Subcategory
        $iphoneSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'iPhone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $iphoneId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $iphoneSubcategory,
            'name' => 'iPhone',
            'base_price' => 800.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $iphoneId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Google Pixel Subcategory
        $googlePixelSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Google Pixel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $googlePixelId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $googlePixelSubcategory,
            'name' => 'Google Pixel Phone',
            'base_price' => 600.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $googlePixelId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Laptops and Printers
        $laptopsPrintersCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Laptops & Printers',
            'emoji' => '💻',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dual Core Subcategory
        $dualCoreSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Dual Core',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dualCoreId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $dualCoreSubcategory,
            'name' => 'Dual Core Laptop',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $dualCoreId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core i3 Subcategory
        $corei3Subcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Core i3',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $corei3Id = DB::table('products')->insertGetId([
            'product_sub_category_id' => $corei3Subcategory,
            'name' => 'Core i3 Laptop',
            'base_price' => 400.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $corei3Id, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core i5 Subcategory
        $corei5Subcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Core i5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $corei5Id = DB::table('products')->insertGetId([
            'product_sub_category_id' => $corei5Subcategory,
            'name' => 'Core i5 Laptop',
            'base_price' => 550.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $corei5Id, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core i7 Subcategory
        $corei7Subcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Core i7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $corei7Id = DB::table('products')->insertGetId([
            'product_sub_category_id' => $corei7Subcategory,
            'name' => 'Core i7 Laptop',
            'base_price' => 750.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $corei7Id, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Gaming Laptops Subcategory
        $gamingLaptopsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Gaming Laptops',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gamingLaptopId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gamingLaptopsSubcategory,
            'name' => 'Gaming Laptop',
            'base_price' => 1200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $gamingLaptopId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Deskjet Printers Subcategory
        $deskjetSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Deskjet Printers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deskjetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $deskjetSubcategory,
            'name' => 'Deskjet Printer',
            'base_price' => 120.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $deskjetId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Laser Printer Subcategory
        $laserSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Laser Printer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $laserId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $laserSubcategory,
            'name' => 'Laser Printer',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $laserId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Inktank Printer Subcategory
        $inktankSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Inktank Printer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inktankId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $inktankSubcategory,
            'name' => 'Inktank Printer',
            'base_price' => 180.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $inktankId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. ICT Accessories
        $ictAccessoriesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'ICT Accessories',
            'emoji' => '🎧',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectorsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $ictAccessoriesCategory,
            'name' => 'Projectors',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectorId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $projectorsSubcategory,
            'name' => 'Projector',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $projectorId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $ictGamingConsolesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $ictAccessoriesCategory,
            'name' => 'Gaming Consoles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $xboxId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $ictGamingConsolesSubcategory,
            'name' => 'Xbox Series X',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $xboxId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // 4. Kitchen ware
        $kitchenwareCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Kitchen ware',
            'emoji' => '🥡',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kitchen Unit
        $kitchenUnitSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Kitchen Unit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenUnitId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $kitchenUnitSubcategory,
            'name' => 'Kitchen Unit',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $kitchenUnitId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Kitchen Table
        $kitchenTableSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Kitchen Table',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenTableId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $kitchenTableSubcategory,
            'name' => 'Kitchen Table',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $kitchenTableId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pots
        $potsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Pots',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $potsId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $potsSubcategory,
            'name' => 'Cooking Pots Set',
            'base_price' => 80.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $potsId, 'name' => '1 Set', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Microwave
        $microwaveSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Microwave',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $microwaveId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $microwaveSubcategory,
            'name' => 'Microwave Oven',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $microwaveId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Rice Cooker
        $riceCookerSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Rice cooker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $riceCookerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $riceCookerSubcategory,
            'name' => 'Rice Cooker',
            'base_price' => 60.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $riceCookerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pressure Cooker
        $pressureCookerSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Pressure Cooker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pressureCookerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $pressureCookerSubcategory,
            'name' => 'Pressure Cooker',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $pressureCookerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Air Fryer
        $airFryerSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Air Fryer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $airFryerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $airFryerSubcategory,
            'name' => 'Air Fryer',
            'base_price' => 120.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $airFryerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Breakfast Maker
        $breakfastMakerSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Breakfast Maker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $breakfastMakerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $breakfastMakerSubcategory,
            'name' => 'Breakfast Maker',
            'base_price' => 70.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $breakfastMakerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Large Vacuum Flask
        $vacuumFlaskSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Large Vacuum Flask',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacuumFlaskId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $vacuumFlaskSubcategory,
            'name' => 'Large Vacuum Flask',
            'base_price' => 40.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $vacuumFlaskId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Sadza Maker
        $sadzaMakerSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Sadza Maker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sadzaMakerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $sadzaMakerSubcategory,
            'name' => 'Sadza Maker',
            'base_price' => 90.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $sadzaMakerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Kitchen Blender
        $blenderSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Kitchen Blender',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blenderId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $blenderSubcategory,
            'name' => 'Kitchen Blender',
            'base_price' => 80.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $blenderId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Stove
        $stovesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Stove',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stoveId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $stovesSubcategory,
            'name' => 'Gas Stove',
            'base_price' => 350.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $stoveId, 'name' => '4 Burner', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $stoveId, 'name' => '6 Burner', 'multiplier' => 1.4, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Fridges
        $fridgesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Fridges',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fridgeId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $fridgesSubcategory,
            'name' => 'Refrigerator',
            'base_price' => 600.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $fridgeId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Washing Machine
        $washingMachineSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Washing Machine',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $washingMachineId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $washingMachineSubcategory,
            'name' => 'Washing Machine',
            'base_price' => 450.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $washingMachineId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // 5. Television and Entertainment
        $tvEntertainmentCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Television & Decoders',
            'emoji' => '📺',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $televisionsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $tvEntertainmentCategory,
            'name' => 'Televisions',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tvId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $televisionsSubcategory,
            'name' => 'Smart TV',
            'base_price' => 400.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $tvId, 'name' => '32 Inch', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $tvId, 'name' => '43 Inch', 'multiplier' => 1.5, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $tvId, 'name' => '55 Inch', 'multiplier' => 2.0, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 6. Lounge Furniture
        $loungewareCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Lounge Furniture',
            'emoji' => '🛋️',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lounge Suite Subcategory
        $loungeSuiteSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'Lounge Suite',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $loungeSuiteId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $loungeSuiteSubcategory,
            'name' => 'Lounge Suite',
            'base_price' => 800.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $loungeSuiteId, 'name' => '3-Seater', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $loungeSuiteId, 'name' => '5-Seater', 'multiplier' => 1.3, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $loungeSuiteId, 'name' => '7-Seater', 'multiplier' => 1.6, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // TV Stands Subcategory
        $tvStandsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'TV Stands',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tvStandId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tvStandsSubcategory,
            'name' => 'TV Stand',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $tvStandId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Coffee Tables Subcategory
        $coffeeTablesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'Coffee Tables',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coffeeTableId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $coffeeTablesSubcategory,
            'name' => 'Coffee Table',
            'base_price' => 120.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $coffeeTableId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Dining Room Sets Subcategory
        $diningRoomSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'Dining Room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $diningSetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $diningRoomSubcategory,
            'name' => 'Dining Set',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $diningSetId, 'name' => '4-Seater', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $diningSetId, 'name' => '6-Seater', 'multiplier' => 1.4, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $diningSetId, 'name' => '8-Seater', 'multiplier' => 1.8, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // 7. Bedroom ware
        $bedroomwareCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Bedroom ware',
            'emoji' => '🛏️',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bed Subcategory
        $bedSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Bed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bedId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $bedSubcategory,
            'name' => 'Bed',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $bedId, 'name' => 'Single', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $bedId, 'name' => 'Double', 'multiplier' => 1.3, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $bedId, 'name' => 'King', 'multiplier' => 1.6, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Headboard Set Subcategory
        $headboardSetSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Headboard Set',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $headboardSetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $headboardSetSubcategory,
            'name' => 'Headboard Set',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $headboardSetId, 'name' => 'Single', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $headboardSetId, 'name' => 'Double', 'multiplier' => 1.3, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $headboardSetId, 'name' => 'King', 'multiplier' => 1.6, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Blankets & Comforters Subcategory
        $blanketsComfortersSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Blankets & Comforters',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blanketId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $blanketsComfortersSubcategory,
            'name' => 'Blanket & Comforter Set',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $blanketId, 'name' => '1 Set', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pillow sets & Sheets Subcategory
        $pillowSheetsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Pillow sets & Sheets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pillowSheetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $pillowSheetsSubcategory,
            'name' => 'Pillow & Sheet Set',
            'base_price' => 80.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $pillowSheetId, 'name' => '1 Set', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 8. Solar systems
        $solarSystemsCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Solar systems',
            'emoji' => '🔆',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batteriesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $solarSystemsCategory,
            'name' => 'Batteries',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batteryId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $batteriesSubcategory,
            'name' => 'Solar Battery',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $batteryId, 'name' => '100Ah', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $batteryId, 'name' => '200Ah', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $panelsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $solarSystemsCategory,
            'name' => 'Panels',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $panelId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $panelsSubcategory,
            'name' => 'Solar Panel',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $panelId, 'name' => '300W', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $panelId, 'name' => '550W', 'multiplier' => 1.8, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 9. Grooming Accessories
        $groomingCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Grooming Accessories',
            'emoji' => '💇',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shavingKitsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $groomingCategory,
            'name' => 'Shaving Kits',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shavingKitId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $shavingKitsSubcategory,
            'name' => 'Shaving Kit',
            'base_price' => 50.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $shavingKitId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $wigsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $groomingCategory,
            'name' => 'Wigs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wigId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $wigsSubcategory,
            'name' => 'Wig',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $wigId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // 10. Motor Sundries
        $motorSundriesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Motor Sundries',
            'emoji' => '🔧',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $motorPartsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $motorSundriesCategory,
            'name' => 'Motor Parts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $motorPartId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $motorPartsSubcategory,
            'name' => 'Motor Part',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $motorPartId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 11. Motor cycles and Bicycle
        $motorcyclesBicyclesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Motor cycles & Bicycle',
            'emoji' => '🏍️',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $motorcyclesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $motorcyclesBicyclesCategory,
            'name' => 'Motorcycles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $motorcycleId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $motorcyclesSubcategory,
            'name' => 'Motorcycle',
            'base_price' => 1500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $motorcycleId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 12. Building Materials
        $buildingMaterialsCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Building Materials',
            'emoji' => '🏗️',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cementSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $buildingMaterialsCategory,
            'name' => 'Cement',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cementId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $cementSubcategory,
            'name' => 'Cement',
            'base_price' => 10.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $cementId, 'name' => '1 Bag (50kg)', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $cementId, 'name' => '10 Bags', 'multiplier' => 10.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 13. Agricultural Equipment
        $agricEquipmentCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Agricultural Equipment',
            'emoji' => '🚜',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tractorsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $agricEquipmentCategory,
            'name' => 'Tractors',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tractorId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tractorsSubcategory,
            'name' => 'Tractor',
            'base_price' => 5000.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $tractorId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Water storage and pumping systems (under Agricultural Equipment)
        $waterStorageSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $agricEquipmentCategory,
            'name' => 'Water Storage & Pumping Systems',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $boreholePumpId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $waterStorageSubcategory,
            'name' => 'Borehole Pump',
            'base_price' => 800.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $boreholePumpId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 14. Scholars back to school
        $backToSchoolCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Scholars back to school',
            'emoji' => '🎒📚',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $schoolUniformsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $backToSchoolCategory,
            'name' => 'School Uniforms',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uniformId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $schoolUniformsSubcategory,
            'name' => 'School Uniform Set',
            'base_price' => 50.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $uniformId, 'name' => '1 Set', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 15. School fees
        $schoolFeesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'School fees',
            'emoji' => '👫📚',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primarySchoolSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Primary School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primaryFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $primarySchoolSubcategory,
            'name' => 'Primary School Fees',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $primaryFeesId, 'name' => '1 Term', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $primaryFeesId, 'name' => '2 Terms', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $primaryFeesId, 'name' => '3 Terms', 'multiplier' => 3.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $secondarySchoolSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Secondary School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondaryFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $secondarySchoolSubcategory,
            'name' => 'Secondary School Fees',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $secondaryFeesId, 'name' => '1 Term', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $secondaryFeesId, 'name' => '2 Terms', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $secondaryFeesId, 'name' => '3 Terms', 'multiplier' => 3.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $polytechSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Polytech',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $polytechFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $polytechSubcategory,
            'name' => 'Polytech Fees',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $polytechFeesId, 'name' => '1 Semester', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $polytechFeesId, 'name' => '2 Semesters', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $universitySubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'University',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $universityFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $universitySubcategory,
            'name' => 'University Fees',
            'base_price' => 600.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $universityFeesId, 'name' => '1 Semester', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $universityFeesId, 'name' => '2 Semesters', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 16. Mother-to-be preparation
        $motherToBeCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Mother-to-be preparation',
            'emoji' => '🤰',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $babyItemsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $motherToBeCategory,
            'name' => 'Baby Items',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $babyKitId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $babyItemsSubcategory,
            'name' => 'Baby Starter Kit',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $babyKitId, 'name' => '1 Kit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 17. Drivers License
        $licensingCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Drivers License',
            'emoji' => '🚗',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driversLicenseSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $licensingCategory,
            'name' => 'Drivers License',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driversLicenseId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $driversLicenseSubcategory,
            'name' => 'Drivers License Application',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $driversLicenseId, 'name' => '1 Application', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 18. Holiday Package
        $holidayPackageCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Holiday Package',
            'emoji' => '🎡',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacationPackagesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $holidayPackageCategory,
            'name' => 'Zimparks Lodges/Cottages',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacationId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $vacationPackagesSubcategory,
            'name' => 'Zimparks Vacation Package',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $vacationId, 'name' => '1 Package', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Personal Products seeded successfully! 18 main categories created.');
    }
}