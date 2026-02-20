<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use App\Models\MicrobizTierItem;

class MicrobizChickenProjectSeeder extends Seeder
{
    public function run(): void
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Ensure Category and Subcategory exist
            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Chicken Projects'],
                ['emoji' => '🐔', 'domain' => 'microbiz']
            );
            $this->command->info("Category ID: {$category->id}");

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Broiler Production'],
                ['image_url' => 'microbiz/broiler_production.jpg'] // Placeholder
            );
            $this->command->info("Subcategory ID: {$subcategory->id}");

            // 2. Define All Items (Costs from Excel)
            $itemsData = [
                // Product | Description | Unit Cost
                ['code' => 'MCPB001', 'name' => 'Birds (Day Old Chicks)', 'unit_cost' => 1.00],
                // Feed
                ['code' => 'MCPB002', 'name' => 'Starter crumbs (25kg)', 'unit_cost' => 18.00],
                ['code' => 'MCPB003', 'name' => 'Starter crumbs (50kg)', 'unit_cost' => 36.00],
                ['code' => 'MCPB004', 'name' => 'Grower pellets (50kg)', 'unit_cost' => 35.00],
                ['code' => 'MCPB005', 'name' => 'Grower pellets (25kg)', 'unit_cost' => 18.00],
                ['code' => 'MCPB006', 'name' => 'Finisher Pellets (50kg)', 'unit_cost' => 34.00],
                ['code' => 'MCPB007', 'name' => 'Finisher Pellets (25kg)', 'unit_cost' => 17.50],
                // Consumables
                ['code' => 'MCPB008', 'name' => 'Stress pack (100g)', 'unit_cost' => 2.00],
                ['code' => 'MCPB009', 'name' => 'Virukill (disinfectant) 250g', 'unit_cost' => 4.20],
                ['code' => 'MCPB010', 'name' => 'Aliseryl (antibiotic)', 'unit_cost' => 6.34],
                // Plastic Drinkers
                ['code' => 'MCPB011', 'name' => '3 litres antidrown', 'unit_cost' => 2.00],
                ['code' => 'MCPB012', 'name' => '6-8 litres drinker', 'unit_cost' => 3.00],
                ['code' => 'MCPB013', 'name' => '10-12 litres drinker', 'unit_cost' => 7.00],
                // Plastic Feeders
                ['code' => 'MCPB014', 'name' => '3kg feeder', 'unit_cost' => 2.00],
                ['code' => 'MCPB015', 'name' => '6kg feeder', 'unit_cost' => 3.00],
                ['code' => 'MCPB016', 'name' => '10kg feeder', 'unit_cost' => 7.00],
                // Housing and Accessories
                ['code' => 'MCPB017', 'name' => 'Chicken coup complete', 'unit_cost' => 140.00],
                ['code' => 'MCPB018', 'name' => 'Wooden Cabin', 'unit_cost' => 120.00],
                ['code' => 'MCPB019', 'name' => 'Mesh wire', 'unit_cost' => 5.00],
                ['code' => 'MCPB020', 'name' => 'Roofing', 'unit_cost' => 12.00],
                ['code' => 'MCPB021', 'name' => 'Brooder', 'unit_cost' => 15.00],
                ['code' => 'MCPB022', 'name' => 'Infra red lamps', 'unit_cost' => 4.00],
            ];

            $items = [];
            foreach ($itemsData as $data) {
                $items[$data['code']] = MicrobizItem::updateOrCreate(
                    ['item_code' => $data['code']],
                    [
                        'microbiz_subcategory_id' => $subcategory->id,
                        'name' => $data['name'],
                        'unit_cost' => $data['unit_cost'],
                        'markup_percentage' => 30.00,
                        'unit' => 'unit',
                    ]
                );
            }
            $this->command->info('Items created/updated.');

            // 3. Define Packages
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 280.00,
                    'items' => [
                        'MCPB001' => 50,
                        'MCPB002' => 1,
                        'MCPB004' => 1,
                        'MCPB005' => 1,
                        'MCPB006' => 1,
                        'MCPB007' => 1,
                        'MCPB008' => 1,
                        'MCPB009' => 1,
                        'MCPB010' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 490.00,
                    'items' => [
                        'MCPB001' => 100,
                        'MCPB003' => 1,
                        'MCPB004' => 3,
                        'MCPB006' => 3,
                        'MCPB008' => 1,
                        'MCPB009' => 1,
                        'MCPB010' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 930.00,
                    'items' => [
                        'MCPB001' => 100,
                        'MCPB003' => 1,
                        'MCPB004' => 3,
                        'MCPB006' => 3,
                        'MCPB008' => 1,
                        'MCPB009' => 1,
                        'MCPB010' => 1,
                        'MCPB011' => 4,
                        'MCPB012' => 4,
                        'MCPB013' => 4,
                        'MCPB014' => 4,
                        'MCPB015' => 4,
                        'MCPB016' => 4,
                        'MCPB018' => 1,
                        'MCPB019' => 1,
                        'MCPB020' => 1,
                        'MCPB021' => 1,
                        'MCPB022' => 4,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 1700.00,
                    'items' => [
                        'MCPB001' => 200,
                        'MCPB003' => 2,
                        'MCPB004' => 6,
                        'MCPB006' => 6,
                        'MCPB008' => 2,
                        'MCPB009' => 2,
                        'MCPB010' => 2,
                        'MCPB011' => 4,
                        'MCPB012' => 4,
                        'MCPB013' => 4,
                        'MCPB014' => 8,
                        'MCPB015' => 8,
                        'MCPB016' => 8,
                        'MCPB018' => 2,
                        'MCPB019' => 2,
                        'MCPB020' => 2,
                        'MCPB021' => 2,
                        'MCPB022' => 8,
                    ],
                ],
            ];

            foreach ($packages as $tier => $pkgData) {
                $package = MicrobizPackage::updateOrCreate(
                    [
                        'microbiz_subcategory_id' => $subcategory->id,
                        'tier' => $tier,
                    ],
                    [
                        'name' => $pkgData['name'],
                        'price' => $pkgData['price'],
                        'transport_method' => null,
                        'courier' => 'None',
                    ]
                );

                $package->items()->detach();

                foreach ($pkgData['items'] as $code => $qty) {
                    if (isset($items[$code])) {
                        $package->items()->attach($items[$code]->id, ['quantity' => $qty, 'is_delivered' => true]);
                    } else {
                        $this->command->error("Item code not found: $code");
                    }
                }
            }
            
            \Illuminate\Support\Facades\DB::commit();
            $this->command->info('✅ Seeded Broiler Production successfully.');
        } catch (\Exception $e) {
             \Illuminate\Support\Facades\DB::rollBack();
             $this->command->error($e->getMessage());
             $this->command->error($e->getTraceAsString());
        }
    }
}
