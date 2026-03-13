<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizIncubatorsSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // 1. Category & Subcategory
            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Agricultural Machinery'],
                ['emoji' => '🚜', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Incubaters'],
                ['image_url' => 'microbiz/incubators.jpg']
            );

            // 2. Items (from Excel: Agric Machines - INCUBATORS)
            $itemsData = [
                ['code' => 'MAMI043', 'name' => '216 Egg Capacity', 'unit_cost' => 150.00],
                ['code' => 'MAMI044', 'name' => '576 Egg Capacity', 'unit_cost' => 650.00],
                ['code' => 'MAMI045', 'name' => '1700 Egg Capacity', 'unit_cost' => 1200.00],
                // Transport
                ['code' => 'TSIM046', 'name' => 'Cabin from source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TSIM047', 'name' => 'Courier charge 1', 'unit_cost' => 15.00],
                ['code' => 'TCIM048', 'name' => 'Courier charge 2', 'unit_cost' => 30.00],
                ['code' => 'TCIM049', 'name' => 'Courier charge 3', 'unit_cost' => 65.00],
                ['code' => 'TCIM050', 'name' => 'Courier charge 4', 'unit_cost' => 120.00],
            ];

            $items = [];
            foreach ($itemsData as $data) {
                $items[$data['code']] = MicrobizItem::updateOrCreate(
                    ['item_code' => $data['code']],
                    [
                        'microbiz_subcategory_id' => $subcategory->id,
                        'name' => $data['name'],
                        'unit_cost' => $data['unit_cost'],
                        'markup_percentage' => 40.00,
                        'unit' => 'unit',
                    ]
                );
            }
            $this->command->info('Incubator items created/updated.');

            // 3. Packages — standardised selling prices across all categories
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MAMI043' => 1,
                        'TSIM046' => 1,
                        'TSIM047' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MAMI043' => 2,
                        'TSIM046' => 1,
                        'TCIM048' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MAMI044' => 1,
                        'TSIM046' => 1,
                        'TCIM049' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MAMI045' => 1,
                        'TSIM046' => 1,
                        'TCIM050' => 1,
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
            $this->command->info('✅ Seeded Incubators successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
