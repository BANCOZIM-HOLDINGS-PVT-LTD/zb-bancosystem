<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizBusinessSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $this->seedBusinesses();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dump("MicrobizBusinessSeeder Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function seedBusinesses() {
        $categories = [
            ['name' => 'Agricultural Machinery', 'emoji' => '🚜', 'subcategories' => [
                'Maize sheller',
                'Water storage and pumping systems',
                'Tractors',
                'Irrigation systems',
                'Land security',
                'Incubaters',
                'Greenhouses',
                'Tobacco bailing machine',
                'Peanut butter machine',
                'Cooking oil press',
                'Grinding mill',
            ]],
            ['name' => 'Agricultural Inputs', 'emoji' => '🌾', 'subcategories' => [
                'Fertilizer',
                'Seed + Chemicals',
                'Combo (Fertilizer + Seed + Chemicals)',
            ]],
            ['name' => 'Chicken Projects', 'emoji' => '🐔', 'subcategories' => [
                'Broiler Production',
            ]],
            ['name' => 'Cleaning Services', 'emoji' => '🧹', 'subcategories' => [
                'Laundry',
                'Car wash',
                'Carpet and fabric',
            ]],
            ['name' => 'Beauty, Hair and Cosmetics', 'emoji' => '💇', 'subcategories' => [
                'Barber & Rasta',
                'Braiding and weaving',
                'Wig installation',
                'Nails and makeup',
                'Saloon equipment',
                'Hair Products and Cosmetics Sales',
            ]],
            ['name' => 'Meat Processing Equipment', 'emoji' => '🥩', 'subcategories' => [
                'Commercial Fridges',
                'Bone cutter',
                'Sausage maker',
                'Mincemeat maker',
                'Chicken plucker',
            ]],
            ['name' => 'Events Management', 'emoji' => '🎉', 'subcategories' => [
                'PA system',
                'Chairs and tables & décor',
                'Tents',
                'Portable Toilets',
                'Venue Set up',
                'Photography',
                'Videography',
                'Catering equipment',
            ]],
            ['name' => 'Snack Production', 'emoji' => '🍿', 'subcategories' => [
                'Freezit making',
                'Maputi making',
                'Popcorn making',
                'Ice making machine',
                'Ice cream making machine',
                'Roasted corn',
            ]],
            ['name' => 'Entertainment', 'emoji' => '🎮', 'subcategories' => [
                'Internet Café',
                'Movie Projectors',
                'Musical Instruments',
                'Quad bikes',
            ]],
            ['name' => 'Branding and Material Printing', 'emoji' => '🖨️', 'subcategories' => [
                'Tshirt & cap printing',
                'Mug printing',
                'Embroidery printing',
                'Larger scale format printing',
            ]],
            ['name' => 'Tailoring', 'emoji' => '✂️', 'subcategories' => [
                'Jersey production',
                'Curtain production',
                'Uniform production',
                'Work suit & dust coat production',
                'Sunhat production',
                'Tshirt production',
                'Bonnet, night ware & scrunchie production',
            ]],
            ['name' => 'Tailoring Machinery', 'emoji' => '🧵', 'subcategories' => [
                'Sewing machines',
                'Embroidery machines',
                'Overlock machines',
                'Cutting equipment',
            ]],
            ['name' => 'Building & Construction Equipment', 'emoji' => '🔨', 'subcategories' => [
                'Tiling',
                'Carpentry',
                'Plumbing',
                'Electrical',
                'Brick & pavers making',
            ]],
            ['name' => 'Business Licensing', 'emoji' => '📜', 'subcategories' => [
                'Liquor Store License',
                'Trading License',
                'Company Registration',
            ]],
            ['name' => 'Small Scale Mining', 'emoji' => '⛏️', 'subcategories' => [
                'Mining Equipment',
            ]],
            ['name' => 'Grocery and Tuckshop', 'emoji' => '🛍️', 'subcategories' => [
                'Groceries',
                'Candy and Confectionery Shop',
            ]],
            ['name' => 'Retail Shops', 'emoji' => '🏪', 'subcategories' => [
                'Cellphone Accessories',
                'Hardware',
                'Automotive Motor Spares',
                'General Dealer',
                'Stationery Shop',
            ]],
            ['name' => 'Financial Services Agency', 'emoji' => '🏦', 'subcategories' => [
                'Ecocash Agency',
                'ZB Bank Agency',
            ]],
            ['name' => 'Bike Delivery Service', 'emoji' => '🏍️', 'subcategories' => [
                'Motor cycle',
            ]],
            ['name' => 'Motor Vehicle Specialised Services', 'emoji' => '🚗', 'subcategories' => [
                'Workshop',
                'Diagnostic',
                'Panel beating',
                'Tire repair services',
                'Wheel alignment',
                'Battery services',
                'Battery Charging',
            ]],
            ['name' => 'Photocopying & Bulk Printing', 'emoji' => '📄', 'subcategories' => [
                'Laser printing',
                'Litho Printing',
            ]],
            ['name' => 'Water Bottling and Purification', 'emoji' => '💧', 'subcategories' => [
                'Water Bottling Station',
                'Purification Systems',
            ]],
        ];

        // Standard tier pricing for all businesses
        // Cost prices: 200, 350, 664, 1700 | 30% markup → Selling prices: 260, 455, 864, 2210
        $tiers = [
            ['tier' => 'lite',       'name' => 'Lite Package',       'price' => 260.00],
            ['tier' => 'standard',   'name' => 'Standard Package',   'price' => 455.00],
            ['tier' => 'full_house', 'name' => 'Full House Package', 'price' => 864.00],
            ['tier' => 'gold',       'name' => 'Gold Package',       'price' => 2210.00],
        ];

        // Special case: Company Registration only has one tier
        $companyRegTiers = [
            ['tier' => 'standard', 'name' => 'Standard Registration', 'price' => 195.00],
        ];

        foreach ($categories as $catData) {
            $category = MicrobizCategory::firstOrCreate(
                ['name' => $catData['name']],
                ['emoji' => $catData['emoji'], 'domain' => 'microbiz']
            );
            // Ensure domain is set on existing records too
            $category->domain = 'microbiz';
            $category->save();

            foreach ($catData['subcategories'] as $subName) {
                $subcategory = MicrobizSubcategory::firstOrCreate(
                    ['microbiz_category_id' => $category->id, 'name' => $subName]
                );

                // Determine which tiers to seed
                $tiersToSeed = ($subName === 'Company Registration') ? $companyRegTiers : $tiers;

                foreach ($tiersToSeed as $tierData) {
                    MicrobizPackage::updateOrCreate(
                        [
                            'microbiz_subcategory_id' => $subcategory->id,
                            'tier' => $tierData['tier'],
                        ],
                        [
                            'name' => $tierData['name'],
                            'price' => $tierData['price'],
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Seeded ' . MicrobizCategory::count() . ' categories, '
            . MicrobizSubcategory::count() . ' businesses, '
            . MicrobizPackage::count() . ' tier packages.');
    }
}
