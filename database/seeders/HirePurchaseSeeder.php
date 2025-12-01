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
        // 1. Cellphones (Already correct structure: Category -> Brand Subcategory -> Series -> Product)
        $this->seedCellphones();

        // 2. Laptops & Printers (Fixed structure: Category -> Type Subcategory -> Brand Series -> Product)
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

        // 16. Mother-to-be preparation
        $this->seedMotherToBe();
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
                    'colors' => ['Phantom Black', 'Phantom Silver', 'Green', 'Burgundy'],
                    'storage' => ['256GB', '512GB', '1TB']
                ],
                'Galaxy A Series' => [
                    'products' => ['Samsung Galaxy A56 5G', 'Samsung Galaxy A36 5G', 'Samsung Galaxy A26 5G'],
                    'colors' => ['Awesome Black', 'Awesome White', 'Awesome Blue'],
                    'storage' => ['64GB', '128GB']
                ],
                'Galaxy Z Series' => [
                    'products' => ['Samsung Galaxy Z Fold 7', 'Samsung Galaxy Z Flip 7', 'Samsung Galaxy Z Fold 6'],
                    'colors' => ['Phantom Black', 'Green', 'Cream'],
                    'storage' => ['256GB', '512GB']
                ]
            ],
            'Apple' => [
                'iPhone 17 Series' => [
                    'products' => ['iPhone 17 Pro Max', 'iPhone 17 Pro', 'iPhone 17', 'iPhone Air'],
                    'colors' => ['Black Titanium', 'White Titanium', 'Blue Titanium'],
                    'storage' => ['128GB', '256GB', '512GB', '1TB']
                ]
            ],
            'ZTE' => [
                'Nubia Series' => [
                    'products' => ['ZTE Nubia Red Magic 11 Pro', 'ZTE Nubia Z80 Ultra'],
                    'colors' => ['Black', 'Silver'],
                    'storage' => ['256GB', '512GB']
                ],
                'Blade Series' => [
                    'products' => ['ZTE Blade A35e', 'ZTE Blade V70 Vita'],
                    'colors' => ['Blue', 'Grey'],
                    'storage' => ['64GB', '128GB']
                ]
            ],
            'Redmi' => [
                'Note Series' => [
                    'products' => ['Redmi Note 15 Pro Plus 5G', 'Redmi Note 15 Pro 5G'],
                    'colors' => ['Midnight Black', 'Polar White'],
                    'storage' => ['128GB', '256GB']
                ]
            ],
            'Tecno' => [
                'Phantom Series' => [
                    'products' => ['Tecno Phantom V Fold 2'],
                    'colors' => ['Black', 'White'],
                    'storage' => ['256GB', '512GB']
                ],
                'Camon Series' => [
                    'products' => ['Tecno Camon 30 Premier 5G'],
                    'colors' => ['Black', 'Blue'],
                    'storage' => ['256GB', '512GB']
                ]
            ],
            'Huawei' => [
                'Mate Series' => [
                    'products' => ['Huawei Mate 80 Pro Max', 'Huawei Mate 70 Air'],
                    'colors' => ['Black', 'Silver'],
                    'storage' => ['256GB', '512GB']
                ],
                'Pura Series' => [
                    'products' => ['Huawei Pura 80 Ultra'],
                    'colors' => ['Black', 'White'],
                    'storage' => ['256GB', '512GB']
                ]
            ]
        ];
        
        foreach ($brands as $brandName => $seriesList) {
            $subName = $brandName === 'Apple' ? 'iPhone' : $brandName;
            $this->seedSubcategoryData($category, $subName, $seriesList, true);
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
                'storage' => ['4GB RAM/500GB HDD', '4GB RAM/256GB SSD']
            ],
            'Dell Dual Core' => [
                'products' => ['Dell Vostro 3500 Celeron', 'Dell Inspiron 3502'],
                'storage' => ['4GB RAM/1TB HDD', '4GB RAM/256GB SSD']
            ],
            'Lenovo Dual Core' => [
                'products' => ['Lenovo V15 Celeron', 'Lenovo IdeaPad 3 Celeron'],
                'storage' => ['4GB RAM/1TB HDD']
            ]
        ], false);

        // Subcategory: Core i3
        $this->seedSubcategoryData($category, 'Core i3', [
            'HP Core i3' => [
                'products' => ['HP 250 G8 i3', 'HP Pavilion 15 i3'],
                'storage' => ['8GB RAM/256GB SSD', '8GB RAM/512GB SSD']
            ],
            'Dell Core i3' => [
                'products' => ['Dell Vostro 3500 i3', 'Dell Inspiron 3511 i3'],
                'storage' => ['8GB RAM/256GB SSD', '8GB RAM/1TB HDD']
            ]
        ], false);

        // Subcategory: Core i5
        $this->seedSubcategoryData($category, 'Core i5', [
            'HP Core i5' => [
                'products' => ['HP ProBook 450 G8 i5', 'HP Pavilion 15 i5'],
                'storage' => ['8GB RAM/512GB SSD', '16GB RAM/512GB SSD']
            ],
            'Dell Core i5' => [
                'products' => ['Dell Latitude 3520 i5', 'Dell Inspiron 5510 i5'],
                'storage' => ['8GB RAM/512GB SSD', '16GB RAM/512GB SSD']
            ]
        ], false);

        // Subcategory: Core i7
        $this->seedSubcategoryData($category, 'Core i7', [
            'HP Core i7' => [
                'products' => ['HP Envy 15 i7', 'HP Spectre x360 i7'],
                'storage' => ['16GB RAM/512GB SSD', '16GB RAM/1TB SSD']
            ],
            'Dell Core i7' => [
                'products' => ['Dell XPS 13 i7', 'Dell XPS 15 i7'],
                'storage' => ['16GB RAM/512GB SSD', '32GB RAM/1TB SSD']
            ]
        ], false);

        // Subcategory: Gaming Laptops
        $this->seedSubcategoryData($category, 'Gaming Laptops', [
            'HP Omen' => [
                'products' => ['HP Omen 16', 'HP Omen 17'],
                'storage' => ['16GB RAM/1TB SSD/RTX 4060', '32GB RAM/1TB SSD/RTX 4070']
            ],
            'Dell Alienware' => [
                'products' => ['Alienware m16', 'Alienware x14'],
                'storage' => ['16GB RAM/1TB SSD/RTX 4060', '32GB RAM/2TB SSD/RTX 4080']
            ],
            'ASUS ROG' => [
                'products' => ['ASUS ROG Strix G16', 'ASUS ROG Zephyrus G14'],
                'storage' => ['16GB RAM/512GB SSD/RTX 4050', '16GB RAM/1TB SSD/RTX 4060']
            ]
        ], false);

        // Printers
        $this->seedSubcategoryData($category, 'Deskjet Printers', [
            'HP Deskjet' => [
                'products' => ['HP DeskJet 2720', 'HP DeskJet Plus 4120'],
                'storage' => ['Standard']
            ],
            'Canon Pixma' => [
                'products' => ['Canon Pixma MG2540S', 'Canon Pixma TS3140'],
                'storage' => ['Standard']
            ]
        ], false);
    }

    private function seedICTAccessories()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'ICT Accessories'],
            ['emoji' => '🎧', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Projectors', [
            'Epson Projectors' => [
                'products' => ['Epson EB-X06', 'Epson EB-FH52'],
                'storage' => ['XGA', 'Full HD']
            ],
            'ViewSonic Projectors' => [
                'products' => ['ViewSonic PA503S', 'ViewSonic PX701-4K'],
                'storage' => ['SVGA', '4K UHD']
            ]
        ], false);

        $this->seedSubcategoryData($category, 'Gaming Consoles', [
            'PlayStation' => [
                'products' => ['PlayStation 5', 'PlayStation 4 Pro'],
                'storage' => ['Digital Edition', 'Disc Edition']
            ],
            'Xbox' => [
                'products' => ['Xbox Series X', 'Xbox Series S'],
                'storage' => ['1TB', '512GB']
            ]
        ], false);
    }

    private function seedKitchenWare()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Kitchen ware'],
            ['emoji' => '🥡', 'type' => 'hire_purchase']
        );

        // Kitchen Unit
        $this->seedSubcategoryData($category, 'Kitchen Unit', [
            'Modern Kitchens' => [
                'products' => ['Harare Fitted Kitchen', 'Bulawayo Modular Kitchen'],
                'colors' => ['White Gloss', 'Oak', 'Grey'],
                'storage' => ['2.4m', '3.0m']
            ]
        ], true);

        // Fridges
        $this->seedSubcategoryData($category, 'Fridges', [
            'Defy Fridges' => [
                'products' => ['Defy C386 Fridge Freezer', 'Defy Side-by-Side'],
                'colors' => ['Metallic', 'White', 'Black'],
                'storage' => ['Standard']
            ],
            'Samsung Fridges' => [
                'products' => ['Samsung Top Freezer', 'Samsung Bespoke'],
                'colors' => ['Silver', 'Black', 'Navy'],
                'storage' => ['Standard']
            ]
        ], true);

        // Stoves
        $this->seedSubcategoryData($category, 'Stove', [
            'Defy Stoves' => [
                'products' => ['Defy 4 Plate Compact', 'Defy Gas Electric'],
                'colors' => ['Black', 'White'],
                'storage' => ['Standard']
            ],
            'KIC Stoves' => [
                'products' => ['KIC 4 Plate Electric'],
                'colors' => ['White'],
                'storage' => ['Standard']
            ]
        ], true);
    }

    private function seedTVs()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Television & Decoders'],
            ['emoji' => '📺', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Televisions', [
            'Samsung TVs' => [
                'products' => ['Samsung Crystal UHD', 'Samsung QLED'],
                'storage' => ['43 Inch', '50 Inch', '55 Inch', '65 Inch']
            ],
            'Hisense TVs' => [
                'products' => ['Hisense A6H Series', 'Hisense ULED'],
                'storage' => ['32 Inch', '43 Inch', '55 Inch', '65 Inch']
            ],
            'Sony TVs' => [
                'products' => ['Sony Bravia X75K', 'Sony Bravia X80K'],
                'storage' => ['55 Inch', '65 Inch']
            ]
        ], false);
    }

    private function seedLoungeFurniture()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Lounge Furniture'],
            ['emoji' => '🛋️', 'type' => 'hire_purchase']
        );

        // Lounge Suite
        $this->seedSubcategoryData($category, 'Lounge Suite', [
            'Zambezi Collection' => [
                'products' => ['Zambezi Corner Couch', 'Zambezi 3-Piece Suite'],
                'colors' => ['Elephant Grey', 'Savanna Beige', 'Buffalo Brown'],
                'storage' => ['Standard']
            ],
            'Kariba Comfort' => [
                'products' => ['Kariba Recliner Suite', 'Kariba L-Shape'],
                'colors' => ['Deep Blue', 'Sunset Orange', 'Sand'],
                'storage' => ['Standard']
            ],
            'Safari Luxury' => [
                'products' => ['Safari Chesterfield', 'Safari Wingback'],
                'colors' => ['Genuine Leather Brown', 'Velvet Green'],
                'storage' => ['3-Seater', '2-Seater', '1-Seater']
            ]
        ], true);

        // TV Stands
        $this->seedSubcategoryData($category, 'TV Stands', [
            'Savanna Woodworks' => [
                'products' => ['Savanna Floating Stand', 'Savanna Rustic Unit'],
                'colors' => ['Teak', 'Oak', 'White Wash'],
                'storage' => ['1.8m', '2.2m']
            ]
        ], true);
    }

    private function seedBedroomWare()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Bedroom ware'],
            ['emoji' => '🛏️', 'type' => 'hire_purchase']
        );

        // Bed
        $this->seedSubcategoryData($category, 'Bed', [
            'Rest Assured' => [
                'products' => ['Rest Assured Matrix', 'Rest Assured Heritage'],
                'storage' => ['Double', 'Queen', 'King']
            ],
            'Cloud Nine' => [
                'products' => ['Cloud Nine Blue', 'Cloud Nine Neuroflex'],
                'storage' => ['Double', 'Queen', 'King']
            ]
        ], false);

        // Headboard Set
        $this->seedSubcategoryData($category, 'Headboard Set', [
            'Royal Sleep' => [
                'products' => ['Royal Tufted Headboard', 'Royal Panel Headboard'],
                'colors' => ['Grey Velvet', 'Cream Linen', 'Black Leather'],
                'storage' => ['Double', 'Queen', 'King']
            ]
        ], true);
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
                'storage' => ['3.5kWh', '4.8kWh']
            ],
            'Freedom Won' => [
                'products' => ['Freedom Won Lite Home'],
                'storage' => ['5/4', '10/8', '15/12']
            ]
        ], false);

        $this->seedSubcategoryData($category, 'Panels', [
            'Canadian Solar' => [
                'products' => ['Canadian Solar HiKu'],
                'storage' => ['455W', '550W']
            ],
            'JA Solar' => [
                'products' => ['JA Solar Deep Blue'],
                'storage' => ['460W', '545W']
            ]
        ], false);
    }

    private function seedGrooming()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Grooming Accessories'],
            ['emoji' => '💈', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Shaving Kits', [
            'Wahl' => [
                'products' => ['Wahl Super Taper', 'Wahl Home Pro'],
                'storage' => ['Standard']
            ],
            'Philips' => [
                'products' => ['Philips Series 3000', 'Philips OneBlade'],
                'storage' => ['Standard']
            ]
        ], false);

        $this->seedSubcategoryData($category, 'Wigs', [
            'Ebony Hair' => [
                'products' => ['Brazilian Straight', 'Peruvian Body Wave'],
                'colors' => ['Natural Black', 'Dark Brown', 'Burgundy'],
                'storage' => ['12 Inch', '16 Inch', '20 Inch']
            ]
        ], true);
    }

    private function seedMotorSundries()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Motor Sundries'],
            ['emoji' => '🔧', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Motor Parts', [
            'Tyres' => [
                'products' => ['Dunlop SP Touring', 'Goodyear EfficientGrip'],
                'storage' => ['13 Inch', '14 Inch', '15 Inch', '16 Inch']
            ],
            'Batteries' => [
                'products' => ['Willard Battery', 'Exide Battery'],
                'storage' => ['628', '646', '652']
            ]
        ], false);
    }

    private function seedMotorcycles()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Motor cycles & Bicycle'],
            ['emoji' => '🏍️', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Motorcycles', [
            'Honda Bikes' => [
                'products' => ['Honda ACE 125', 'Honda XL 125'],
                'colors' => ['Red', 'White'],
                'storage' => ['Standard']
            ],
            'Yamaha Bikes' => [
                'products' => ['Yamaha Crux', 'Yamaha YBR 125'],
                'colors' => ['Blue', 'Black'],
                'storage' => ['Standard']
            ]
        ], true);
    }

    private function seedBuildingMaterials()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Building Materials'],
            ['emoji' => '🧱', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Cement', [
            'PPC Cement' => [
                'products' => ['PPC Surebuild 42.5N'],
                'storage' => ['50kg Bag', '50 Bags Pallet']
            ],
            'Lafarge Cement' => [
                'products' => ['Lafarge Supaset'],
                'storage' => ['50kg Bag', '50 Bags Pallet']
            ]
        ], false);
    }

    private function seedAgricEquipment()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Agricultural Equipment'],
            ['emoji' => '🚜', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Tractors', [
            'John Deere Fleet' => [
                'products' => ['John Deere 5055E', 'John Deere 5075E'],
                'storage' => ['2WD', '4WD']
            ],
            'Massey Ferguson Fleet' => [
                'products' => ['Massey Ferguson 240', 'Massey Ferguson 265'],
                'storage' => ['2WD', '4WD']
            ],
            'New Holland Fleet' => [
                'products' => ['New Holland TT55', 'New Holland TT75'],
                'storage' => ['2WD', '4WD']
            ]
        ], false);

        $this->seedSubcategoryData($category, 'Water Storage & Pumping Systems', [
            'Jojo Tanks' => [
                'products' => ['Jojo Water Tank'],
                'colors' => ['Green', 'Beige'],
                'storage' => ['2500L', '5000L', '10000L']
            ],
            'Solar Pumps' => [
                'products' => ['Grundfos Solar Pump'],
                'storage' => ['Submersible', 'Surface']
            ]
        ], true);
    }

    private function seedMotherToBe()
    {
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Mother-to-be preparation'],
            ['emoji' => '🤰', 'type' => 'hire_purchase']
        );

        $this->seedSubcategoryData($category, 'Baby Items', [
            'Baby Essentials' => [
                'products' => ['Newborn Starter Kit', 'Hospital Bag Pack'],
                'colors' => ['Blue', 'Pink', 'Neutral'],
                'storage' => ['Standard']
            ],
            'Prams & Travel' => [
                'products' => ['Chelino Travel System', 'Bambino Stroller'],
                'colors' => ['Grey', 'Black', 'Red'],
                'storage' => ['Standard']
            ]
        ], true);
    }

    private function seedSubcategoryData($category, $subcategoryName, $seriesList, $hasColors)
    {
        // Find existing subcategory or create if missing (though we expect them to exist)
        $subcategory = ProductSubCategory::firstOrCreate(
            ['product_category_id' => $category->id, 'name' => $subcategoryName]
        );

        foreach ($seriesList as $seriesName => $data) {
            $series = ProductSeries::firstOrCreate(
                ['product_sub_category_id' => $subcategory->id, 'name' => $seriesName],
                ['description' => "Collection: $seriesName", 'image_url' => null]
            );

            foreach ($data['products'] as $productName) {
                $product = Product::firstOrCreate(
                    ['product_sub_category_id' => $subcategory->id, 'name' => $productName],
                    [
                        'product_series_id' => $series->id,
                        'base_price' => rand(100, 3000),
                        'image_url' => null,
                        'colors' => isset($data['colors']) ? $data['colors'] : ($hasColors ? ['Standard'] : null)
                    ]
                );

                // Add variants (scales)
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
