<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCatalogSeeder extends Seeder
{
    
    public function run(): void
    {
        // MicroBiz Main Categories (Updated with new names)
        $categories = [
            ['id' => 'agric-mechanization', 'name' => 'Agricultural Machinery', 'emoji' => '🚜'],
            ['id' => 'agricultural-inputs', 'name' => 'Agricultural Inputs', 'emoji' => '🌾'],
            ['id' => 'chicken-projects', 'name' => 'Chicken Projects', 'emoji' => '🐔'],
            ['id' => 'cleaning-services', 'name' => 'Cleaning Services', 'emoji' => '🧹'],
            ['id' => 'beauty-hair-cosmetics', 'name' => 'Beauty, Hair and Cosmetics', 'emoji' => '💇'],
            ['id' => 'meat-processing', 'name' => 'Meat Processing Equipment', 'emoji' => '🥩'], // Renamed from Butchery
            ['id' => 'events-management', 'name' => 'Events Management', 'emoji' => '🎉'],
            ['id' => 'snack-production', 'name' => 'Snack Production', 'emoji' => '🍿'],
            // Food Processing REMOVED - items moved to Agricultural Machinery
            ['id' => 'entertainment', 'name' => 'Entertainment', 'emoji' => '🎮'],
            ['id' => 'branding-printing', 'name' => 'Branding and Material Printing', 'emoji' => '🖨️'], // Renamed
            ['id' => 'digital-multimedia', 'name' => 'Digital Multimedia Production', 'emoji' => '📸'],
            ['id' => 'tailoring', 'name' => 'Tailoring', 'emoji' => '✂️'],
            ['id' => 'tailoring-machinery', 'name' => 'Tailoring Machinery', 'emoji' => '🧵'], // NEW category
            ['id' => 'building-construction', 'name' => 'Building & Construction Equipment', 'emoji' => '🔨'],
            ['id' => 'business-licensing', 'name' => 'Business Licensing', 'emoji' => '📜'], // Added back
            ['id' => 'small-scale-mining', 'name' => 'Small Scale Mining', 'emoji' => '⛏️'], // Name capitalized
            ['id' => 'grocery-tuckshop', 'name' => 'Grocery and Tuckshop', 'emoji' => '🛍️'], // Renamed from Tuck shop
            ['id' => 'retail-shops', 'name' => 'Retail Shops', 'emoji' => '🏪'], // Renamed from Retailing
            ['id' => 'financial-services-agency', 'name' => 'Financial Services Agency', 'emoji' => '🏦'], // Renamed from Banking Agency
            ['id' => 'bike-delivery', 'name' => 'Bike Delivery Service', 'emoji' => '🏍️'], // Renamed from Delivery Services
            ['id' => 'motor-vehicle-specialised', 'name' => 'Motor Vehicle Specialised Services', 'emoji' => '🚗'], // Renamed
            ['id' => 'photocopying-bulk-printing', 'name' => 'Photocopying & Bulk Printing', 'emoji' => '📄'],
            ['id' => 'water-purification', 'name' => 'Water Bottling and Purification', 'emoji' => '💧'],
        ];
        
        foreach ($categories as $categoryData) {
            $categoryId = DB::table('product_categories')->insertGetId([
                'name' => $categoryData['name'],
                'emoji' => $categoryData['emoji'],
                'type' => 'microbiz',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $subcategories = $this->getSubcategoriesForCategory($categoryData['id']);

            foreach ($subcategories as $subcategoryData) {
                $subcategoryId = DB::table('product_sub_categories')->insertGetId([
                    'product_category_id' => $categoryId,
                    'name' => $subcategoryData['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($subcategoryData['businesses'] as $businessData) {
                    $productId = DB::table('products')->insertGetId([
                        'product_sub_category_id' => $subcategoryId,
                        'name' => $businessData['name'],
                        'base_price' => 280.00, // Base price is effectively the Lite Package price
                        'image_url' => 'https://via.placeholder.com/150',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($businessData['name'] === 'Company Registration') {
                        // Company Registration has no variants/scales, just one standard price
                        DB::table('product_package_sizes')->insert([
                            'product_id' => $productId,
                            'name' => 'Standard',
                            'multiplier' => 1.0,
                            'custom_price' => 195.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }

                    // Standard scales for ALL MicroBiz products (Updated naming)
                    $scales = [
                        ['name' => 'Lite Package', 'custom_price' => 280.00, 'multiplier' => 1.0],
                        ['name' => 'Standard Package', 'custom_price' => 490.00, 'multiplier' => 1.75],
                        ['name' => 'Full House Package', 'custom_price' => 930.00, 'multiplier' => 3.32],
                        ['name' => 'Gold Package', 'custom_price' => 1500.00, 'multiplier' => 5.36], // NEW 4th tier
                    ];

                    foreach ($scales as $scaleData) {
                        DB::table('product_package_sizes')->insert([
                            'product_id' => $productId,
                            'name' => $scaleData['name'],
                            'multiplier' => $scaleData['multiplier'],
                            'custom_price' => $scaleData['custom_price'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $repaymentTerms = [6, 12, 18, 24, 36];
        foreach ($repaymentTerms as $months) {
            DB::table('repayment_terms')->insert([
                'months' => $months,
                'interest_rate' => 10.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function getSubcategoriesForCategory(string $categoryId): array
    {
        $allSubcategories = [
            // 1. Agricultural Machinery (including items from removed Food Processing)
            'agric-mechanization' => [
                ['name' => 'Maize sheller', 'businesses' => [['name' => 'Maize sheller']]],
                ['name' => 'Water storage and pumping systems', 'businesses' => [['name' => 'Water storage and pumping systems']]],
                ['name' => 'Tractors', 'businesses' => [['name' => 'Tractors']]],
                ['name' => 'Irrigation systems', 'businesses' => [['name' => 'Irrigation systems']]],
                ['name' => 'Land security', 'businesses' => [['name' => 'Land security']]],
                ['name' => 'Incubation', 'businesses' => [['name' => 'Incubation']]],
                ['name' => 'Greenhouses', 'businesses' => [['name' => 'Greenhouses']]],
                ['name' => 'Tobacco bailing machine', 'businesses' => [['name' => 'Tobacco bailing machine']]],
                // Moved from Food Processing
                ['name' => 'Peanut butter machine', 'businesses' => [['name' => 'Peanut butter machine']]],
                ['name' => 'Cooking oil press', 'businesses' => [['name' => 'Cooking oil press']]],
                ['name' => 'Grinding mill', 'businesses' => [['name' => 'Grinding mill']]],
            ],
            // 2. Agricultural Inputs
            'agricultural-inputs' => [
                ['name' => 'Fertilizer', 'businesses' => [['name' => 'Fertilizer']]],
                ['name' => 'Seed + Chemicals', 'businesses' => [['name' => 'Seed + Chemicals']]],
                ['name' => 'Combo (Fertilizer + Seed + Chemicals)', 'businesses' => [['name' => 'Combo (Fertilizer + Seed + Chemicals)']]],
            ],
            // 3. Chicken Projects
            'chicken-projects' => [
                ['name' => 'Broiler Production', 'businesses' => [['name' => 'Broiler Production']]],
                ['name' => 'Egg Hatchery', 'businesses' => [['name' => 'Egg Hatchery']]],
            ],
            // 4. Cleaning Services
            'cleaning-services' => [
                ['name' => 'Laundry', 'businesses' => [['name' => 'Laundry']]],
                ['name' => 'Car wash', 'businesses' => [['name' => 'Car wash']]],
                ['name' => 'Carpet and fabric', 'businesses' => [['name' => 'Carpet and fabric']]],
            ],
            // 5. Beauty, Hair and Cosmetics
            'beauty-hair-cosmetics' => [
                ['name' => 'Barber & Rasta', 'businesses' => [['name' => 'Barber & Rasta']]],
                ['name' => 'Braiding and weaving', 'businesses' => [['name' => 'Braiding and weaving']]],
                ['name' => 'Wig installation', 'businesses' => [['name' => 'Wig installation']]],
                ['name' => 'Nails and makeup', 'businesses' => [['name' => 'Nails and makeup']]],
                ['name' => 'Saloon equipment', 'businesses' => [['name' => 'Saloon equipment']]],
                ['name' => 'Hair Products and Cosmetics Sales', 'businesses' => [['name' => 'Hair Products and Cosmetics Sales']]],
            ],
            // 6. Food Production

            // 7. Meat Processing Equipment (renamed from Butchery)
            'meat-processing' => [
                ['name' => 'Commercial Fridges', 'businesses' => [['name' => 'Commercial Fridges']]],
                ['name' => 'Bone cutter', 'businesses' => [['name' => 'Bone cutter']]],
                ['name' => 'Sausage maker', 'businesses' => [['name' => 'Sausage maker']]],
                ['name' => 'Mincemeat maker', 'businesses' => [['name' => 'Mincemeat maker']]],
                ['name' => 'Chicken plucker', 'businesses' => [['name' => 'Chicken plucker']]], // NEW
            ],
            // 8. Events Management
            'events-management' => [
                ['name' => 'PA system', 'businesses' => [['name' => 'PA system']]],
                ['name' => 'Chairs and tables & décor', 'businesses' => [['name' => 'Chairs and tables & décor']]],
                ['name' => 'Tents', 'businesses' => [['name' => 'Tents']]],
                ['name' => 'Balloon décor/room décor', 'businesses' => [['name' => 'Balloon décor/room décor']]],
                ['name' => 'Portable Toilets', 'businesses' => [['name' => 'Portable Toilets']]],
            ],
            // 9. Snack Production
            'snack-production' => [
                ['name' => 'Freezit making', 'businesses' => [['name' => 'Freezit making']]],
                ['name' => 'Maputi making', 'businesses' => [['name' => 'Maputi making']]],
                ['name' => 'Popcorn making', 'businesses' => [['name' => 'Popcorn making']]],
                ['name' => 'Ice making machine', 'businesses' => [['name' => 'Ice making machine']]],
                ['name' => 'Ice cream making machine', 'businesses' => [['name' => 'Ice cream making machine']]],
                ['name' => 'Roasted corn', 'businesses' => [['name' => 'Roasted corn']]],
            ],
            // 10. Entertainment
            'entertainment' => [
                ['name' => 'Snooker table', 'businesses' => [['name' => 'Snooker table']]],
                ['name' => 'Slug', 'businesses' => [['name' => 'Slug']]],
                ['name' => 'Gaming (ps4, monitors)', 'businesses' => [['name' => 'Gaming (ps4, monitors)']]],
                ['name' => 'DJ PA system', 'businesses' => [['name' => 'DJ PA system']]],
                ['name' => 'Internet Café', 'businesses' => [['name' => 'Internet Café']]],
                ['name' => 'Movie Projectors', 'businesses' => [['name' => 'Movie Projectors']]],
                ['name' => 'Musical Instruments', 'businesses' => [['name' => 'Musical Instruments']]],
                ['name' => 'Quad bikes', 'businesses' => [['name' => 'Quad bikes']]],
            ],
            // 11. Branding and Material Printing (renamed)
            'branding-printing' => [
                ['name' => 'Tshirt & cap printing', 'businesses' => [['name' => 'Tshirt & cap printing']]],
                ['name' => 'Mug printing', 'businesses' => [['name' => 'Mug printing']]],
                ['name' => 'Embroidery printing', 'businesses' => [['name' => 'Embroidery printing']]],
                ['name' => 'Larger scale format printing', 'businesses' => [['name' => 'Larger scale format printing']]],
            ],
            // 12. Digital Multimedia Production
            'digital-multimedia' => [
                ['name' => 'Photography', 'businesses' => [['name' => 'Photography']]],
                ['name' => 'Videography', 'businesses' => [['name' => 'Videography']]],
            ],
            // 13. Tailoring
            'tailoring' => [
                ['name' => 'Jersey production', 'businesses' => [['name' => 'Jersey production']]],
                ['name' => 'Curtain production', 'businesses' => [['name' => 'Curtain production']]],
                ['name' => 'Uniform production', 'businesses' => [['name' => 'Uniform production']]],
                ['name' => 'Work suit & dust coat production', 'businesses' => [['name' => 'Work suit & dust coat production']]],
                ['name' => 'Sunhat production', 'businesses' => [['name' => 'Sunhat production']]],
                ['name' => 'Tshirt production', 'businesses' => [['name' => 'Tshirt production']]],
                ['name' => 'Bonnet, night ware & scrunchie production', 'businesses' => [['name' => 'Bonnet, night ware & scrunchie production']]],
            ],
            // 14. Tailoring Machinery (NEW separate category)
            'tailoring-machinery' => [
                ['name' => 'Sewing machines', 'businesses' => [['name' => 'Sewing machines']]],
                ['name' => 'Embroidery machines', 'businesses' => [['name' => 'Embroidery machines']]],
                ['name' => 'Overlock machines', 'businesses' => [['name' => 'Overlock machines']]],
                ['name' => 'Cutting equipment', 'businesses' => [['name' => 'Cutting equipment']]],
            ],
            // 15. Building & Construction Equipment
            'building-construction' => [
                ['name' => 'Tiling', 'businesses' => [['name' => 'Tiling']]],
                ['name' => 'Carpentry', 'businesses' => [['name' => 'Carpentry']]],
                ['name' => 'Plumbing', 'businesses' => [['name' => 'Plumbing']]],
                ['name' => 'Electrical', 'businesses' => [['name' => 'Electrical']]],
                ['name' => 'Brick & pavers making', 'businesses' => [['name' => 'Brick & pavers making']]],
            ],
            // 16. Business Licensing (Added back)
            'business-licensing' => [
                ['name' => 'Liquor Store License', 'businesses' => [['name' => 'Liquor Store License']]],
                ['name' => 'Trading License', 'businesses' => [['name' => 'Trading License']]],
                ['name' => 'Company Registration', 'businesses' => [['name' => 'Company Registration']]],
            ],
            // 17. Small Scale Mining
            'small-scale-mining' => [
                ['name' => 'Mining Equipment', 'businesses' => [['name' => 'Mining Equipment']]],
            ],
            // 18. Grocery and Tuckshop (renamed)
            'grocery-tuckshop' => [
                ['name' => 'Groceries', 'businesses' => [['name' => 'Groceries']]],
                ['name' => 'Candy and Confectionery Shop', 'businesses' => [['name' => 'Candy and Confectionery Shop']]], // Renamed
            ],
            // 19. Retail Shops (expanded)
            'retail-shops' => [
                ['name' => 'Cellphone Accessories', 'businesses' => [['name' => 'Cellphone Accessories']]],
                ['name' => 'Hardware', 'businesses' => [['name' => 'Hardware']]],
                ['name' => 'Automotive Motor Spares', 'businesses' => [['name' => 'Automotive Motor Spares']]],
                ['name' => 'General Dealer', 'businesses' => [['name' => 'General Dealer (plastic ware, cango pots, comforters, mini hardware, protective clothing, blankets)']]],
                ['name' => 'Stationery Shop', 'businesses' => [['name' => 'Stationery Shop']]],
            ],
            // 20. Financial Services Agency (renamed from Banking Agency)
            'financial-services-agency' => [
                ['name' => 'Ecocash', 'businesses' => [['name' => 'Ecocash Agency']]],
                ['name' => 'ZB Bank', 'businesses' => [['name' => 'ZB Bank Agency']]],
            ],
            // 21. Bike Delivery Service (renamed)
            'bike-delivery' => [
                ['name' => 'Motor cycle', 'businesses' => [['name' => 'Motor cycle']]],
            ],
            // 22. Motor Vehicle Specialised Services (renamed)
            'motor-vehicle-specialised' => [
                ['name' => 'Workshop', 'businesses' => [['name' => 'Workshop']]],
                ['name' => 'Diagnostic', 'businesses' => [['name' => 'Diagnostic']]],
                ['name' => 'Panel beating', 'businesses' => [['name' => 'Panel beating']]],
                ['name' => 'Tire repair services', 'businesses' => [['name' => 'Tire repair services']]],
                ['name' => 'Wheel alignment', 'businesses' => [['name' => 'Wheel alignment']]],
                ['name' => 'Battery services', 'businesses' => [['name' => 'Battery services']]],
                ['name' => 'Battery Charging', 'businesses' => [['name' => 'Battery Charging']]],
            ],
            // 23. Photocopying & Bulk Printing
            'photocopying-bulk-printing' => [
                ['name' => 'Laser printing', 'businesses' => [['name' => 'Laser printing']]],
                ['name' => 'Litho Printing', 'businesses' => [['name' => 'Litho Printing']]],
            ],
            // 24. Water Purification
            'water-purification' => [
                ['name' => 'Water Bottling Station', 'businesses' => [['name' => 'Water Bottling Station']]],
                ['name' => 'Purification Systems', 'businesses' => [['name' => 'Purification Systems']]],
            ],
        ];

        return $allSubcategories[$categoryId] ?? [];
    }
}