<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizSewingMachinesSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Tailoring Machinery'],
                ['emoji' => '🧵', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Sewing machines'],
                ['image_url' => 'microbiz/sewing_machines.jpg']
            );

            // Items from Excel: Tailoring - SEWING MACHINES
            $itemsData = [
                ['code' => 'MTSM090', 'name' => 'Straight Machine Domestic (Butterfly)', 'unit_cost' => 73.00],
                ['code' => 'MTSM091', 'name' => 'Straight Machine Domestic (Singer)', 'unit_cost' => 85.00],
                ['code' => 'MTSM092', 'name' => 'Overlocking Machine Domestic (Butterfly)', 'unit_cost' => 100.00],
                ['code' => 'MTSM093', 'name' => 'Zig Zag Machine Domestic (Kawkab)', 'unit_cost' => 115.00],
                ['code' => 'MTSM094', 'name' => 'Overlocking Machine 4 Threader Industrial (Worlden)', 'unit_cost' => 465.00],
                ['code' => 'MTSM095', 'name' => 'Cutting Machine 8 inch (Worlden)', 'unit_cost' => 360.00],
                ['code' => 'MTSM096', 'name' => 'Cutting Machine 10 inch (Juki)', 'unit_cost' => 270.00],
                ['code' => 'MTSM097', 'name' => 'Cutting Machine Octar', 'unit_cost' => 70.00],
                ['code' => 'MTSM098', 'name' => 'Button Hole Machine Kawkab', 'unit_cost' => 1635.00],
                ['code' => 'MTSM099', 'name' => 'Straight Machine Industrial (Juki)', 'unit_cost' => 350.00],
                ['code' => 'MTSM100', 'name' => 'Flossy Machine Industrial (Juki)', 'unit_cost' => 770.00],
                ['code' => 'MTSM101', 'name' => 'Iron Industrial (Silverstar)', 'unit_cost' => 60.00],
                // Transport
                ['code' => 'TSTM103', 'name' => 'From source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TCTM104', 'name' => 'Courier charge', 'unit_cost' => 18.50],
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
            $this->command->info('Sewing Machines items created/updated.');

            // 5 tiers as per Excel (Lite, Standard, Full House, Gold 1, Gold 2)
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MTSM091' => 1, // Straight Singer
                        'MTSM092' => 1, // Overlocking Butterfly
                        'TCTM104' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MTSM099' => 1, // Straight Industrial Juki
                        'TCTM104' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MTSM092' => 1, // Overlocking Butterfly
                        'MTSM097' => 1, // Cutting Machine Octar
                        'MTSM099' => 1, // Straight Industrial Juki
                        'MTSM101' => 1, // Iron Industrial
                        'TSTM103' => 1,
                    ],
                ],
                'gold_1' => [
                    'name' => 'Gold Package 1',
                    'price' => 2210.00,
                    'items' => [
                        'MTSM094' => 1, // Overlocking Industrial
                        'MTSM099' => 3, // Straight Industrial x3
                        'TSTM103' => 1,
                    ],
                ],
                'gold_2' => [
                    'name' => 'Gold Package 2',
                    'price' => 2210.00,
                    'items' => [
                        'MTSM098' => 1, // Button Hole Machine
                        'TCTM104' => 1,
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
            $this->command->info('✅ Seeded Sewing Machines successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
