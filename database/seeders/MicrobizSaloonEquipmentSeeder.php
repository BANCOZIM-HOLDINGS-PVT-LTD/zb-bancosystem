<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizSaloonEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Beauty, Hair and Cosmetics'],
                ['emoji' => '💇', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Saloon equipment'],
                ['image_url' => 'microbiz/saloon_equipment.jpg']
            );

            // Items from Excel: SALOON EQUIPMENT
            $itemsData = [
                // Furniture
                ['code' => 'MBCF128', 'name' => 'Chair/Stool', 'unit_cost' => 40.00],
                ['code' => 'MBCF129', 'name' => 'Saloon Station (Mirrors, USB ports)', 'unit_cost' => 130.00],
                // Equipment
                ['code' => 'MBCF130', 'name' => 'Wash Basin Medium', 'unit_cost' => 130.00],
                ['code' => 'MBCF131', 'name' => 'Hair Dryer Moveable', 'unit_cost' => 130.00],
                ['code' => 'MBCF132', 'name' => 'Wash Basin Big', 'unit_cost' => 250.00],
                ['code' => 'MBCF133', 'name' => 'Executive Barber Chairs Big', 'unit_cost' => 250.00],
                ['code' => 'MBCF134', 'name' => 'War Dryer Mounted', 'unit_cost' => 160.00],
                ['code' => 'MBCF135', 'name' => 'Trolley', 'unit_cost' => 40.00],
                // Transport
                ['code' => 'TSSF136', 'name' => 'Transport Indrive', 'unit_cost' => 6.00],
                ['code' => 'TCSF138', 'name' => 'Transport Small Truck', 'unit_cost' => 20.00],
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
            $this->command->info('Saloon Equipment items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MBCF128' => 1,  // Chair/Stool
                        'MBCF129' => 1,  // Saloon Station
                        'TSSF136' => 1,  // Transport Indrive
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MBCF128' => 2,  // Chair/Stool x2
                        'MBCF129' => 2,  // Saloon Station x2
                        'TSSF136' => 1,  // Transport Indrive
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MBCF128' => 3,  // Chair/Stool x3
                        'MBCF129' => 3,  // Saloon Station x3
                        'MBCF135' => 2,  // Trolley x2
                        'TCSF138' => 1,  // Transport Small Truck
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MBCF128' => 5,  // Chair/Stool x5
                        'MBCF129' => 5,  // Saloon Station x5
                        'MBCF131' => 1,  // Hair Dryer Moveable
                        'MBCF132' => 1,  // Wash Basin Big
                        'MBCF133' => 1,  // Executive Barber Chairs
                        'MBCF135' => 1,  // Trolley
                        'TSSF136' => 1,
                        'TCSF138' => 1,
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
            $this->command->info('✅ Seeded Saloon Equipment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
