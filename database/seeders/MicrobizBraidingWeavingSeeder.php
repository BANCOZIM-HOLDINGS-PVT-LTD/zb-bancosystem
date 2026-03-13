<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizBraidingWeavingSeeder extends Seeder
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
                ['microbiz_category_id' => $category->id, 'name' => 'Braiding and weaving'],
                ['image_url' => 'microbiz/braiding_weaving.jpg']
            );

            // Items from Excel: BRAIDING, WEAVING, WIG INSTALLATION
            $itemsData = [
                ['code' => 'MBCW180', 'name' => 'Chair/Stool', 'unit_cost' => 40.00],
                ['code' => 'MBCW181', 'name' => 'Saloon Station (mirror, USB ports)', 'unit_cost' => 130.00],
                ['code' => 'MBCW182', 'name' => 'Hair dryer big', 'unit_cost' => 160.00],
                ['code' => 'MBCW183', 'name' => 'Hair dryer small', 'unit_cost' => 130.00],
                ['code' => 'MBCW184', 'name' => 'Wash Basin Medium', 'unit_cost' => 130.00],
                ['code' => 'MBCW185', 'name' => 'Thread', 'unit_cost' => 0.50],
                ['code' => 'MBCW186', 'name' => 'Needle set', 'unit_cost' => 1.00],
                ['code' => 'MBCW187', 'name' => 'Crochet needle', 'unit_cost' => 0.50],
                ['code' => 'MBCW188', 'name' => 'Scissors', 'unit_cost' => 5.00],
                ['code' => 'MBCW189', 'name' => 'Comb set (rat tail) set', 'unit_cost' => 2.00],
                ['code' => 'MBCW190', 'name' => 'Comb set (wide tooth) set', 'unit_cost' => 2.00],
                ['code' => 'MBCW191', 'name' => 'Blower', 'unit_cost' => 10.00],
                ['code' => 'MBCW192', 'name' => 'Flat iron', 'unit_cost' => 10.00],
                ['code' => 'MBCW193', 'name' => 'Curling iron', 'unit_cost' => 10.00],
                ['code' => 'MBCW194', 'name' => 'Hot comb', 'unit_cost' => 10.00],
                ['code' => 'MBCW195', 'name' => 'Clippers set', 'unit_cost' => 2.00],
                ['code' => 'MBCW196', 'name' => 'Wig brush set', 'unit_cost' => 2.00],
                ['code' => 'MBCW197', 'name' => 'Spray bottle', 'unit_cost' => 2.00],
                ['code' => 'MBCW198', 'name' => 'Elastic band', 'unit_cost' => 1.00],
                ['code' => 'MBCW199', 'name' => 'Edge brush', 'unit_cost' => 0.50],
                ['code' => 'MBCW200', 'name' => 'Wig cap', 'unit_cost' => 1.00],
                ['code' => 'MBCW201', 'name' => 'Wig head mannequin', 'unit_cost' => 9.00],
                ['code' => 'MBCW202', 'name' => 'Towels', 'unit_cost' => 3.00],
                ['code' => 'MBCW203', 'name' => 'Apron plain', 'unit_cost' => 5.00],
                // Consumables
                ['code' => 'MBCW204', 'name' => 'Bonding glue', 'unit_cost' => 3.00],
                ['code' => 'MBCW205', 'name' => 'Shampoo set', 'unit_cost' => 3.00],
                ['code' => 'MBCW206', 'name' => 'Conditioner', 'unit_cost' => 3.00],
                ['code' => 'MBCW207', 'name' => 'Locking gel', 'unit_cost' => 2.50],
                ['code' => 'MBCW208', 'name' => 'Styling Foam', 'unit_cost' => 2.50],
                ['code' => 'MBCW209', 'name' => 'Edge control', 'unit_cost' => 3.00],
                ['code' => 'MBCW210', 'name' => 'Hair oil', 'unit_cost' => 3.00],
                ['code' => 'MBCW211', 'name' => 'Holding spray', 'unit_cost' => 3.50],
                ['code' => 'MBCW212', 'name' => 'Heat protectant spray', 'unit_cost' => 2.00],
                ['code' => 'MBCW213', 'name' => 'Lace tint spray', 'unit_cost' => 3.00],
                ['code' => 'MBCW214', 'name' => 'Hair food', 'unit_cost' => 2.00],
                // Transport
                ['code' => 'TSCW215', 'name' => 'From source to Courier', 'unit_cost' => 5.00],
                ['code' => 'TCCW216', 'name' => 'Courier charge', 'unit_cost' => 17.00],
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
            $this->command->info('Braiding/Weaving items created/updated.');

            // 3 tiers only (no Gold) — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MBCW185' => 1,
                        'MBCW186' => 1,
                        'MBCW187' => 1,
                        'MBCW188' => 1,
                        'MBCW189' => 1,
                        'MBCW190' => 1,
                        'MBCW191' => 1,
                        'MBCW192' => 1,
                        'MBCW193' => 1,
                        'MBCW194' => 1,
                        'MBCW195' => 1,
                        'MBCW196' => 1,
                        'MBCW197' => 1,
                        'MBCW198' => 4,
                        'MBCW199' => 2,
                        'MBCW200' => 2,
                        'MBCW201' => 1,
                        'MBCW202' => 3,
                        'MBCW203' => 1,
                        'MBCW204' => 3,
                        'MBCW205' => 3,
                        'MBCW206' => 3,
                        'MBCW207' => 3,
                        'MBCW208' => 3,
                        'MBCW209' => 3,
                        'MBCW210' => 3,
                        'MBCW211' => 3,
                        'MBCW212' => 3,
                        'MBCW213' => 3,
                        'MBCW214' => 3,
                        'TSCW215' => 1,
                        'TCCW216' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MBCW183' => 1, // Hair dryer small
                        'MBCW185' => 1,
                        'MBCW186' => 1,
                        'MBCW187' => 1,
                        'MBCW188' => 1,
                        'MBCW189' => 1,
                        'MBCW190' => 1,
                        'MBCW191' => 1,
                        'MBCW192' => 1,
                        'MBCW193' => 1,
                        'MBCW194' => 1,
                        'MBCW195' => 1,
                        'MBCW196' => 1,
                        'MBCW197' => 1,
                        'MBCW198' => 4,
                        'MBCW199' => 2,
                        'MBCW200' => 2,
                        'MBCW201' => 1,
                        'MBCW202' => 3,
                        'MBCW203' => 1,
                        'MBCW204' => 3,
                        'MBCW205' => 3,
                        'MBCW206' => 3,
                        'MBCW207' => 3,
                        'MBCW208' => 3,
                        'MBCW209' => 3,
                        'MBCW210' => 3,
                        'MBCW211' => 3,
                        'MBCW212' => 3,
                        'MBCW213' => 3,
                        'MBCW214' => 3,
                        'TSCW215' => 1,
                        'TCCW216' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MBCW180' => 1, // Chair/Stool
                        'MBCW181' => 1, // Saloon Station
                        'MBCW183' => 1, // Hair dryer small
                        'MBCW184' => 1, // Wash Basin
                        'MBCW185' => 1,
                        'MBCW186' => 1,
                        'MBCW187' => 1,
                        'MBCW188' => 1,
                        'MBCW189' => 1,
                        'MBCW190' => 1,
                        'MBCW191' => 1,
                        'MBCW192' => 1,
                        'MBCW193' => 1,
                        'MBCW194' => 1,
                        'MBCW195' => 1,
                        'MBCW196' => 1,
                        'MBCW197' => 1,
                        'MBCW198' => 4,
                        'MBCW199' => 2,
                        'MBCW200' => 2,
                        'MBCW201' => 1,
                        'MBCW202' => 3,
                        'MBCW203' => 1,
                        'MBCW204' => 3,
                        'MBCW205' => 3,
                        'MBCW206' => 3,
                        'MBCW207' => 3,
                        'MBCW208' => 3,
                        'MBCW209' => 3,
                        'MBCW210' => 3,
                        'MBCW211' => 3,
                        'MBCW212' => 3,
                        'MBCW213' => 3,
                        'MBCW214' => 3,
                        'TSCW215' => 1,
                        'TCCW216' => 1,
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
            $this->command->info('✅ Seeded Braiding & Weaving successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
