<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 22 MicroBiz Main Categories (Airtime and Delivery Services are now separate)
        $categories = [
            ['id' => 'agric-mechanization', 'name' => 'Agric mechanization', 'emoji' => '🚜'],
            ['id' => 'agriculture', 'name' => 'Agriculture', 'emoji' => '🌾'],
            ['id' => 'cleaning-services', 'name' => 'Cleaning Services', 'emoji' => '🧹'],
            ['id' => 'beauty-hair-cosmetics', 'name' => 'Beauty, Hair and Cosmetics', 'emoji' => '💇'],
            ['id' => 'food-production', 'name' => 'Food Production', 'emoji' => '🍞'],
            ['id' => 'butchery', 'name' => 'Butchery', 'emoji' => '🥩'],
            ['id' => 'events-management', 'name' => 'Events Management', 'emoji' => '🎉'],
            ['id' => 'snack-production', 'name' => 'Snack Production', 'emoji' => '🍿'],
            ['id' => 'food-processing', 'name' => 'Food Processing', 'emoji' => '🥜'],
            ['id' => 'entertainment', 'name' => 'Entertainment', 'emoji' => '🎮'],
            ['id' => 'printing', 'name' => 'Printing', 'emoji' => '🖨️'],
            ['id' => 'digital-multimedia', 'name' => 'Digital Multimedia Production', 'emoji' => '📸'],
            ['id' => 'tailoring', 'name' => 'Tailoring', 'emoji' => '✂️'],
            ['id' => 'building-construction', 'name' => 'Building & Construction', 'emoji' => '🔨'],
            ['id' => 'business-licensing', 'name' => 'Business Licensing', 'emoji' => '📄'],
            ['id' => 'small-scale-mining', 'name' => 'Small scale mining hire', 'emoji' => '⛏️'],
            ['id' => 'tuck-shop', 'name' => 'Tuck shop', 'emoji' => '🛍️'],
            ['id' => 'retail', 'name' => 'Retail', 'emoji' => '🏪'],
            ['id' => 'airtime', 'name' => 'Airtime', 'emoji' => '📱'],
            ['id' => 'delivery-services', 'name' => 'Delivery Services', 'emoji' => '🏍️'],
            ['id' => 'motor-vehicle', 'name' => 'Motor vehicle', 'emoji' => '🚗'],
            ['id' => 'key-replacement', 'name' => 'Key Replacement', 'emoji' => '🔑'],
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
                        'base_price' => $businessData['basePrice'],
                        'image_url' => 'https://via.placeholder.com/150',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($businessData['scales'] as $scaleData) {
                        DB::table('product_package_sizes')->insert([
                            'product_id' => $productId,
                            'name' => $scaleData['name'],
                            'multiplier' => $scaleData['multiplier'],
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
            // 1. Agric mechanization
            'agric-mechanization' => [
                ['name' => 'Maize sheller', 'businesses' => $this->getBusinessesForSubcategory('Maize sheller')],
                ['name' => 'Water storage and pumping systems', 'businesses' => $this->getBusinessesForSubcategory('Water storage and pumping systems')],
                ['name' => 'Tractors', 'businesses' => $this->getBusinessesForSubcategory('Tractors')],
                ['name' => 'Irrigation systems', 'businesses' => $this->getBusinessesForSubcategory('Irrigation systems')],
                ['name' => 'Land security', 'businesses' => $this->getBusinessesForSubcategory('Land security')],
                ['name' => 'Incubation', 'businesses' => $this->getBusinessesForSubcategory('Incubation')],
                ['name' => 'Greenhouses', 'businesses' => $this->getBusinessesForSubcategory('Greenhouses')],
                ['name' => 'Tobacco bailing machine', 'businesses' => $this->getBusinessesForSubcategory('Tobacco bailing machine')],
            ],
            // 2. Agriculture
            'agriculture' => [
                ['name' => 'Cash crop production', 'businesses' => $this->getBusinessesForSubcategory('Cash crop production')],
                ['name' => 'Supplementary inputs', 'businesses' => $this->getBusinessesForSubcategory('Supplementary inputs')],
                ['name' => 'Broiler production', 'businesses' => $this->getBusinessesForSubcategory('Broiler production')],
                ['name' => 'Egg hatching & incubation', 'businesses' => $this->getBusinessesForSubcategory('Egg hatching & incubation')],
            ],
            // 3. Cleaning Services
            'cleaning-services' => [
                ['name' => 'Laundry', 'businesses' => $this->getBusinessesForSubcategory('Laundry')],
                ['name' => 'Car wash', 'businesses' => $this->getBusinessesForSubcategory('Car wash')],
                ['name' => 'Carpet and fabric', 'businesses' => $this->getBusinessesForSubcategory('Carpet and fabric')],
            ],
            // 4. Beauty, Hair and Cosmetics
            'beauty-hair-cosmetics' => [
                ['name' => 'Barber', 'businesses' => $this->getBusinessesForSubcategory('Barber')],
                ['name' => 'Braiding and weaving', 'businesses' => $this->getBusinessesForSubcategory('Braiding and weaving')],
                ['name' => 'Wig installation', 'businesses' => $this->getBusinessesForSubcategory('Wig installation')],
                ['name' => 'Nails and makeup', 'businesses' => $this->getBusinessesForSubcategory('Nails and makeup')],
                ['name' => 'Saloon equipment', 'businesses' => $this->getBusinessesForSubcategory('Saloon equipment')],
                ['name' => 'Hair Products and Cosmetics Sales', 'businesses' => $this->getBusinessesForSubcategory('Hair Products and Cosmetics Sales')],
            ],
            // 5. Food Production
            'food-production' => [
                ['name' => 'Baking', 'businesses' => $this->getBusinessesForSubcategory('Baking')],
                ['name' => 'Catering', 'businesses' => $this->getBusinessesForSubcategory('Catering')],
                ['name' => 'Mobile food cart', 'businesses' => $this->getBusinessesForSubcategory('Mobile food cart')],
                ['name' => 'Canteen', 'businesses' => $this->getBusinessesForSubcategory('Canteen')],
                ['name' => 'Chip and burger fryer', 'businesses' => $this->getBusinessesForSubcategory('Chip and burger fryer')],
            ],
            // 6. Butchery
            'butchery' => [
                ['name' => 'Butchery equipment', 'businesses' => $this->getBusinessesForSubcategory('Butchery equipment')],
                ['name' => 'Sausage maker', 'businesses' => $this->getBusinessesForSubcategory('Sausage maker')],
                ['name' => 'Mincemeat production', 'businesses' => $this->getBusinessesForSubcategory('Mincemeat production')],
            ],
            // 7. Events Management
            'events-management' => [
                ['name' => 'PA system', 'businesses' => $this->getBusinessesForSubcategory('PA system')],
                ['name' => 'Chairs and tables & décor', 'businesses' => $this->getBusinessesForSubcategory('Chairs and tables & décor')],
                ['name' => 'Tents', 'businesses' => $this->getBusinessesForSubcategory('Tents')],
                ['name' => 'Balloon décor/room décor', 'businesses' => $this->getBusinessesForSubcategory('Balloon décor/room décor')],
                ['name' => 'Portable toilet hiring', 'businesses' => $this->getBusinessesForSubcategory('Portable toilet hiring')],
            ],
            // 8. Snack Production
            'snack-production' => [
                ['name' => 'Freezit making', 'businesses' => $this->getBusinessesForSubcategory('Freezit making')],
                ['name' => 'Maputi making', 'businesses' => $this->getBusinessesForSubcategory('Maputi making')],
                ['name' => 'Popcorn making', 'businesses' => $this->getBusinessesForSubcategory('Popcorn making')],
                ['name' => 'Ice making machine', 'businesses' => $this->getBusinessesForSubcategory('Ice making machine')],
                ['name' => 'Ice cream making machine', 'businesses' => $this->getBusinessesForSubcategory('Ice cream making machine')],
                ['name' => 'Roasted corn', 'businesses' => $this->getBusinessesForSubcategory('Roasted corn')],
            ],
            // 9. Food Processing
            'food-processing' => [
                ['name' => 'Peanut butter', 'businesses' => $this->getBusinessesForSubcategory('Peanut butter')],
                ['name' => 'Cooking oil', 'businesses' => $this->getBusinessesForSubcategory('Cooking oil')],
                ['name' => 'Grinding mill', 'businesses' => $this->getBusinessesForSubcategory('Grinding mill')],
            ],
            // 10. Entertainment
            'entertainment' => [
                ['name' => 'Snooker table', 'businesses' => $this->getBusinessesForSubcategory('Snooker table')],
                ['name' => 'Slug', 'businesses' => $this->getBusinessesForSubcategory('Slug')],
                ['name' => 'Gaming (ps4, monitors)', 'businesses' => $this->getBusinessesForSubcategory('Gaming (ps4, monitors)')],
                ['name' => 'DJ PA system', 'businesses' => $this->getBusinessesForSubcategory('DJ PA system')],
                ['name' => 'Internet Café', 'businesses' => $this->getBusinessesForSubcategory('Internet Café')],
                ['name' => 'Movie Projectors', 'businesses' => $this->getBusinessesForSubcategory('Movie Projectors')],
                ['name' => 'Instruments Hiring', 'businesses' => $this->getBusinessesForSubcategory('Instruments Hiring')],
            ],
            // 11. Printing
            'printing' => [
                ['name' => 'Tshirt & cap printing', 'businesses' => $this->getBusinessesForSubcategory('Tshirt & cap printing')],
                ['name' => 'Mug printing', 'businesses' => $this->getBusinessesForSubcategory('Mug printing')],
                ['name' => 'Laser printing', 'businesses' => $this->getBusinessesForSubcategory('Laser printing')],
                ['name' => 'Bulk paper printing', 'businesses' => $this->getBusinessesForSubcategory('Bulk paper printing')],
                ['name' => 'Embroidery printing', 'businesses' => $this->getBusinessesForSubcategory('Embroidery printing')],
                ['name' => 'Larger scale format printing', 'businesses' => $this->getBusinessesForSubcategory('Larger scale format printing')],
            ],
            // 12. Digital Multimedia Production
            'digital-multimedia' => [
                ['name' => 'Photography', 'businesses' => $this->getBusinessesForSubcategory('Photography')],
                ['name' => 'Videography', 'businesses' => $this->getBusinessesForSubcategory('Videography')],
            ],
            // 13. Tailoring
            'tailoring' => [
                ['name' => 'Jersey making', 'businesses' => $this->getBusinessesForSubcategory('Jersey making')],
                ['name' => 'Curtain making', 'businesses' => $this->getBusinessesForSubcategory('Curtain making')],
                ['name' => 'Uniform making', 'businesses' => $this->getBusinessesForSubcategory('Uniform making')],
                ['name' => 'Work suit & dust coat production', 'businesses' => $this->getBusinessesForSubcategory('Work suit & dust coat production')],
                ['name' => 'Sunhat production', 'businesses' => $this->getBusinessesForSubcategory('Sunhat production')],
                ['name' => 'Tshirt production', 'businesses' => $this->getBusinessesForSubcategory('Tshirt production')],
                ['name' => 'Bonnet, night ware & scrunchie production', 'businesses' => $this->getBusinessesForSubcategory('Bonnet, night ware & scrunchie production')],
            ],
            // 14. Building & Construction
            'building-construction' => [
                ['name' => 'Tiling', 'businesses' => $this->getBusinessesForSubcategory('Tiling')],
                ['name' => 'Carpentry', 'businesses' => $this->getBusinessesForSubcategory('Carpentry')],
                ['name' => 'Plumbing', 'businesses' => $this->getBusinessesForSubcategory('Plumbing')],
                ['name' => 'Electrical', 'businesses' => $this->getBusinessesForSubcategory('Electrical')],
                ['name' => 'Brick & pavers making', 'businesses' => $this->getBusinessesForSubcategory('Brick & pavers making')],
            ],
            // 15. Business Licensing
            'business-licensing' => [
                ['name' => 'Business License', 'businesses' => $this->getBusinessesForSubcategory('Business License')],
            ],
            // 16. Small scale mining hire
            'small-scale-mining' => [
                ['name' => 'Mining Equipment', 'businesses' => $this->getBusinessesForSubcategory('Mining Equipment')],
            ],
            // 17. Tuck shop
            'tuck-shop' => [
                ['name' => 'Groceries', 'businesses' => $this->getBusinessesForSubcategory('Groceries')],
                ['name' => 'Candy shop', 'businesses' => $this->getBusinessesForSubcategory('Candy shop')],
            ],
            // 18. Retail
            'retail' => [
                ['name' => 'Stationary shops', 'businesses' => $this->getBusinessesForSubcategory('Stationary shops')],
                ['name' => 'Cellphone accessories', 'businesses' => $this->getBusinessesForSubcategory('Cellphone accessories')],
            ],
            // 19. Airtime
            'airtime' => [
                ['name' => 'Airtime vending', 'businesses' => $this->getBusinessesForSubcategory('Airtime vending')],
            ],
            // 20. Delivery Services
            'delivery-services' => [
                ['name' => 'Motor cycle', 'businesses' => $this->getBusinessesForSubcategory('Motor cycle')],
            ],
            // 21. Motor vehicle
            'motor-vehicle' => [
                ['name' => 'Workshop', 'businesses' => $this->getBusinessesForSubcategory('Workshop')],
                ['name' => 'Diagnostic', 'businesses' => $this->getBusinessesForSubcategory('Diagnostic')],
                ['name' => 'Panel beating', 'businesses' => $this->getBusinessesForSubcategory('Panel beating')],
                ['name' => 'Tire repair services', 'businesses' => $this->getBusinessesForSubcategory('Tire repair services')],
                ['name' => 'Wheel alignment', 'businesses' => $this->getBusinessesForSubcategory('Wheel alignment')],
                ['name' => 'Battery services', 'businesses' => $this->getBusinessesForSubcategory('Battery services')],
            ],
            // 22. Key Replacement
            'key-replacement' => [
                ['name' => 'Locksmith', 'businesses' => $this->getBusinessesForSubcategory('Locksmith')],
                ['name' => 'Car keys', 'businesses' => $this->getBusinessesForSubcategory('Car keys')],
            ],
        ];

        return $allSubcategories[$categoryId] ?? [];
    }

    private function getBusinessesForSubcategory(string $subcategoryName): array
    {
        $allBusinesses = [
            'Cash Crops' => [
                ['name' => 'Cotton', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Maize', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Potato', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Soya Beans', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sugar Beans', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sunflower', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sweet Potato', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
            ],
            'Livestock & Poultry' => [
                ['name' => 'Animal Feed Production', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Bee keeping', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Cattle Services', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Chickens Layers', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Chickens Rearing', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Goat Rearing', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Fish Farming', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Rabbits', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
                ['name' => 'Piggery', 'basePrice' => 600, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3], ['name' => 'Commercial', 'multiplier' => 5]]],
            ],
            'Food Services' => [
                ['name' => 'Baking – Bread', 'basePrice' => 1000, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Baking - Cakes & confectionery', 'basePrice' => 1000, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Chip Fryer', 'basePrice' => 800, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Canteen', 'basePrice' => 1200, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Mobile food kiosk', 'basePrice' => 900, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Outside catering', 'basePrice' => 1100, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
            ],
            'Trade Tools' => [
                ['name' => 'Electrical', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
                ['name' => 'Plumbing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
                ['name' => 'Tiling', 'basePrice' => 1000, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
                ['name' => 'Brickwork tools', 'basePrice' => 800, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
            ],
            'Entertainment Equipment' => [
                ['name' => 'Musical Instruments Hire', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'PA System', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Professional Setup', 'multiplier' => 3]]],
                ['name' => 'Snooker Table', 'basePrice' => 3000, 'scales' => [['name' => 'Single Table', 'multiplier' => 1], ['name' => 'Multiple Tables', 'multiplier' => 2], ['name' => 'Full Hall', 'multiplier' => 3]]],
                ['name' => 'Slug Table', 'basePrice' => 2500, 'scales' => [['name' => 'Single Table', 'multiplier' => 1], ['name' => 'Multiple Tables', 'multiplier' => 2], ['name' => 'Full Hall', 'multiplier' => 3]]],
            ],
            'Event Equipment' => [
                ['name' => 'Chairs & table', 'basePrice' => 1200, 'scales' => [['name' => 'Small Event', 'multiplier' => 1], ['name' => 'Medium Event', 'multiplier' => 2], ['name' => 'Large Event', 'multiplier' => 3]]],
                ['name' => 'Tent', 'basePrice' => 2000, 'scales' => [['name' => 'Small Tent', 'multiplier' => 1], ['name' => 'Medium Tent', 'multiplier' => 2], ['name' => 'Large Tent', 'multiplier' => 3]]],
                ['name' => 'Decor', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Red Carpet and accessories', 'basePrice' => 800, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Portable Toilet Hire', 'basePrice' => 1000, 'scales' => [['name' => 'Basic Units', 'multiplier' => 1], ['name' => 'Standard Units', 'multiplier' => 2], ['name' => 'Premium Units', 'multiplier' => 3]]],
                ['name' => 'Interactive Big Screen Monitors', 'basePrice' => 3000, 'scales' => [['name' => 'Single Screen', 'multiplier' => 1], ['name' => 'Multiple Screens', 'multiplier' => 2], ['name' => 'Full Setup', 'multiplier' => 3]]],
            ],
            'Beauty Services' => [
                ['name' => 'Barber', 'basePrice' => 400, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
                ['name' => 'Hair Salon', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
                ['name' => 'Nail Installation', 'basePrice' => 600, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
            ],
            'Manufacturing' => [
                ['name' => 'Detergent Chemicals', 'basePrice' => 1200, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3]]],
                ['name' => 'Fence Making', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Equipment', 'multiplier' => 1], ['name' => 'Standard Equipment', 'multiplier' => 2], ['name' => 'Industrial Equipment', 'multiplier' => 3]]],
                ['name' => 'Furniture –Carpentry', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 2], ['name' => 'Full Workshop', 'multiplier' => 3]]],
                ['name' => 'Soap production', 'basePrice' => 1000, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3]]],
                ['name' => 'Ice Making', 'basePrice' => 2000, 'scales' => [['name' => 'Small Production', 'multiplier' => 1], ['name' => 'Medium Production', 'multiplier' => 2], ['name' => 'Large Production', 'multiplier' => 3]]],
                ['name' => 'Welding', 'basePrice' => 1600, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Professional Setup', 'multiplier' => 3]]],
            ],
            'Agricultural Equipment' => [
                ['name' => 'Egg Incubator', 'basePrice' => 1500, 'scales' => [['name' => 'Small Capacity', 'multiplier' => 1], ['name' => 'Medium Capacity', 'multiplier' => 2], ['name' => 'Large Capacity', 'multiplier' => 3]]],
                ['name' => 'Indigenous Agric Pack', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Pack', 'multiplier' => 1], ['name' => 'Standard Pack', 'multiplier' => 2], ['name' => 'Complete Pack', 'multiplier' => 3]]],
                ['name' => 'Grinding Mill', 'basePrice' => 3000, 'scales' => [['name' => 'Small Mill', 'multiplier' => 1], ['name' => 'Medium Mill', 'multiplier' => 2], ['name' => 'Industrial Mill', 'multiplier' => 3]]],
                ['name' => 'Green House', 'basePrice' => 2500, 'scales' => [['name' => 'Small Greenhouse', 'multiplier' => 1], ['name' => 'Medium Greenhouse', 'multiplier' => 2], ['name' => 'Large Greenhouse', 'multiplier' => 3]]],
                ['name' => 'Farm Security', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Security', 'multiplier' => 1], ['name' => 'Standard Security', 'multiplier' => 2], ['name' => 'Advanced Security', 'multiplier' => 3]]],
                ['name' => 'Micro Irrigation Systems', 'basePrice' => 2200, 'scales' => [['name' => 'Small System', 'multiplier' => 1], ['name' => 'Medium System', 'multiplier' => 2], ['name' => 'Large System', 'multiplier' => 3]]],
                ['name' => 'Tractors & accessories', 'basePrice' => 15000, 'scales' => [['name' => 'Basic Tractor', 'multiplier' => 1], ['name' => 'Standard Tractor', 'multiplier' => 1.5], ['name' => 'Full Package', 'multiplier' => 2]]],
            ],
            'Food Production' => [
                ['name' => 'Cooking Oil Production', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
                ['name' => 'Dry Food Repackaging', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
                ['name' => 'Freezit making', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
                ['name' => 'Maputi production', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
                ['name' => 'Peanut Butter Making', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
                ['name' => 'Roasted Corn/Peanuts', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Meat Services' => [
                ['name' => 'Butchery', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
                ['name' => 'Meat Cutter', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
                ['name' => 'Mince Making', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
                ['name' => 'Sausage Production', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
            ],
            'Mining Equipment' => [
                ['name' => 'Water Extraction', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
                ['name' => 'Drilling', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
                ['name' => 'Industrial generators', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
            ],
            'Printing Services' => [
                ['name' => 'Mugs-Cup', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Vehicle Branding', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Digital Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'D.T.F Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Screen Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Plans printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'T Shirt & Cap Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Photocopy', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Photo printing instant', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Service Equipment' => [
                ['name' => 'Bar', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Butchery', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Car key programming', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Cell repair', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Car wash', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Cleaning commercial service', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Internet', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Gaming', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Gas Station', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Grass Cutting', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Gymnasium', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Motor cycle delivery', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Laptop repairs', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Laundry Service', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Lock Smith Service', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Photography Studio', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Photo printing', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Pre School', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Satellite Dish Installation', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Saw Mill', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Shop Accessories', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Shop Fitting', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
                ['name' => 'Videography', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Retail Businesses' => [
                ['name' => 'Agro', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Book', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Candy', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Cell phone', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Cell phone Accessories', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Ceramics Tiles', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Clothing (Men/Women: Formal/Informal)', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Cosmetics', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Hair (braids, wigs, weaves)', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Hats & Caps', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Herbal', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Fabric & textile', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Jewellery', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Shoes - Men\'s Sports', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
                ['name' => 'Solar & Accessories', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
            ],
            'Tailoring Services' => [
                ['name' => 'Embroidery', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.6], ['name' => 'Industrial Setup', 'multiplier' => 2.8]]],
                ['name' => 'Knitting – jersey manufacturing', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.6], ['name' => 'Industrial Setup', 'multiplier' => 2.8]]],
                ['name' => 'Industrial overlocking', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.6], ['name' => 'Industrial Setup', 'multiplier' => 2.8]]],
                ['name' => 'Domestic sewing machine', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.6], ['name' => 'Industrial Setup', 'multiplier' => 2.8]]],
            ],
            'Trading Services' => [
                ['name' => 'Airtime scratch cards distribution', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
                ['name' => 'Tuck shop -groceries', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
                ['name' => 'Tyres', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
                ['name' => 'Windscreen Replacement', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
            ],
            'Airtime vending' => [
                ['name' => 'Airtime vending equipment', 'basePrice' => 500, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
            ],
            'Motor cycle' => [
                ['name' => 'Delivery motorcycle', 'basePrice' => 2500, 'scales' => [['name' => 'Single Bike', 'multiplier' => 1], ['name' => 'Two Bikes', 'multiplier' => 2], ['name' => 'Fleet', 'multiplier' => 3]]],
            ],
            'Vehicle Services' => [
                ['name' => 'Air con re-gassing', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
                ['name' => 'Tyre Fitting', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
                ['name' => 'Vehicle Alignment', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
                ['name' => 'Vehicle diagnosis', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
                ['name' => 'Vehicle Repairs Workshop', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
                ['name' => 'Vehicle panel beating', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Vocational Services' => [
                ['name' => 'Network marketing admission Fees (Avon, B.B.B, Forever Living, Honey)', 'basePrice' => 300, 'scales' => [['name' => 'Individual', 'multiplier' => 1], ['name' => 'Small Group', 'multiplier' => 2], ['name' => 'Large Group', 'multiplier' => 4]]],
                ['name' => 'Nurse Aid', 'basePrice' => 300, 'scales' => [['name' => 'Individual', 'multiplier' => 1], ['name' => 'Small Group', 'multiplier' => 2], ['name' => 'Large Group', 'multiplier' => 4]]],
            ],
            'Wedding Services' => [
                ['name' => 'Accessories ( Artificial Flowers, Crown, Ring Basset)', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 1.8], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Bridal gown', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 1.8], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Bridal team', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 1.8], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Groom suit', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 1.8], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'High Back Chair', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 1.8], ['name' => 'Premium Package', 'multiplier' => 3]]],
            ],
        ];

        // Return specific businesses if found, otherwise return a default business for the subcategory
        return $allBusinesses[$subcategoryName] ?? [
            [
                'name' => $subcategoryName . ' Equipment',
                'basePrice' => 500,
                'scales' => [
                    ['name' => 'Small', 'multiplier' => 1],
                    ['name' => 'Medium', 'multiplier' => 2],
                    ['name' => 'Large', 'multiplier' => 3],
                ]
            ]
        ];
    }
}