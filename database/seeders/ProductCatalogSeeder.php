<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCatalogSeeder extends Seeder
{
    
    public function run(): void
    {
        // 22 MicroBiz Main Categories
        $categories = [
            ['id' => 'agric-mechanization', 'name' => 'Agricultural mechanization', 'emoji' => '🚜'],
            ['id' => 'agriculture', 'name' => 'Agriculture', 'emoji' => '🌾'],
            ['id' => 'cleaning-services', 'name' => 'Cleaning Services', 'emoji' => '🧹'],
            ['id' => 'beauty-hair-cosmetics', 'name' => 'Beauty, Hair and Cosmetics', 'emoji' => '💇'],
            ['id' => 'food-production', 'name' => 'Food Production', 'emoji' => '🍞'],
            ['id' => 'butchery', 'name' => 'Butchery Equipment', 'emoji' => '🥩'],
            ['id' => 'events-management', 'name' => 'Events Management', 'emoji' => '🎉'],
            ['id' => 'snack-production', 'name' => 'Snack Production', 'emoji' => '🍿'],
            ['id' => 'food-processing', 'name' => 'Food Processing', 'emoji' => '🥜'],
            ['id' => 'entertainment', 'name' => 'Entertainment', 'emoji' => '🎮'],
            ['id' => 'printing', 'name' => 'Material Printing', 'emoji' => '🖨️'],
            ['id' => 'digital-multimedia', 'name' => 'Digital Multimedia Production', 'emoji' => '📸'],
            ['id' => 'tailoring', 'name' => 'Tailoring', 'emoji' => '✂️'],
            ['id' => 'building-construction', 'name' => 'Building & Construction Equipment', 'emoji' => '🔨'],
            ['id' => 'business-licensing', 'name' => 'Municipal Business License Financing', 'emoji' => '📄'],
            ['id' => 'small-scale-mining', 'name' => 'Small scale mining', 'emoji' => '⛏️'],
            ['id' => 'tuck-shop', 'name' => 'Tuck shop', 'emoji' => '🛍️'],
            ['id' => 'retail', 'name' => 'Retailing', 'emoji' => '🏪'],
            ['id' => 'airtime', 'name' => 'Airtime Vending', 'emoji' => '📱'],
            ['id' => 'delivery-services', 'name' => 'Delivery Services', 'emoji' => '🏍️'],
            ['id' => 'motor-vehicle', 'name' => 'Motor Vehicle Sundries', 'emoji' => '🚗'],
            ['id' => 'photocopying-bulk-printing', 'name' => 'Photocopying & Bulk Printing', 'emoji' => '📄'],

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
                        'base_price' => 280.00, // Base price is effectively the Bronze Package price
                        'image_url' => 'https://via.placeholder.com/150',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Standard scales for ALL MicroBiz products
                    $scales = [
                        ['name' => 'Bronze Package', 'custom_price' => 280.00, 'multiplier' => 1.0],
                        ['name' => 'Silver Package', 'custom_price' => 490.00, 'multiplier' => 1.75], // Approx multiplier
                        ['name' => 'Gold Package', 'custom_price' => 930.00, 'multiplier' => 3.32], // Approx multiplier
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
            // 1. Agricultural mechanization
            'agric-mechanization' => [
                ['name' => 'Maize sheller', 'businesses' => [['name' => 'Maize sheller']]],
                ['name' => 'Water storage and pumping systems', 'businesses' => [['name' => 'Water storage and pumping systems']]],
                ['name' => 'Tractors', 'businesses' => [['name' => 'Tractors']]],
                ['name' => 'Irrigation systems', 'businesses' => [['name' => 'Irrigation systems']]],
                ['name' => 'Land security', 'businesses' => [['name' => 'Land security']]],
                ['name' => 'Incubation', 'businesses' => [['name' => 'Incubation']]],
                ['name' => 'Greenhouses', 'businesses' => [['name' => 'Greenhouses']]],
                ['name' => 'Tobacco bailing machine', 'businesses' => [['name' => 'Tobacco bailing machine']]],
                ['name' => 'Hatchery', 'businesses' => [['name' => 'Hatchery']]],
            ],
            // 2. Agriculture
            'agriculture' => [
                ['name' => 'Cash crop production', 'businesses' => [['name' => 'Cash crop production']]],
                ['name' => 'Supplementary inputs', 'businesses' => [['name' => 'Supplementary inputs']]],
                ['name' => 'Broiler production', 'businesses' => [['name' => 'Broiler production']]],
            ],
            // 3. Cleaning Services
            'cleaning-services' => [
                ['name' => 'Laundry', 'businesses' => [['name' => 'Laundry']]],
                ['name' => 'Car wash', 'businesses' => [['name' => 'Car wash']]],
                ['name' => 'Carpet and fabric', 'businesses' => [['name' => 'Carpet and fabric']]],
            ],
            // 4. Beauty, Hair and Cosmetics
            'beauty-hair-cosmetics' => [
                ['name' => 'Barber & Rasta', 'businesses' => [['name' => 'Barber & Rasta']]],
                ['name' => 'Braiding and weaving', 'businesses' => [['name' => 'Braiding and weaving']]],
                ['name' => 'Wig installation', 'businesses' => [['name' => 'Wig installation']]],
                ['name' => 'Nails and makeup', 'businesses' => [['name' => 'Nails and makeup']]],
                ['name' => 'Saloon equipment', 'businesses' => [['name' => 'Saloon equipment']]],
                ['name' => 'Hair Products and Cosmetics Sales', 'businesses' => [['name' => 'Hair Products and Cosmetics Sales']]],
            ],
            // 5. Food Production
            'food-production' => [
                ['name' => 'Baking', 'businesses' => [['name' => 'Baking']]],
                ['name' => 'Mobile food cart', 'businesses' => [['name' => 'Mobile food cart']]],
                ['name' => 'Takeaway Canteen', 'businesses' => [['name' => 'Takeaway Canteen']]],
                ['name' => 'Chip and burger fryer', 'businesses' => [['name' => 'Chip and burger fryer']]],
            ],
            // 6. Butchery Equipment
            'butchery' => [
                ['name' => 'Commercial Fridges', 'businesses' => [['name' => 'Commercial Fridges']]],
                ['name' => 'Bone cutter', 'businesses' => [['name' => 'Bone cutter']]],
                ['name' => 'Sausage maker', 'businesses' => [['name' => 'Sausage maker']]],
                ['name' => 'Mincemeat maker', 'businesses' => [['name' => 'Mincemeat maker']]],
            ],
            // 7. Events Management
            'events-management' => [
                ['name' => 'PA system', 'businesses' => [['name' => 'PA system']]],
                ['name' => 'Chairs and tables & décor', 'businesses' => [['name' => 'Chairs and tables & décor']]],
                ['name' => 'Tents', 'businesses' => [['name' => 'Tents']]],
                ['name' => 'Balloon décor/room décor', 'businesses' => [['name' => 'Balloon décor/room décor']]],
                ['name' => 'Portable Toilets', 'businesses' => [['name' => 'Portable Toilets']]],
            ],
            // 8. Snack Production
            'snack-production' => [
                ['name' => 'Freezit making', 'businesses' => [['name' => 'Freezit making']]],
                ['name' => 'Maputi making', 'businesses' => [['name' => 'Maputi making']]],
                ['name' => 'Popcorn making', 'businesses' => [['name' => 'Popcorn making']]],
                ['name' => 'Ice making machine', 'businesses' => [['name' => 'Ice making machine']]],
                ['name' => 'Ice cream making machine', 'businesses' => [['name' => 'Ice cream making machine']]],
                ['name' => 'Roasted corn', 'businesses' => [['name' => 'Roasted corn']]],
            ],
            // 9. Food Processing
            'food-processing' => [
                ['name' => 'Peanut butter', 'businesses' => [['name' => 'Peanut butter']]],
                ['name' => 'Cooking oil', 'businesses' => [['name' => 'Cooking oil']]],
                ['name' => 'Grinding mill', 'businesses' => [['name' => 'Grinding mill']]],
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
            // 11. Material Printing
            'printing' => [
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
            // 14. Building & Construction Equipment
            'building-construction' => [
                ['name' => 'Tiling', 'businesses' => [['name' => 'Tiling']]],
                ['name' => 'Carpentry', 'businesses' => [['name' => 'Carpentry']]],
                ['name' => 'Plumbing', 'businesses' => [['name' => 'Plumbing']]],
                ['name' => 'Electrical', 'businesses' => [['name' => 'Electrical']]],
                ['name' => 'Brick & pavers making', 'businesses' => [['name' => 'Brick & pavers making']]],
            ],
            // 15. Municipal Business License Financing
            'business-licensing' => [
                ['name' => 'Business License', 'businesses' => [['name' => 'Business License']]],
            ],
            // 16. Small scale mining
            'small-scale-mining' => [
                ['name' => 'Mining Equipment', 'businesses' => [['name' => 'Mining Equipment']]],
            ],
            // 17. Tuck shop
            'tuck-shop' => [
                ['name' => 'Groceries', 'businesses' => [['name' => 'Groceries']]],
                ['name' => 'Candy shop', 'businesses' => [['name' => 'Candy shop']]],
            ],
            // 18. Retailing
            'retail' => [
                ['name' => 'Stationary shops', 'businesses' => [['name' => 'Stationary shops']]],
                ['name' => 'Cellphone accessories', 'businesses' => [['name' => 'Cellphone accessories']]],
            ],
            // 19. Airtime
            'airtime' => [
                ['name' => 'Airtime vending', 'businesses' => [['name' => 'Airtime vending']]],
            ],
            // 20. Delivery Services
            'delivery-services' => [
                ['name' => 'Motor cycle', 'businesses' => [['name' => 'Motor cycle']]],
            ],
            // 21. Motor Vehicle Sundries
            'motor-vehicle' => [
                ['name' => 'Workshop', 'businesses' => [['name' => 'Workshop']]],
                ['name' => 'Diagnostic', 'businesses' => [['name' => 'Diagnostic']]],
                ['name' => 'Panel beating', 'businesses' => [['name' => 'Panel beating']]],
                ['name' => 'Tire repair services', 'businesses' => [['name' => 'Tire repair services']]],
                ['name' => 'Wheel alignment', 'businesses' => [['name' => 'Wheel alignment']]],
                ['name' => 'Battery services', 'businesses' => [['name' => 'Battery services']]],
                ['name' => 'Battery Charging', 'businesses' => [['name' => 'Battery Charging']]],
            ],
            // 22. Photocopying & Bulk Printing
            'photocopying-bulk-printing' => [
                ['name' => 'Laser printing', 'businesses' => [['name' => 'Laser printing']]],
                ['name' => 'Litho Printing', 'businesses' => [['name' => 'Litho Printing']]],
            ],
        ];

        return $allSubcategories[$categoryId] ?? [];
    }
}