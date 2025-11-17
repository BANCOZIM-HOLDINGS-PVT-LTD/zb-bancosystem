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

        $smartphonesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $cellphonesCategory,
            'name' => 'Smartphones',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $smartphoneId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $smartphonesSubcategory,
            'name' => 'Smartphone',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $smartphoneId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $smartphoneId, 'name' => '2 Units', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Laptops and Printers
        $laptopsPrintersCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Laptops and Printers',
            'emoji' => '💻',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $laptopsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Laptops',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $laptopId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $laptopsSubcategory,
            'name' => 'Laptop',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $laptopId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $printersSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $laptopsPrintersCategory,
            'name' => 'Printers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $printerId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $printersSubcategory,
            'name' => 'Printer',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $printerId, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
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

        // 4. Kitchen ware
        $kitchenwareCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Kitchen ware',
            'emoji' => '🍳',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stovesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $kitchenwareCategory,
            'name' => 'Stoves',
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

        // 5. Television and Entertainment
        $tvEntertainmentCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Television and Entertainment',
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

        // 6. Lounge ware
        $loungewareCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Lounge ware',
            'emoji' => '🛋️',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $gamingConsolesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'Gaming Consoles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ps5Id = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gamingConsolesSubcategory,
            'name' => 'PlayStation 5',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $ps5Id, 'name' => '1 Unit', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Dining Room Sets (under Lounge ware)
        $diningTablesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $loungewareCategory,
            'name' => 'Dining Room Sets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $diningSetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $diningTablesSubcategory,
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

        $wardrobesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Wardrobes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wardrobeId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $wardrobesSubcategory,
            'name' => 'Wardrobe',
            'base_price' => 400.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $wardrobeId, 'name' => '2-Door', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $wardrobeId, 'name' => '4-Door', 'multiplier' => 1.5, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $headboardsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $bedroomwareCategory,
            'name' => 'Headboards',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $headboardId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $headboardsSubcategory,
            'name' => 'Headboard',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $headboardId, 'name' => 'Single', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $headboardId, 'name' => 'Double', 'multiplier' => 1.3, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $headboardId, 'name' => 'King', 'multiplier' => 1.6, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 8. Solar systems
        $solarSystemsCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Solar systems',
            'emoji' => '☀️',
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
            'name' => 'Motor cycles and Bicycle',
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
            'name' => 'Water Storage and Pumping Systems',
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
            'emoji' => '🎒',
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
            'emoji' => '🏫',
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

        // 17. Licensing & Certification Documents
        $licensingCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Licensing & Certification Documents',
            'emoji' => '📄',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $passportSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $licensingCategory,
            'name' => 'Passport',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $passportId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $passportSubcategory,
            'name' => 'Passport Application',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $passportId, 'name' => '1 Application', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
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
            'name' => 'Vacation Packages',
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