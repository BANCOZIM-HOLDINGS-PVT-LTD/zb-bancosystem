<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalProductsSeeder extends Seeder
{
    /**
     * Run the database seeds for Personal Products (Hire Purchase)
     */
    public function run(): void
    {
        // Electronics Category
        $electronicsCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Electronics',
            'emoji' => '📱',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mobile Phones Subcategory
        $mobilePhonesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $electronicsCategory,
            'name' => 'Mobile Phones',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Smartphone Product
        $smartphoneId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $mobilePhonesSubcategory,
            'name' => 'Smartphone',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $smartphoneId,
                'name' => '1 Unit',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $smartphoneId,
                'name' => '2 Units',
                'multiplier' => 2.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Laptops Subcategory
        $laptopsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $electronicsCategory,
            'name' => 'Laptops',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $laptopId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $laptopsSubcategory,
            'name' => 'Standard Laptop',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $laptopId,
                'name' => '1 Unit',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Tablets Subcategory
        $tabletsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $electronicsCategory,
            'name' => 'Tablets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tabletId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tabletsSubcategory,
            'name' => 'Tablet',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $tabletId,
                'name' => '1 Unit',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Home Appliances Category
        $appliancesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Home Appliances',
            'emoji' => '🏠',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Refrigerators Subcategory
        $refrigeratorsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $appliancesCategory,
            'name' => 'Refrigerators',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $refrigeratorId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $refrigeratorsSubcategory,
            'name' => 'Refrigerator',
            'base_price' => 600.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $refrigeratorId,
                'name' => '1 Unit',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Washing Machines Subcategory
        $washingMachinesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $appliancesCategory,
            'name' => 'Washing Machines',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $washingMachineId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $washingMachinesSubcategory,
            'name' => 'Washing Machine',
            'base_price' => 450.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $washingMachineId,
                'name' => '1 Unit',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Stoves Subcategory
        $stovesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $appliancesCategory,
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
            [
                'product_id' => $stoveId,
                'name' => '4 Burner',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $stoveId,
                'name' => '6 Burner',
                'multiplier' => 1.4,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Furniture Category
        $furnitureCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Furniture',
            'emoji' => '🛋️',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Living Room Subcategory
        $livingRoomSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $furnitureCategory,
            'name' => 'Living Room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sofaSetId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $livingRoomSubcategory,
            'name' => 'Sofa Set',
            'base_price' => 400.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $sofaSetId,
                'name' => '3-Seater',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $sofaSetId,
                'name' => '5-Seater',
                'multiplier' => 1.5,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $sofaSetId,
                'name' => '7-Seater',
                'multiplier' => 2.0,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Bedroom Subcategory
        $bedroomSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $furnitureCategory,
            'name' => 'Bedroom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bedId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $bedroomSubcategory,
            'name' => 'Bed Set',
            'base_price' => 350.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $bedId,
                'name' => 'Single',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bedId,
                'name' => 'Double',
                'multiplier' => 1.3,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $bedId,
                'name' => 'King',
                'multiplier' => 1.6,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Dining Room Subcategory
        $diningRoomSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $furnitureCategory,
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
            [
                'product_id' => $diningSetId,
                'name' => '4-Seater',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $diningSetId,
                'name' => '6-Seater',
                'multiplier' => 1.4,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $diningSetId,
                'name' => '8-Seater',
                'multiplier' => 1.8,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Solar Systems Category
        $solarCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Solar Systems',
            'emoji' => '☀️',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Power Stations Subcategory
        $powerStationsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $solarCategory,
            'name' => 'Power Stations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $powerStationId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $powerStationsSubcategory,
            'name' => 'Solar Power Station',
            'base_price' => 1000.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $powerStationId,
                'name' => '1kW',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $powerStationId,
                'name' => '2kW',
                'multiplier' => 2.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $powerStationId,
                'name' => '5kW',
                'multiplier' => 5.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $powerStationId,
                'name' => '10kW',
                'multiplier' => 10.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Solar Panels Subcategory
        $solarPanelsSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $solarCategory,
            'name' => 'Solar Panels',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $solarPanelId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $solarPanelsSubcategory,
            'name' => 'Solar Panel',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            [
                'product_id' => $solarPanelId,
                'name' => '1 Panel (300W)',
                'multiplier' => 1.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $solarPanelId,
                'name' => '4 Panels (1.2kW)',
                'multiplier' => 4.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $solarPanelId,
                'name' => '8 Panels (2.4kW)',
                'multiplier' => 8.00,
                'custom_price' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('Personal Products seeded successfully!');
    }
}