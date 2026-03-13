<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizVenueSetupSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Events Management'],
                ['emoji' => '🎉', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Venue Set up'],
                ['image_url' => 'microbiz/venue_setup.jpg']
            );

            // Items from Excel: Events Man - VENUE SET UP
            $itemsData = [
                ['code' => 'MEMV078', 'name' => 'Tiffany chairs', 'unit_cost' => 12.00],
                ['code' => 'MEMV079', 'name' => 'Foldable Tables Rectangle', 'unit_cost' => 45.00],
                ['code' => 'MEMV080', 'name' => 'Small Foldable table', 'unit_cost' => 30.00],
                ['code' => 'MEMV081', 'name' => 'Foldable table', 'unit_cost' => 45.00],
                ['code' => 'MEMV082', 'name' => 'Tent Peg and Pole 100 Seater (5mx5m)', 'unit_cost' => 1800.00],
                ['code' => 'MEMV083', 'name' => 'Tent Peg and Pole 50 Seater (5mx5m)', 'unit_cost' => 1000.00],
                ['code' => 'MEMV084', 'name' => 'Bride and Grooms chairs', 'unit_cost' => 200.00],
                // Plastic chairs (no code in Excel — use MEMV prefix)
                ['code' => 'MEMV085', 'name' => 'Plastic Chairs', 'unit_cost' => 4.00],
                // Transport
                ['code' => 'TSMV086', 'name' => 'From source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TCMV087', 'name' => 'Courier charge 1', 'unit_cost' => 16.00],
                ['code' => 'TCMV088', 'name' => 'Courier charge 2', 'unit_cost' => 30.60],
                ['code' => 'TCMV089', 'name' => 'Courier charge 3', 'unit_cost' => 58.00],
                ['code' => 'TCMV090', 'name' => 'Courier charge 4', 'unit_cost' => 180.00],
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
            $this->command->info('Venue Setup items created/updated.');

            // 7 tier packages as per Excel (Lite, Std1, Std2, FH1, FH2, Gold1, Gold2)
            // Standardised selling prices per tier family
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MEMV085' => 40, // Plastic chairs x40
                        'TSMV086' => 1,
                        'TCMV087' => 1,
                    ],
                ],
                'standard_1' => [
                    'name' => 'Standard Package 1',
                    'price' => 455.00,
                    'items' => [
                        'MEMV078' => 18, // Tiffany chairs x18
                        'MEMV079' => 2,  // Foldable Tables x2
                        'TSMV086' => 1,
                        'TCMV088' => 1,
                    ],
                ],
                'standard_2' => [
                    'name' => 'Standard Package 2',
                    'price' => 455.00,
                    'items' => [
                        'MEMV085' => 75, // Plastic chairs x75
                        'TSMV086' => 1,
                        'TCMV088' => 1,
                    ],
                ],
                'full_house_1' => [
                    'name' => 'Full House Package 1',
                    'price' => 864.00,
                    'items' => [
                        'MEMV085' => 145, // Plastic chairs x145
                        'TSMV086' => 1,
                        'TCMV089' => 1,
                    ],
                ],
                'full_house_2' => [
                    'name' => 'Full House Package 2',
                    'price' => 864.00,
                    'items' => [
                        'MEMV078' => 35, // Tiffany chairs x35
                        'MEMV079' => 3,  // Foldable Tables x3
                        'MEMV080' => 1,  // Small Foldable table
                        'TSMV086' => 1,
                        'TCMV089' => 1,
                    ],
                ],
                'gold_1' => [
                    'name' => 'Gold Package 1',
                    'price' => 2210.00,
                    'items' => [
                        'MEMV082' => 1,  // Tent 100 Seater
                        'TSMV086' => 1,
                        'TCMV090' => 1,
                    ],
                ],
                'gold_2' => [
                    'name' => 'Gold Package 2',
                    'price' => 2210.00,
                    'items' => [
                        'MEMV085' => 50,  // Plastic chairs x50
                        'MEMV078' => 6,   // Tiffany chairs x6
                        'MEMV079' => 2,   // Foldable Tables x2
                        'MEMV081' => 5,   // Foldable table x5
                        'MEMV083' => 1,   // Tent 50 Seater
                        'MEMV084' => 1,   // Bride/Groom chairs
                        'TSMV086' => 1,
                        'TCMV090' => 1,
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
            $this->command->info('✅ Seeded Venue Setup successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
