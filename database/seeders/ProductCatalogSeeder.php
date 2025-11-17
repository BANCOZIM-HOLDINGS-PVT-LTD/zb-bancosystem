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
            ['id' => 'agric-mechanization', 'name' => 'Agric mechanization', 'emoji' => '🚜'],
            ['id' => 'agriculture', 'name' => 'Agriculture', 'emoji' => '🌾'],
            ['id' => 'cleaning-services', 'name' => 'Cleaning Services', 'emoji' => '🧹'],
            ['id' => 'beauty-hair-cosmetics', 'name' => 'Beauty, Hair and Cosmetics', 'emoji' => '💇'],
            ['id' => 'food-production', 'name' => 'Food Production', 'emoji' => '🍞'],
            ['id' => 'butchery', 'name' => 'Butchery Equipment', 'emoji' => '🥩'],
            ['id' => 'events-management', 'name' => 'Events Management', 'emoji' => '🎉'],
            ['id' => 'snack-production', 'name' => 'Snack Production', 'emoji' => '🍿'],
            ['id' => 'food-processing', 'name' => 'Food Processing', 'emoji' => '🥜'],
            ['id' => 'entertainment', 'name' => 'Musical Instruments', 'emoji' => '🎮'],
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
                ['name' => 'Hatchery', 'businesses' => $this->getBusinessesForSubcategory('Hatchery')],
            ],
            // 2. Agriculture
            'agriculture' => [
                ['name' => 'Cash crop production', 'businesses' => $this->getBusinessesForSubcategory('Cash crop production')],
                ['name' => 'Supplementary inputs', 'businesses' => $this->getBusinessesForSubcategory('Supplementary inputs')],
                ['name' => 'Broiler production', 'businesses' => $this->getBusinessesForSubcategory('Broiler production')],
            ],
            // 3. Cleaning Services
            'cleaning-services' => [
                ['name' => 'Laundry', 'businesses' => $this->getBusinessesForSubcategory('Laundry')],
                ['name' => 'Car wash', 'businesses' => $this->getBusinessesForSubcategory('Car wash')],
                ['name' => 'Carpet and fabric', 'businesses' => $this->getBusinessesForSubcategory('Carpet and fabric')],
            ],
            // 4. Beauty, Hair and Cosmetics
            'beauty-hair-cosmetics' => [
                ['name' => 'Barber & Rasta', 'businesses' => $this->getBusinessesForSubcategory('Barber & Rasta')],
                ['name' => 'Braiding and weaving', 'businesses' => $this->getBusinessesForSubcategory('Braiding and weaving')],
                ['name' => 'Wig installation', 'businesses' => $this->getBusinessesForSubcategory('Wig installation')],
                ['name' => 'Nails and makeup', 'businesses' => $this->getBusinessesForSubcategory('Nails and makeup')],
                ['name' => 'Saloon equipment', 'businesses' => $this->getBusinessesForSubcategory('Saloon equipment')],
                ['name' => 'Hair Products and Cosmetics Sales', 'businesses' => $this->getBusinessesForSubcategory('Hair Products and Cosmetics Sales')],
            ],
            // 5. Food Production
            'food-production' => [
                ['name' => 'Baking', 'businesses' => $this->getBusinessesForSubcategory('Baking')],
                ['name' => 'Mobile food cart', 'businesses' => $this->getBusinessesForSubcategory('Mobile food cart')],
                ['name' => 'Takeaway Canteen', 'businesses' => $this->getBusinessesForSubcategory('Takeaway Canteen')],
                ['name' => 'Chip and burger fryer', 'businesses' => $this->getBusinessesForSubcategory('Chip and burger fryer')],
            ],
            // 6. Butchery Equipment
            'butchery' => [
                ['name' => 'Commercial Fridges', 'businesses' => $this->getBusinessesForSubcategory('Commercial Fridges')],
                ['name' => 'Bone cutter', 'businesses' => $this->getBusinessesForSubcategory('Bone cutter')],
                ['name' => 'Sausage maker', 'businesses' => $this->getBusinessesForSubcategory('Sausage maker')],
                ['name' => 'Mincemeat maker', 'businesses' => $this->getBusinessesForSubcategory('Mincemeat maker')],
            ],
            // 7. Events Management
            'events-management' => [
                ['name' => 'PA system', 'businesses' => $this->getBusinessesForSubcategory('PA system')],
                ['name' => 'Chairs and tables & décor', 'businesses' => $this->getBusinessesForSubcategory('Chairs and tables & décor')],
                ['name' => 'Tents', 'businesses' => $this->getBusinessesForSubcategory('Tents')],
                ['name' => 'Balloon décor/room décor', 'businesses' => $this->getBusinessesForSubcategory('Balloon décor/room décor')],
                ['name' => 'Portable Toilets', 'businesses' => $this->getBusinessesForSubcategory('Portable Toilets')],
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
            // 10. Musical Instruments
            'entertainment' => [
                ['name' => 'Snooker table', 'businesses' => $this->getBusinessesForSubcategory('Snooker table')],
                ['name' => 'Slug', 'businesses' => $this->getBusinessesForSubcategory('Slug')],
                ['name' => 'Gaming (ps4, monitors)', 'businesses' => $this->getBusinessesForSubcategory('Gaming (ps4, monitors)')],
                ['name' => 'DJ PA system', 'businesses' => $this->getBusinessesForSubcategory('DJ PA system')],
                ['name' => 'Internet Café', 'businesses' => $this->getBusinessesForSubcategory('Internet Café')],
                ['name' => 'Movie Projectors', 'businesses' => $this->getBusinessesForSubcategory('Movie Projectors')],
                ['name' => 'Instruments Hiring', 'businesses' => $this->getBusinessesForSubcategory('Instruments Hiring')],
            ],
            // 11. Material Printing
            'printing' => [
                ['name' => 'Tshirt & cap printing', 'businesses' => $this->getBusinessesForSubcategory('Tshirt & cap printing')],
                ['name' => 'Mug printing', 'businesses' => $this->getBusinessesForSubcategory('Mug printing')],
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
                ['name' => 'Jersey production', 'businesses' => $this->getBusinessesForSubcategory('Jersey making')],
                ['name' => 'Curtain production', 'businesses' => $this->getBusinessesForSubcategory('Curtain making')],
                ['name' => 'Uniform production', 'businesses' => $this->getBusinessesForSubcategory('Uniform making')],
                ['name' => 'Work suit & dust coat production', 'businesses' => $this->getBusinessesForSubcategory('Work suit & dust coat production')],
                ['name' => 'Sunhat production', 'businesses' => $this->getBusinessesForSubcategory('Sunhat production')],
                ['name' => 'Tshirt production', 'businesses' => $this->getBusinessesForSubcategory('Tshirt production')],
                ['name' => 'Bonnet, night ware & scrunchie production', 'businesses' => $this->getBusinessesForSubcategory('Bonnet, night ware & scrunchie production')],
            ],
            // 14. Building & Construction Equipment
            'building-construction' => [
                ['name' => 'Tiling', 'businesses' => $this->getBusinessesForSubcategory('Tiling')],
                ['name' => 'Carpentry', 'businesses' => $this->getBusinessesForSubcategory('Carpentry')],
                ['name' => 'Plumbing', 'businesses' => $this->getBusinessesForSubcategory('Plumbing')],
                ['name' => 'Electrical', 'businesses' => $this->getBusinessesForSubcategory('Electrical')],
                ['name' => 'Brick & pavers making', 'businesses' => $this->getBusinessesForSubcategory('Brick & pavers making')],
            ],
            // 15. Municipal Business License Financing
            'business-licensing' => [
                ['name' => 'Business License', 'businesses' => $this->getBusinessesForSubcategory('Business License')],
            ],
            // 16. Small scale mining
            'small-scale-mining' => [
                ['name' => 'Mining Equipment', 'businesses' => $this->getBusinessesForSubcategory('Mining Equipment')],
            ],
            // 17. Tuck shop
            'tuck-shop' => [
                ['name' => 'Groceries', 'businesses' => $this->getBusinessesForSubcategory('Groceries')],
                ['name' => 'Candy shop', 'businesses' => $this->getBusinessesForSubcategory('Candy shop')],
            ],
            // 18. Retailing
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
            // 21. Motor Vehicle Sundries
            'motor-vehicle' => [
                ['name' => 'Workshop', 'businesses' => $this->getBusinessesForSubcategory('Workshop')],
                ['name' => 'Diagnostic', 'businesses' => $this->getBusinessesForSubcategory('Diagnostic')],
                ['name' => 'Panel beating', 'businesses' => $this->getBusinessesForSubcategory('Panel beating')],
                ['name' => 'Tire repair services', 'businesses' => $this->getBusinessesForSubcategory('Tire repair services')],
                ['name' => 'Wheel alignment', 'businesses' => $this->getBusinessesForSubcategory('Wheel alignment')],
                ['name' => 'Battery services', 'businesses' => $this->getBusinessesForSubcategory('Battery services')],
                ['name' => 'Battery Charging', 'businesses' => $this->getBusinessesForSubcategory('Battery Charging')],
            ],
            // 22. Photocopying & Bulk Printing
            'photocopying-bulk-printing' => [
                ['name' => 'Laser printing', 'businesses' => $this->getBusinessesForSubcategory('Laser printing')],
                ['name' => 'Litho Printing', 'businesses' => $this->getBusinessesForSubcategory('Litho Printing')],
            ],
        ];

        return $allSubcategories[$categoryId] ?? [];
    }

    private function getBusinessesForSubcategory(string $subcategoryName): array
    {
        $allBusinesses = [
            'Cash crop production' => [
                ['name' => 'Cotton', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Maize', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Potato', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Soya Beans', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sugar Beans', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sunflower', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
                ['name' => 'Sweet Potato', 'basePrice' => 800, 'scales' => [['name' => '1 Ha', 'multiplier' => 1], ['name' => '2 Ha', 'multiplier' => 2], ['name' => '3 Ha', 'multiplier' => 3], ['name' => '5 Ha', 'multiplier' => 5]]],
            ],
            'Supplementary inputs' => [
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
            'Baking' => [
                ['name' => 'Baking – Bread', 'basePrice' => 1000, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
                ['name' => 'Baking - Cakes & confectionery', 'basePrice' => 1000, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
            ],
            'Chip and burger fryer' => [
                ['name' => 'Chip Fryer', 'basePrice' => 800, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
            ],
            'Takeaway Canteen' => [
                ['name' => 'Canteen', 'basePrice' => 1200, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
            ],
            'Mobile food cart' => [
                ['name' => 'Mobile food kiosk', 'basePrice' => 900, 'scales' => [['name' => 'Small', 'multiplier' => 1], ['name' => 'Medium', 'multiplier' => 2], ['name' => 'Large', 'multiplier' => 3]]],
            ],
            'Tiling' => [
                ['name' => 'Tiling Tools', 'basePrice' => 1000, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
            ],
            'Electrical' => [
                ['name' => 'Electrical Tools', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
            ],
            'Plumbing' => [
                ['name' => 'Plumbing Tools', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Tools', 'multiplier' => 1], ['name' => 'Standard Tools', 'multiplier' => 2], ['name' => 'Professional Tools', 'multiplier' => 3]]],
            ],
            'Instruments Hiring' => [
                ['name' => 'Musical Instruments Hire', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
            ],
            'PA system' => [
                ['name' => 'PA System', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Professional Setup', 'multiplier' => 3]]],
            ],
            'Snooker table' => [
                ['name' => 'Snooker Table', 'basePrice' => 3000, 'scales' => [['name' => 'Single Table', 'multiplier' => 1], ['name' => 'Multiple Tables', 'multiplier' => 2], ['name' => 'Full Hall', 'multiplier' => 3]]],
            ],
            'Slug' => [
                ['name' => 'Slug Table', 'basePrice' => 2500, 'scales' => [['name' => 'Single Table', 'multiplier' => 1], ['name' => 'Multiple Tables', 'multiplier' => 2], ['name' => 'Full Hall', 'multiplier' => 3]]],
            ],
            'Chairs and tables & décor' => [
                ['name' => 'Chairs & table', 'basePrice' => 1200, 'scales' => [['name' => 'Small Event', 'multiplier' => 1], ['name' => 'Medium Event', 'multiplier' => 2], ['name' => 'Large Event', 'multiplier' => 3]]],
            ],
            'Tents' => [
                ['name' => 'Tent', 'basePrice' => 2000, 'scales' => [['name' => 'Small Tent', 'multiplier' => 1], ['name' => 'Medium Tent', 'multiplier' => 2], ['name' => 'Large Tent', 'multiplier' => 3]]],
            ],
            'Balloon décor/room décor' => [
                ['name' => 'Decor', 'basePrice' => 1500, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
                ['name' => 'Red Carpet and accessories', 'basePrice' => 800, 'scales' => [['name' => 'Basic Package', 'multiplier' => 1], ['name' => 'Standard Package', 'multiplier' => 2], ['name' => 'Premium Package', 'multiplier' => 3]]],
            ],
            'Portable Toilets' => [
                ['name' => 'Portable Toilet Hire', 'basePrice' => 1000, 'scales' => [['name' => 'Basic Units', 'multiplier' => 1], ['name' => 'Standard Units', 'multiplier' => 2], ['name' => 'Premium Units', 'multiplier' => 3]]],
            ],
            'Barber & Rasta' => [
                ['name' => 'Barber', 'basePrice' => 400, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
            ],
            'Braiding and weaving' => [
                ['name' => 'Hair Salon', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
            ],
            'Nails and makeup' => [
                ['name' => 'Nail Installation', 'basePrice' => 600, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 2], ['name' => 'Premium Setup', 'multiplier' => 3]]],
            ],
            'Carpentry' => [
                ['name' => 'Furniture –Carpentry', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 2], ['name' => 'Full Workshop', 'multiplier' => 3]]],
            ],
            'Grinding mill' => [
                ['name' => 'Grinding Mill', 'basePrice' => 3000, 'scales' => [['name' => 'Small Mill', 'multiplier' => 1], ['name' => 'Medium Mill', 'multiplier' => 2], ['name' => 'Industrial Mill', 'multiplier' => 3]]],
            ],
            'Greenhouses' => [
                ['name' => 'Green House', 'basePrice' => 2500, 'scales' => [['name' => 'Small Greenhouse', 'multiplier' => 1], ['name' => 'Medium Greenhouse', 'multiplier' => 2], ['name' => 'Large Greenhouse', 'multiplier' => 3]]],
            ],
            'Land security' => [
                ['name' => 'Farm Security', 'basePrice' => 1800, 'scales' => [['name' => 'Basic Security', 'multiplier' => 1], ['name' => 'Standard Security', 'multiplier' => 2], ['name' => 'Advanced Security', 'multiplier' => 3]]],
            ],
            'Irrigation systems' => [
                ['name' => 'Micro Irrigation Systems', 'basePrice' => 2200, 'scales' => [['name' => 'Small System', 'multiplier' => 1], ['name' => 'Medium System', 'multiplier' => 2], ['name' => 'Large System', 'multiplier' => 3]]],
            ],
            'Tractors' => [
                ['name' => 'Tractors & accessories', 'basePrice' => 15000, 'scales' => [['name' => 'Basic Tractor', 'multiplier' => 1], ['name' => 'Standard Tractor', 'multiplier' => 1.5], ['name' => 'Full Package', 'multiplier' => 2]]],
            ],
            'Cooking oil' => [
                ['name' => 'Cooking Oil Production', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Freezit making' => [
                ['name' => 'Freezit making', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Maputi making' => [
                ['name' => 'Maputi production', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Peanut butter' => [
                ['name' => 'Peanut Butter Making', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Roasted corn' => [
                ['name' => 'Roasted Corn/Peanuts', 'basePrice' => 900, 'scales' => [['name' => 'Small Batch', 'multiplier' => 1], ['name' => 'Medium Batch', 'multiplier' => 2], ['name' => 'Large Batch', 'multiplier' => 3.5]]],
            ],
            'Commercial Fridges' => [
                ['name' => 'Butchery', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
            ],
            'Bone cutter' => [
                ['name' => 'Meat Cutter', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
            ],
            'Mincemeat maker' => [
                ['name' => 'Mince Making', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
            ],
            'Sausage maker' => [
                ['name' => 'Sausage Production', 'basePrice' => 1800, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 1.8], ['name' => 'Large Shop', 'multiplier' => 3]]],
            ],
            'Mining Equipment' => [
                ['name' => 'Water Extraction', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
                ['name' => 'Drilling', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
                ['name' => 'Industrial generators', 'basePrice' => 5000, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 2], ['name' => 'Heavy Duty', 'multiplier' => 4]]],
            ],
            'Mug printing' => [
                ['name' => 'Mugs-Cup', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Tshirt & cap printing' => [
                ['name' => 'T Shirt & Cap Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Embroidery printing' => [
                ['name' => 'D.T.F Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Screen Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Larger scale format printing' => [
                ['name' => 'Vehicle Branding', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
                ['name' => 'Plans printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Laser printing' => [
                ['name' => 'Photocopy', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Litho Printing' => [
                ['name' => 'Digital Printing', 'basePrice' => 1200, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.8], ['name' => 'Professional Setup', 'multiplier' => 3.2]]],
            ],
            'Car wash' => [
                ['name' => 'Car wash', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Laundry' => [
                ['name' => 'Laundry Service', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Motor cycle' => [
                ['name' => 'Motor cycle delivery', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Internet Café' => [
                ['name' => 'Internet', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Gaming (ps4, monitors)' => [
                ['name' => 'Gaming', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Photography' => [
                ['name' => 'Photography Studio', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Videography' => [
                ['name' => 'Videography', 'basePrice' => 1500, 'scales' => [['name' => 'Basic', 'multiplier' => 1], ['name' => 'Standard', 'multiplier' => 1.8], ['name' => 'Premium', 'multiplier' => 2.8]]],
            ],
            'Groceries' => [
                ['name' => 'Tuck shop -groceries', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
            ],
            'Candy shop' => [
                ['name' => 'Candy', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
            ],
            'Stationary shops' => [
                ['name' => 'Book', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
            ],
            'Cellphone accessories' => [
                ['name' => 'Cell phone Accessories', 'basePrice' => 1000, 'scales' => [['name' => 'Small Shop', 'multiplier' => 1], ['name' => 'Medium Shop', 'multiplier' => 2], ['name' => 'Large Shop', 'multiplier' => 3.5]]],
            ],
            'Jersey making' => [
                ['name' => 'Knitting – jersey manufacturing', 'basePrice' => 800, 'scales' => [['name' => 'Basic Setup', 'multiplier' => 1], ['name' => 'Standard Setup', 'multiplier' => 1.6], ['name' => 'Industrial Setup', 'multiplier' => 2.8]]],
            ],
            'Airtime vending' => [
                ['name' => 'Airtime scratch cards distribution', 'basePrice' => 600, 'scales' => [['name' => 'Small Scale', 'multiplier' => 1], ['name' => 'Medium Scale', 'multiplier' => 1.8], ['name' => 'Large Scale', 'multiplier' => 3]]],
            ],
            'Workshop' => [
                ['name' => 'Vehicle Repairs Workshop', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Diagnostic' => [
                ['name' => 'Vehicle diagnosis', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Panel beating' => [
                ['name' => 'Vehicle panel beating', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Tire repair services' => [
                ['name' => 'Tyre Fitting', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Wheel alignment' => [
                ['name' => 'Vehicle Alignment', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Battery services' => [
                ['name' => 'Air con re-gassing', 'basePrice' => 2000, 'scales' => [['name' => 'Basic Workshop', 'multiplier' => 1], ['name' => 'Standard Workshop', 'multiplier' => 1.8], ['name' => 'Full Service', 'multiplier' => 3.2]]],
            ],
            'Hatchery' => [
                ['name' => 'Egg Incubator', 'basePrice' => 1500, 'scales' => [['name' => 'Small Capacity', 'multiplier' => 1], ['name' => 'Medium Capacity', 'multiplier' => 2], ['name' => 'Large Capacity', 'multiplier' => 3]]],
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