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
        $categories = [
            ['id' => 'agriculture', 'name' => 'Agriculture', 'emoji' => '🌾'],
            ['id' => 'animal-husbandry', 'name' => 'Animal Husbandry', 'emoji' => '🐄'],
            ['id' => 'catering', 'name' => 'Catering', 'emoji' => '🍽️'],
            ['id' => 'construction', 'name' => 'Construction', 'emoji' => '🔨'],
            ['id' => 'entertainment', 'name' => 'Entertainment', 'emoji' => '🎵'],
            ['id' => 'events-hire', 'name' => 'Events Hire', 'emoji' => '🎉'],
            ['id' => 'hair-grooming', 'name' => 'Hair & Grooming', 'emoji' => '💇'],
            ['id' => 'home-industry-manufacturing', 'name' => 'Home Industry Manufacturing', 'emoji' => '🏭'],
            ['id' => 'farming-machinery', 'name' => 'Farming Machinery', 'emoji' => '🚜'],
            ['id' => 'food-processing', 'name' => 'Food Processing', 'emoji' => '🥜'],
            ['id' => 'meat-processing', 'name' => 'Meat Processing', 'emoji' => '🥩'],
            ['id' => 'mining', 'name' => 'Mining', 'emoji' => '⛏️'],
            ['id' => 'printing', 'name' => 'Printing', 'emoji' => '🖨️'],
            ['id' => 'professional-services-equipment', 'name' => 'Professional Services Equipment', 'emoji' => '💼'],
            ['id' => 'retail-shop', 'name' => 'Retail Shop', 'emoji' => '🛍️'],
            ['id' => 'tailoring', 'name' => 'Tailoring', 'emoji' => '✂️'],
            ['id' => 'trade-services', 'name' => 'Trade Services', 'emoji' => '📱'],
            ['id' => 'vehicle', 'name' => 'Vehicle', 'emoji' => '🚗'],
            ['id' => 'vocation', 'name' => 'Vocation', 'emoji' => '🎓'],
            ['id' => 'wedding-attire-hire', 'name' => 'Wedding Attire Hire', 'emoji' => '💒'],
        ];

        foreach ($categories as $categoryData) {
            $categoryId = DB::table('product_categories')->insertGetId([
                'name' => $categoryData['name'],
                'emoji' => $categoryData['emoji'],
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
            'agriculture' => [
                ['name' => 'Cash Crops', 'businesses' => $this->getBusinessesForSubcategory('Cash Crops')],
            ],
            'animal-husbandry' => [
                ['name' => 'Livestock & Poultry', 'businesses' => $this->getBusinessesForSubcategory('Livestock & Poultry')],
            ],
            'catering' => [
                ['name' => 'Food Services', 'businesses' => $this->getBusinessesForSubcategory('Food Services')],
            ],
            'construction' => [
                ['name' => 'Trade Tools', 'businesses' => $this->getBusinessesForSubcategory('Trade Tools')],
            ],
            'entertainment' => [
                ['name' => 'Musical Instruments Hire', 'businesses' => $this->getBusinessesForSubcategory('Musical Instruments Hire')],
                ['name' => 'PA System', 'businesses' => $this->getBusinessesForSubcategory('PA System')],
                ['name' => 'Snooker Table', 'businesses' => $this->getBusinessesForSubcategory('Snooker Table')],
                ['name' => 'Slug Table', 'businesses' => $this->getBusinessesForSubcategory('Slug Table')],
            ],
            'events-hire' => [
                ['name' => 'Chairs & table', 'businesses' => $this->getBusinessesForSubcategory('Chairs & table')],
                ['name' => 'Tent', 'businesses' => $this->getBusinessesForSubcategory('Tent')],
                ['name' => 'Decor', 'businesses' => $this->getBusinessesForSubcategory('Decor')],
                ['name' => 'Red Carpet and accessories', 'businesses' => $this->getBusinessesForSubcategory('Red Carpet and accessories')],
                ['name' => 'Portable Toilet Hire', 'businesses' => $this->getBusinessesForSubcategory('Portable Toilet Hire')],
                ['name' => 'Interactive Big Screen Monitors', 'businesses' => $this->getBusinessesForSubcategory('Interactive Big Screen Monitors')],
            ],
            'hair-grooming' => [
                ['name' => 'Barber', 'businesses' => $this->getBusinessesForSubcategory('Barber')],
                ['name' => 'Hair Salon', 'businesses' => $this->getBusinessesForSubcategory('Hair Salon')],
                ['name' => 'Nail Installation', 'businesses' => $this->getBusinessesForSubcategory('Nail Installation')],
            ],
            'home-industry-manufacturing' => [
                ['name' => 'Detergent Chemicals', 'businesses' => $this->getBusinessesForSubcategory('Detergent Chemicals')],
                ['name' => 'Fence Making', 'businesses' => $this->getBusinessesForSubcategory('Fence Making')],
                ['name' => 'Furniture –Carpentry', 'businesses' => $this->getBusinessesForSubcategory('Furniture –Carpentry')],
                ['name' => 'Soap production', 'businesses' => $this->getBusinessesForSubcategory('Soap production')],
                ['name' => 'Ice Making', 'businesses' => $this->getBusinessesForSubcategory('Ice Making')],
                ['name' => 'Welding', 'businesses' => $this->getBusinessesForSubcategory('Welding')],
            ],
            'farming-machinery' => [
                ['name' => 'Egg Incubator', 'businesses' => $this->getBusinessesForSubcategory('Egg Incubator')],
                ['name' => 'Indigenous Agric Pack', 'businesses' => $this->getBusinessesForSubcategory('Indigenous Agric Pack')],
                ['name' => 'Grinding Mill', 'businesses' => $this->getBusinessesForSubcategory('Grinding Mill')],
                ['name' => 'Green House', 'businesses' => $this->getBusinessesForSubcategory('Green House')],
                ['name' => 'Farm Security', 'businesses' => $this->getBusinessesForSubcategory('Farm Security')],
                ['name' => 'Micro Irrigation Systems', 'businesses' => $this->getBusinessesForSubcategory('Micro Irrigation Systems')],
                ['name' => 'Tractors & accessories', 'businesses' => $this->getBusinessesForSubcategory('Tractors & accessories')],
            ],
            'food-processing' => [
                ['name' => 'Cooking Oil Production', 'businesses' => $this->getBusinessesForSubcategory('Cooking Oil Production')],
                ['name' => 'Dry Food Repackaging', 'businesses' => $this->getBusinessesForSubcategory('Dry Food Repackaging')],
                ['name' => 'Freezit making', 'businesses' => $this->getBusinessesForSubcategory('Freezit making')],
                ['name' => 'Maputi production', 'businesses' => $this->getBusinessesForSubcategory('Maputi production')],
                ['name' => 'Peanut Butter Making', 'businesses' => $this->getBusinessesForSubcategory('Peanut Butter Making')],
                ['name' => 'Roasted Corn/Peanuts', 'businesses' => $this->getBusinessesForSubcategory('Roasted Corn/Peanuts')],
            ],
            'meat-processing' => [
                ['name' => 'Butchery', 'businesses' => $this->getBusinessesForSubcategory('Butchery')],
                ['name' => 'Meat Cutter', 'businesses' => $this->getBusinessesForSubcategory('Meat Cutter')],
                ['name' => 'Mince Making', 'businesses' => $this->getBusinessesForSubcategory('Mince Making')],
                ['name' => 'Sausage Production', 'businesses' => $this->getBusinessesForSubcategory('Sausage Production')],
            ],
            'mining' => [
                ['name' => 'Water Extraction', 'businesses' => $this->getBusinessesForSubcategory('Water Extraction')],
                ['name' => 'Drilling', 'businesses' => $this->getBusinessesForSubcategory('Drilling')],
                ['name' => 'Industrial generators', 'businesses' => $this->getBusinessesForSubcategory('Industrial generators')],
            ],
            'printing' => [
                ['name' => 'Mugs-Cup', 'businesses' => $this->getBusinessesForSubcategory('Mugs-Cup')],
                ['name' => 'Vehicle Branding', 'businesses' => $this->getBusinessesForSubcategory('Vehicle Branding')],
                ['name' => 'Digital Printing', 'businesses' => $this->getBusinessesForSubcategory('Digital Printing')],
                ['name' => 'D.T.F Printing', 'businesses' => $this->getBusinessesForSubcategory('D.T.F Printing')],
                ['name' => 'Screen Printing', 'businesses' => $this->getBusinessesForSubcategory('Screen Printing')],
                ['name' => 'Plans printing', 'businesses' => $this->getBusinessesForSubcategory('Plans printing')],
                ['name' => 'T Shirt & Cap Printing', 'businesses' => $this->getBusinessesForSubcategory('T Shirt & Cap Printing')],
                ['name' => 'Photocopy', 'businesses' => $this->getBusinessesForSubcategory('Photocopy')],
                ['name' => 'Photo printing instant', 'businesses' => $this->getBusinessesForSubcategory('Photo printing instant')],
            ],
            'professional-services-equipment' => [
                ['name' => 'Bar', 'businesses' => $this->getBusinessesForSubcategory('Bar')],
                ['name' => 'Butchery', 'businesses' => $this->getBusinessesForSubcategory('Butchery')],
                ['name' => 'Car key programming', 'businesses' => $this->getBusinessesForSubcategory('Car key programming')],
                ['name' => 'Cell repair', 'businesses' => $this->getBusinessesForSubcategory('Cell repair')],
                ['name' => 'Car wash', 'businesses' => $this->getBusinessesForSubcategory('Car wash')],
                ['name' => 'Cleaning commercial service', 'businesses' => $this->getBusinessesForSubcategory('Cleaning commercial service')],
                ['name' => 'Internet', 'businesses' => $this->getBusinessesForSubcategory('Internet')],
                ['name' => 'Gaming', 'businesses' => $this->getBusinessesForSubcategory('Gaming')],
                ['name' => 'Gas Station', 'businesses' => $this->getBusinessesForSubcategory('Gas Station')],
                ['name' => 'Grass Cutting', 'businesses' => $this->getBusinessesForSubcategory('Grass Cutting')],
                ['name' => 'Gymnasium', 'businesses' => $this->getBusinessesForSubcategory('Gymnasium')],
                ['name' => 'Motor cycle delivery', 'businesses' => $this->getBusinessesForSubcategory('Motor cycle delivery')],
                ['name' => 'Laptop repairs', 'businesses' => $this->getBusinessesForSubcategory('Laptop repairs')],
                ['name' => 'Laundry Service', 'businesses' => $this->getBusinessesForSubcategory('Laundry Service')],
                ['name' => 'Lock Smith Service', 'businesses' => $this->getBusinessesForSubcategory('Lock Smith Service')],
                ['name' => 'Photography Studio', 'businesses' => $this->getBusinessesForSubcategory('Photography Studio')],
                ['name' => 'Photo printing', 'businesses' => $this->getBusinessesForSubcategory('Photo printing')],
                ['name' => 'Pre School', 'businesses' => $this->getBusinessesForSubcategory('Pre School')],
                ['name' => 'Satellite Dish Installation', 'businesses' => $this->getBusinessesForSubcategory('Satellite Dish Installation')],
                ['name' => 'Saw Mill', 'businesses' => $this->getBusinessesForSubcategory('Saw Mill')],
                ['name' => 'Shop Accessories', 'businesses' => $this->getBusinessesForSubcategory('Shop Accessories')],
                ['name' => 'Shop Fitting', 'businesses' => $this->getBusinessesForSubcategory('Shop Fitting')],
                ['name' => 'Videography', 'businesses' => $this->getBusinessesForSubcategory('Videography')],
            ],
            'retail-shop' => [
                ['name' => 'Agro', 'businesses' => $this->getBusinessesForSubcategory('Agro')],
                ['name' => 'Book', 'businesses' => $this->getBusinessesForSubcategory('Book')],
                ['name' => 'Candy', 'businesses' => $this->getBusinessesForSubcategory('Candy')],
                ['name' => 'Cell phone', 'businesses' => $this->getBusinessesForSubcategory('Cell phone')],
                ['name' => 'Cell phone Accessories', 'businesses' => $this->getBusinessesForSubcategory('Cell phone Accessories')],
                ['name' => 'Ceramics Tiles', 'businesses' => $this->getBusinessesForSubcategory('Ceramics Tiles')],
                ['name' => 'Clothing (Men/Women: Formal/Informal)', 'businesses' => $this->getBusinessesForSubcategory('Clothing (Men/Women: Formal/Informal)')],
                ['name' => 'Cosmetics', 'businesses' => $this->getBusinessesForSubcategory('Cosmetics')],
                ['name' => 'Hair (braids, wigs, weaves)', 'businesses' => $this->getBusinessesForSubcategory('Hair (braids, wigs, weaves)')],
                ['name' => 'Hats & Caps', 'businesses' => $this->getBusinessesForSubcategory('Hats & Caps')],
                ['name' => 'Herbal', 'businesses' => $this->getBusinessesForSubcategory('Herbal')],
                ['name' => 'Fabric & textile', 'businesses' => $this->getBusinessesForSubcategory('Fabric & textile')],
                ['name' => 'Jewellery', 'businesses' => $this->getBusinessesForSubcategory('Jewellery')],
                ['name' => 'Shoes - Men\'s Sports', 'businesses' => $this->getBusinessesForSubcategory('Shoes - Men\'s Sports')],
                ['name' => 'Solar & Accessories', 'businesses' => $this->getBusinessesForSubcategory('Solar & Accessories')],
            ],
            'tailoring' => [
                ['name' => 'Embroidery', 'businesses' => $this->getBusinessesForSubcategory('Embroidery')],
                ['name' => 'Knitting – jersey manufacturing', 'businesses' => $this->getBusinessesForSubcategory('Knitting – jersey manufacturing')],
                ['name' => 'Industrial overlocking', 'businesses' => $this->getBusinessesForSubcategory('Industrial overlocking')],
                ['name' => 'Domestic sewing machine', 'businesses' => $this->getBusinessesForSubcategory('Domestic sewing machine')],
            ],
            'trade-services' => [
                ['name' => 'Airtime scratch cards distribution', 'businesses' => $this->getBusinessesForSubcategory('Airtime scratch cards distribution')],
                ['name' => 'Tuck shop -groceries', 'businesses' => $this->getBusinessesForSubcategory('Tuck shop -groceries')],
                ['name' => 'Tyres', 'businesses' => $this->getBusinessesForSubcategory('Tyres')],
                ['name' => 'Windscreen Replacement', 'businesses' => $this->getBusinessesForSubcategory('Windscreen Replacement')],
            ],
            'vehicle' => [
                ['name' => 'Air con re-gassing', 'businesses' => $this->getBusinessesForSubcategory('Air con re-gassing')],
                ['name' => 'Tyre Fitting', 'businesses' => $this->getBusinessesForSubcategory('Tyre Fitting')],
                ['name' => 'Vehicle Alignment', 'businesses' => $this->getBusinessesForSubcategory('Vehicle Alignment')],
                ['name' => 'Vehicle diagnosis', 'businesses' => $this->getBusinessesForSubcategory('Vehicle diagnosis')],
                ['name' => 'Vehicle Repairs Workshop', 'businesses' => $this->getBusinessesForSubcategory('Vehicle Repairs Workshop')],
                ['name' => 'Vehicle panel beating', 'businesses' => $this->getBusinessesForSubcategory('Vehicle panel beating')],
            ],
            'vocation' => [
                ['name' => 'Network marketing admission Fees (Avon, B.B.B, Forever Living, Honey)', 'businesses' => $this->getBusinessesForSubcategory('Network marketing admission Fees (Avon, B.B.B, Forever Living, Honey)')],
                ['name' => 'Nurse Aid', 'businesses' => $this->getBusinessesForSubcategory('Nurse Aid')],
            ],
            'wedding-attire-hire' => [
                ['name' => 'Accessories ( Artificial Flowers, Crown, Ring Basset)', 'businesses' => $this->getBusinessesForSubcategory('Accessories ( Artificial Flowers, Crown, Ring Basset)')],
                ['name' => 'Bridal gown', 'businesses' => $this->getBusinessesForSubcategory('Bridal gown')],
                ['name' => 'Bridal team', 'businesses' => $this->getBusinessesForSubcategory('Bridal team')],
                ['name' => 'Groom suit', 'businesses' => $this->getBusinessesForSubcategory('Groom suit')],
                ['name' => 'High Back Chair', 'businesses' => $this->getBusinessesForSubcategory('High Back Chair')],
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

        return $allBusinesses[$subcategoryName] ?? [];
    }
}
