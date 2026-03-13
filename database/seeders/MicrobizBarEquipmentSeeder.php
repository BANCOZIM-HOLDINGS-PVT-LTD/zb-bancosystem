<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizBarEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Entertainment'],
                ['emoji' => '🎮', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Bar Entertainment'],
                ['image_url' => 'microbiz/bar_entertainment.jpg']
            );

            // Items from Excel: Entertainment - Bar Equipment
            $itemsData = [
                ['code' => 'METB065', 'name' => 'Speaker Tower', 'unit_cost' => 160.00],
                ['code' => 'METB066', 'name' => 'Dart body', 'unit_cost' => 20.00],
                ['code' => 'METB067', 'name' => '55 inch Television Smart TV', 'unit_cost' => 265.00],
                ['code' => 'METB068', 'name' => 'Decoder Azam', 'unit_cost' => 28.00],
                ['code' => 'METB069', 'name' => 'Decoder Subscription 4 month', 'unit_cost' => 40.00],
                ['code' => 'METB070', 'name' => 'Slug', 'unit_cost' => 600.00],
                ['code' => 'METB071', 'name' => 'Snooker Table Commercial', 'unit_cost' => 850.00],
                ['code' => 'METB072', 'name' => 'Disco lights', 'unit_cost' => 100.00],
                // Transport
                ['code' => 'TSBE073', 'name' => 'From source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TCBE074', 'name' => 'Courier charge 1', 'unit_cost' => 18.00],
                ['code' => 'TCBE075', 'name' => 'Courier charge 2', 'unit_cost' => 29.30],
                ['code' => 'TCBE076', 'name' => 'Courier charge 3', 'unit_cost' => 60.00],
                ['code' => 'TCBE077', 'name' => 'Courier charge 4', 'unit_cost' => 155.00],
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
            $this->command->info('Bar Equipment items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'METB065' => 1, // Speaker Tower
                        'METB066' => 1, // Dart body
                        'TCBE074' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'METB067' => 1, // 55" TV
                        'METB068' => 1, // Decoder
                        'METB069' => 1, // Subscription
                        'TCBE075' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'METB070' => 1, // Slug
                        'TSBE073' => 1,
                        'TCBE076' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'METB070' => 1, // Slug
                        'METB071' => 1, // Snooker Table
                        'METB072' => 1, // Disco lights
                        'TSBE073' => 1,
                        'TCBE077' => 1,
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
            $this->command->info('✅ Seeded Bar Entertainment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
