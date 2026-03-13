<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizDriversLicenseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Personal Development'],
                ['emoji' => '📚', 'domain' => 'service']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Drivers Licence'],
                ['image_url' => 'microbiz/drivers_license.jpg']
            );

            // Items from Excel: Personal Dev - DRIVERS LICENSE
            $itemsData = [
                ['code' => 'PDDL105', 'name' => 'Oral lessons (until you pass)', 'unit_cost' => 10.00],
                ['code' => 'PDDL106', 'name' => 'Provisional Licence class 2/1 (one attempt)', 'unit_cost' => 20.00],
                ['code' => 'PDDL107', 'name' => 'Provisional Licence class 4/3 (max two attempts)', 'unit_cost' => 20.00],
                ['code' => 'PDDL108', 'name' => 'Practical Lessons class 4/3 (30 x half hour)', 'unit_cost' => 4.00],
                ['code' => 'PDDL109', 'name' => 'Practical Lessons class 1/2 (10 x half hour)', 'unit_cost' => 9.00],
                ['code' => 'PDDL110', 'name' => 'Car Hire class 4/3 VID (one attempt)', 'unit_cost' => 30.00],
                ['code' => 'PDDL111', 'name' => 'Car Hire class 4/3 VID (max two attempts)', 'unit_cost' => 30.00],
                ['code' => 'PDDL112', 'name' => 'Car Hire class 2/1 VID (max two attempts)', 'unit_cost' => 55.00],
                ['code' => 'PDDL113', 'name' => 'Road Test class 4/3 VID booking (one attempt)', 'unit_cost' => 25.00],
                ['code' => 'PDDL114', 'name' => 'Road Test class 4/3 VID booking (two attempts)', 'unit_cost' => 25.00],
                ['code' => 'PDDL115', 'name' => 'Road Test class 2/1 VID booking (max two attempts)', 'unit_cost' => 30.00],
                ['code' => 'PDDL116', 'name' => 'Defensive Driving Course (2 day)', 'unit_cost' => 45.00],
                ['code' => 'PDDL117', 'name' => 'Defensive Driving Test (road, vision, psychomotor)', 'unit_cost' => 60.00],
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
            $this->command->info('Drivers License items created/updated.');

            // 3 tiers only (no Gold) as per Excel
            // Lite = Class 4 or 3, Standard = Class 1 or 2, Full House = Class 4&1 Combo
            $packages = [
                'lite' => [
                    'name' => 'Lite Package (Class 4/3)',
                    'price' => 260.00,
                    'items' => [
                        'PDDL105' => 1,  // Oral lessons
                        'PDDL106' => 1,  // Provisional class 2/1
                        'PDDL108' => 30, // Practical class 4/3 x30
                        'PDDL110' => 1,  // Car Hire class 4/3
                        'PDDL113' => 1,  // Road Test class 4/3
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package (Class 1/2)',
                    'price' => 455.00,
                    'items' => [
                        'PDDL109' => 10, // Practical class 1/2 x10
                        'PDDL112' => 2,  // Car Hire class 2/1 x2
                        'PDDL116' => 1,  // Defensive Driving Course
                        'PDDL117' => 1,  // Defensive Driving Test
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package (Class 4&1 Combo)',
                    'price' => 864.00,
                    'items' => [
                        'PDDL105' => 1,  // Oral lessons
                        'PDDL107' => 2,  // Provisional class 4/3 x2
                        'PDDL108' => 30, // Practical class 4/3 x30
                        'PDDL109' => 10, // Practical class 1/2 x10
                        'PDDL111' => 2,  // Car Hire class 4/3 x2
                        'PDDL112' => 2,  // Car Hire class 2/1 x2
                        'PDDL114' => 2,  // Road Test class 4/3 x2
                        'PDDL115' => 2,  // Road Test class 2/1 x2
                        'PDDL116' => 1,  // Defensive Driving Course
                        'PDDL117' => 1,  // Defensive Driving Test
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
            $this->command->info('✅ Seeded Drivers License successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
