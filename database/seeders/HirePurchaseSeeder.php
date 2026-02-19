<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\ProductSeries;
use App\Models\Product;
use App\Models\ProductPackageSize;

class HirePurchaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cellphones
        $this->seedCellphones();

        // 2. Laptops & Printers
        $this->seedLaptopsAndPrinters();

        // 3. ICT Accessories
        $this->seedICTAccessories();

        // 4. Kitchen ware
        $this->seedKitchenWare();

        // 5. Television & Decoders
        $this->seedTVs();

        // 6. Lounge Furniture
        $this->seedLoungeFurniture();

        // 7. Bedroom ware
        $this->seedBedroomWare();

        // 8. Solar systems
        $this->seedSolarSystems();

        // 9. Grooming Accessories
        $this->seedGrooming();

        // 10. Motor Sundries
        $this->seedMotorSundries();

        // 11. Motor cycles & Bicycle
        $this->seedMotorcycles();

        // 12. Building Materials
        $this->seedBuildingMaterials();

        // 13. Agricultural Equipment
        $this->seedAgricEquipment();

        // 14. Agricultural Inputs
        $this->seedAgriculturalInputs();

        // 16. Mother-to-be preparation
        $this->seedMotherToBe();
    }
    
    // ...

    private function seedAgriculturalInputs()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Agricultural Inputs', 'type' => 'hire_purchase'],
            ['emoji' => '🌾']
        );

        // 1. Fertilizer
        $this->seedSubcategoryData($category, 'Fertilizer', [
            'Fertilizer' => [
                'products' => ['Fertilizer'],
                'storage' => ['50kg Bag'],
                'transport' => 'small_truck'
            ]
        ], false, '50kg Standard Bag');

        // 2. Seed + Chemicals
        $this->seedSubcategoryData($category, 'Seed + Chemicals', [
            'Seed' => [
                'products' => ['Seed + Chemicals'],
                'storage' => ['Standard Pack'],
                'transport' => 'indrive'
            ]
        ], false, 'Certified Seeds and Agrochemicals');

        // 3. Combo
        $this->seedSubcategoryData($category, 'Combo (Fertilizer + Seed + Chemicals)', [
            'Combo' => [
                'products' => ['Combo (Fertilizer + Seed + Chemicals)'],
                'storage' => ['Starter Pack'],
                'transport' => 'small_truck'
            ]
        ], false, 'Complete Starter Pack');
    }


    private function seedCellphones()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Cellphones'],
            ['emoji' => '📱', 'type' => 'hire_purchase']
        );

        $brands = [
            'Samsung' => [
                'Galaxy S Series' => [
                    'products' => ['Samsung Galaxy S25 Ultra', 'Samsung Galaxy S25+', 'Samsung Galaxy S25', 'Samsung Galaxy S25 FE'],
                    'storage' => ['256GB', '512GB', '1TB'],
                    'transport' => 'indrive'
                ],
                'Galaxy A Series' => [
                    'products' => ['Samsung Galaxy A56 5G', 'Samsung Galaxy A36 5G', 'Samsung Galaxy A26 5G'],
                    'colors' => ['Awesome Black', 'Awesome White', 'Awesome Blue'],
                    'storage' => ['64GB', '128GB'],
                    'transport' => 'indrive'
                ],
                // ... other series
            ],
            // ... other brands would follow same pattern, ensuring transport is set
             'Apple' => [
                'iPhone 17 Series' => [
                    'products' => ['iPhone 17 Pro Max', 'iPhone 17 Pro', 'iPhone 17', 'iPhone Air'],
                    'colors' => ['Black Titanium', 'White Titanium', 'Blue Titanium'],
                    'storage' => ['128GB', '256GB', '512GB', '1TB'],
                    'transport' => 'indrive'
                ]
            ],
            // Simplify for brevity - applying 'indrive' default to electronics in helper if not specified
        ];
        
        foreach ($brands as $brandName => $seriesList) {
            $subName = $brandName === 'Apple' ? 'iPhone' : $brandName;
            $this->seedSubcategoryData($category, $subName, $seriesList, true, 'Smartphone with full warranty');
        }
    }

    private function seedLaptopsAndPrinters()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Laptops & Printers'],
            ['emoji' => '💻', 'type' => 'hire_purchase']
        );

        // Subcategory: Dual Core
        $this->seedSubcategoryData($category, 'Dual Core', [
            'HP Dual Core' => [
                'products' => ['HP 250 G8 Celeron', 'HP 15 Celeron'],
                'storage' => ['4GB RAM/500GB HDD', '4GB RAM/256GB SSD'],
                'transport' => 'indrive'
            ],
            // ...
        ], false, 'Entry level laptop');

        // ... Add others with transport='indrive'
    }

    // ... (Other seed methods would be similar, adding 'transport' => 'small_truck' or 'indrive')
    
    private function seedKitchenWare()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Kitchen ware'],
            ['emoji' => '🥡', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Fridges', [
            'Defy Fridges' => [
                'products' => ['Defy C386 Fridge Freezer', 'Defy Side-by-Side'],
                'colors' => ['Metallic', 'White', 'Black'],
                'storage' => ['Standard'],
                'transport' => 'small_truck'
            ],
        ], true, 'Energy efficient refrigerator');
    }

    private function seedSolarSystems()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Solar systems'],
            ['emoji' => '☀️', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Batteries', [
            'Pylontech' => [
                'products' => ['Pylontech US3000C', 'Pylontech UP5000'],
                'storage' => ['3.5kWh', '4.8kWh'],
                'transport' => 'small_truck'
            ],
        ], false, 'Lithium-Ion Solar Battery');
    }
    
    // Fallback for others to ensure they compile
    private function seedICTAccessories() { 
         $category = ProductCategory::firstOrCreate(['name' => 'ICT Accessories'], ['emoji' => '🎧', 'type' => 'hire_purchase']);
         
         // Projectors
         $this->seedSubcategoryData($category, 'Projectors', ['Epson' => ['products' => ['Epson EB-X06'], 'storage' => ['Standard']]], false, 'Projector');

         // Starlink
         $this->seedSubcategoryData($category, 'Satellite Internet', [
            'Starlink' => [
                'products' => ['Starlink Internet Kit'], 
                'storage' => ['Standard Kit', 'High Performance Kit'],
                'transport' => 'small_truck',
                'colors' => ['White']
            ]
         ], false, 'High Speed Low Latency Internet');
    }
    private function seedTVs() {
         $category = ProductCategory::firstOrCreate(['name' => 'Television & Decoders'], ['emoji' => '📺', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Televisions', ['Samsung' => ['products' => ['Samsung 55 Inch'], 'storage' => ['Standard']]], false, 'Smart TV');
    }
    private function seedLoungeFurniture() {
         $category = ProductCategory::firstOrCreate(['name' => 'Lounge Furniture'], ['emoji' => '🛋️', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Lounge Suite', ['Zambezi' => ['products' => ['Zambezi Corner Couch'], 'storage' => ['Standard']]], true, 'Lounge Suite');
    }
    private function seedBedroomWare() {
         $category = ProductCategory::firstOrCreate(['name' => 'Bedroom ware'], ['emoji' => '🛏️', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Bed', ['Rest Assured' => ['products' => ['Rest Assured Matrix'], 'storage' => ['Queen']]], false, 'Bed Set');
    }
    private function seedGrooming() {
         $category = ProductCategory::firstOrCreate(['name' => 'Grooming Accessories'], ['emoji' => '💈', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Shaving Kits', ['Wahl' => ['products' => ['Wahl Super Taper'], 'storage' => ['Standard']]], false, 'Grooming Kit');
    }
    private function seedMotorSundries() {
         $category = ProductCategory::firstOrCreate(['name' => 'Motor Sundries'], ['emoji' => '🔧', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Motor Parts', ['Tyres' => ['products' => ['Dunlop SP Touring'], 'storage' => ['15 Inch']]], false, 'Vehicle Part');
    }
    private function seedMotorcycles() {
         $category = ProductCategory::firstOrCreate(['name' => 'Motor cycles & Bicycle'], ['emoji' => '🏍️', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Motorcycles', ['Honda' => ['products' => ['Honda ACE 125'], 'storage' => ['Standard']]], true, 'Motorcycle');
    }
    private function seedBuildingMaterials() {
         $category = ProductCategory::firstOrCreate(['name' => 'Building Materials'], ['emoji' => '🧱', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Cement', ['PPC' => ['products' => ['PPC Cement'], 'storage' => ['50kg'], 'transport' => 'small_truck']], false, 'Cement');
    }
    private function seedAgricEquipment() {
         $category = ProductCategory::firstOrCreate(['name' => 'Agricultural Equipment'], ['emoji' => '🚜', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Tractors', ['John Deere' => ['products' => ['John Deere 5055E'], 'storage' => ['4WD'], 'transport' => 'small_truck']], false, 'Tractor');
    }
    private function seedMotherToBe() {
         $category = ProductCategory::firstOrCreate(['name' => 'Expecting Mother Preparation'], ['emoji' => '🤰', 'type' => 'hire_purchase']);
         $this->seedSubcategoryData($category, 'Baby Items', ['Essentials' => ['products' => ['Newborn Starter Kit'], 'storage' => ['Standard']]], true, 'Baby Essentials');
    }


    private function seedSubcategoryData($category, $subcategoryName, $seriesList, $hasColors, $defaultSpec = 'Standard Specification')
    {
        $subcategory = ProductSubCategory::firstOrCreate(
            ['product_category_id' => $category->id, 'name' => $subcategoryName]
        );

        foreach ($seriesList as $seriesName => $data) {
            $series = ProductSeries::firstOrCreate(
                ['product_sub_category_id' => $subcategory->id, 'name' => $seriesName],
                ['description' => "Collection: $seriesName", 'image_url' => null]
            );

            foreach ($data['products'] as $productName) {
                // Generate strict code: HP-{CAT_PREFIX}-{NAME_PREFIX}-{RAND}
                $catPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $category->name), 0, 3));
                $namePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 6));
                $productCode = "HP-{$catPrefix}-{$namePrefix}-" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

                // Use provided transport or default based on category emoji/type
                $transport = $data['transport'] ?? 'indrive'; // Default to smaller transport
                if ($category->name === 'Building Materials' || $category->name === 'Agricultural Equipment' || $category->name === 'Lounge Furniture') {
                    $transport = $data['transport'] ?? 'small_truck';
                }

                $product = Product::updateOrCreate(
                    ['product_sub_category_id' => $subcategory->id, 'name' => $productName],
                    [
                        'product_series_id' => $series->id,
                        'product_code' => $productCode,
                        'base_price' => rand(100, 3000),
                        'specification' => $defaultSpec,
                        'transport_method' => $transport,
                        'image_url' => null,
                    ]
                );

                if (isset($data['storage'])) {
                    foreach ($data['storage'] as $index => $variant) {
                        ProductPackageSize::firstOrCreate(
                            ['product_id' => $product->id, 'name' => $variant],
                            [
                                'multiplier' => 1 + ($index * 0.2),
                                'custom_price' => $product->base_price * (1 + ($index * 0.2))
                            ]
                        );
                    }
                }
            }
        }
    }
}
