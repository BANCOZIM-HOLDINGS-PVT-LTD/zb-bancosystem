<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizCarWashSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Cleaning Services'],
                ['emoji' => '🧹', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Car wash'],
                ['image_url' => 'microbiz/car_wash.jpg']
            );

            // Items from Excel: Cleaning Service - Car wash
            $itemsData = [
                ['code' => 'MCSC118', 'name' => 'Electric High Pressure cleaner 1600W Nexus', 'unit_cost' => 75.00],
                ['code' => 'MCSC119', 'name' => 'Electric High Pressure cleaner 1800W Total', 'unit_cost' => 100.00],
                ['code' => 'MCSC120', 'name' => 'Electric High Pressure cleaner Total', 'unit_cost' => 150.00],
                ['code' => 'MCSC121', 'name' => 'Petrol Pressure Cleaner 6HP Total', 'unit_cost' => 268.00],
                ['code' => 'MCSC122', 'name' => 'Vacuum cleaner', 'unit_cost' => 110.00],
                ['code' => 'MCSC123', 'name' => 'Vacuum cleaner 1400W', 'unit_cost' => 130.00],
                ['code' => 'MCSC124', 'name' => 'Buffing Machine Electric Total', 'unit_cost' => 85.00],
                ['code' => 'MCSC125', 'name' => 'Buffing Machine Battery powered Total', 'unit_cost' => 110.00],
                ['code' => 'MCSC126', 'name' => 'Car port 1 car', 'unit_cost' => 530.00],
                ['code' => 'MCSC127', 'name' => 'IBC Tank 1000L', 'unit_cost' => 160.00],
                // Transport
                ['code' => 'TSCW128', 'name' => 'From source to Courier', 'unit_cost' => 5.00],
                ['code' => 'TCCW129', 'name' => 'Courier charge', 'unit_cost' => 20.50],
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
            $this->command->info('Car Wash items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MCSC118' => 1, // 1600W Nexus
                        'MCSC122' => 1, // Vacuum cleaner
                        'TSCW128' => 1,
                        'TCCW129' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MCSC119' => 1, // 1800W Total
                        'MCSC123' => 1, // Vacuum 1400W
                        'MCSC124' => 1, // Buffing Electric
                        'TSCW128' => 1,
                        'TCCW129' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MCSC119' => 1, // 1800W Total
                        'MCSC121' => 1, // Petrol 6HP
                        'MCSC123' => 1, // Vacuum 1400W
                        'MCSC125' => 1, // Buffing Battery
                        'TSCW128' => 1,
                        'TCCW129' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MCSC120' => 2, // High Pressure x2
                        'MCSC121' => 1, // Petrol 6HP
                        'MCSC123' => 2, // Vacuum 1400W x2
                        'MCSC124' => 1, // Buffing Electric
                        'MCSC126' => 1, // Car port
                        'MCSC127' => 1, // IBC Tank
                        'TSCW128' => 1,
                        'TCCW129' => 1,
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
            $this->command->info('✅ Seeded Car Wash successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
