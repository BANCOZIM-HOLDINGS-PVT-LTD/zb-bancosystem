<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizLayersProjectSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // 1. Ensure Category exists
            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Chicken Projects'],
                ['emoji' => '🐔', 'domain' => 'microbiz']
            );
            $this->command->info("Category ID: {$category->id}");

            // 2. Create Layers Subcategory
            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Layers'],
                ['image_url' => 'microbiz/layers_production.jpg'] // Placeholder
            );
            $this->command->info("Subcategory ID: {$subcategory->id}");

            // 3. Define Items
            $itemsData = [
                ['code' => 'MCPL025', 'name' => 'Birds (Day Old Chicks)', 'unit_cost' => 1.00],
                ['code' => 'MCPL026', 'name' => 'Starter Crumbs (50kg)', 'unit_cost' => 32.00],
                ['code' => 'MCPL027', 'name' => 'Grower Crumbs (50kg)', 'unit_cost' => 26.00],
                ['code' => 'MCPL028', 'name' => 'Layer Mash Concetrate (50kg)', 'unit_cost' => 28.00],
                // Drinkers
                ['code' => 'MCPL029', 'name' => '3 litres antidrown', 'unit_cost' => 2.00],
                ['code' => 'MCPL030', 'name' => '6-8 litres', 'unit_cost' => 3.00],
                ['code' => 'MCPL031', 'name' => '10-12 litres', 'unit_cost' => 7.00],
                // Feeders
                ['code' => 'MCPL032', 'name' => '3kg feeder', 'unit_cost' => 2.00],
                ['code' => 'MCPL033', 'name' => '6kgs feeder', 'unit_cost' => 3.00],
                ['code' => 'MCPL034', 'name' => '10kgs feeder', 'unit_cost' => 7.00],
                // Housing
                ['code' => 'MCPL035', 'name' => 'Wooden Cabin', 'unit_cost' => 120.00],
                ['code' => 'MCPL036', 'name' => 'Mesh wire', 'unit_cost' => 5.00],
                ['code' => 'MCPL037', 'name' => 'Roofing', 'unit_cost' => 12.00],
                ['code' => 'MCPL038', 'name' => 'Brooder', 'unit_cost' => 15.00],
                ['code' => 'MCPL039', 'name' => 'Infra red lamps', 'unit_cost' => 4.00],
                ['code' => 'MCPL040', 'name' => 'Chicken coup complete', 'unit_cost' => 140.00],
                // Transport Items
                ['code' => 'TSLP041', 'name' => 'Cabin from source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TCLP042', 'name' => 'Courier charge', 'unit_cost' => 14.00],
            ];

            $items = [];
            foreach ($itemsData as $data) {
                $items[$data['code']] = MicrobizItem::updateOrCreate(
                    ['item_code' => $data['code']],
                    [
                        'microbiz_subcategory_id' => $subcategory->id,
                        'name' => $data['name'],
                        'unit_cost' => $data['unit_cost'],
                        'markup_percentage' => 40.00, // 40% Margin as per Image
                        'unit' => 'unit',
                    ]
                );
            }
            $this->command->info('Items created/updated.');

            // 4. Define Packages
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 263.20, // (50*1 + 32 + 3*26 + 28) * 1.4 = 188 * 1.4 = 263.2
                    'items' => [
                        'MCPL025' => 50,
                        'MCPL026' => 1,
                        'MCPL027' => 3,
                        'MCPL028' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 487.20, // (100*1 + 2*32 + 6*26 + 28) * 1.4 = 348 * 1.4 = 487.2
                    'items' => [
                        'MCPL025' => 100,
                        'MCPL026' => 2,
                        'MCPL027' => 6,
                        'MCPL028' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 926.80, // 662 * 1.4 = 926.8
                    'items' => [
                        'MCPL025' => 100,
                        'MCPL026' => 2,
                        'MCPL027' => 6,
                        'MCPL028' => 2,
                        'MCPL029' => 4,
                        'MCPL030' => 4,
                        'MCPL031' => 4,
                        'MCPL032' => 4,
                        'MCPL033' => 4,
                        'MCPL034' => 4,
                        'MCPL035' => 1,
                        'MCPL036' => 1,
                        'MCPL037' => 1,
                        'MCPL038' => 1,
                        'MCPL039' => 1,
                        'TSLP041' => 1,
                        'TCLP042' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 1700.00, // User requested override to 1700 regardless of category
                    'items' => [
                        'MCPL025' => 200,
                        'MCPL026' => 4,
                        'MCPL027' => 12,
                        'MCPL028' => 4,
                        'MCPL029' => 4,
                        'MCPL030' => 4,
                        'MCPL031' => 4,
                        'MCPL032' => 4,
                        'MCPL033' => 4,
                        'MCPL034' => 4,
                        'MCPL035' => 2,
                        'MCPL036' => 2,
                        'MCPL037' => 2,
                        'MCPL038' => 2,
                        'MCPL039' => 2,
                        // Note: Gold has 0 transport in image, but maintaining pattern
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
            
            DB::commit();
            $this->command->info('✅ Seeded Layers Production successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
