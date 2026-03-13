<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizPASystemsSeeder extends Seeder
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
                ['microbiz_category_id' => $category->id, 'name' => 'PA system'],
                ['image_url' => 'microbiz/pa_systems.jpg']
            );

            // Items from Excel: Events Man - PA systems
            $itemsData = [
                ['code' => 'MEMP051', 'name' => 'Mixer', 'unit_cost' => 145.00],
                ['code' => 'MEMP052', 'name' => 'Mixer', 'unit_cost' => 200.00],
                ['code' => 'MEMP053', 'name' => 'Microphone', 'unit_cost' => 32.00],
                ['code' => 'MEMP054', 'name' => 'Microphone', 'unit_cost' => 125.00],
                ['code' => 'MEMP055', 'name' => 'Top Speaker', 'unit_cost' => 180.00],
                ['code' => 'MEMP056', 'name' => 'Base Bin Speaker', 'unit_cost' => 460.00],
                ['code' => 'MEMP057', 'name' => 'Amplifier', 'unit_cost' => 360.00],
                ['code' => 'MEMP058', 'name' => 'Speaker Cables', 'unit_cost' => 12.00],
                ['code' => 'MEMP059', 'name' => 'Pole Stand', 'unit_cost' => 35.00],
                // Transport
                ['code' => 'TSPS060', 'name' => 'From source to Courier', 'unit_cost' => 20.00],
                ['code' => 'TCPS061', 'name' => 'Courier charge 1', 'unit_cost' => 17.70],
                ['code' => 'TCPS062', 'name' => 'Courier charge 2', 'unit_cost' => 32.50],
                ['code' => 'TCPS063', 'name' => 'Courier charge 3', 'unit_cost' => 59.60],
                ['code' => 'TCPS064', 'name' => 'Courier charge 4', 'unit_cost' => 157.60],
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
            $this->command->info('PA Systems items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MEMP051' => 1, // Mixer 6ch
                        'MEMP053' => 1, // Mic coded 3pack
                        'TSPS060' => 1,
                        'TCPS061' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MEMP051' => 1, // Mixer 6ch
                        'MEMP055' => 1, // Top Speaker 15in
                        'TSPS060' => 1,
                        'TCPS062' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MEMP051' => 1, // Mixer 6ch
                        'MEMP053' => 1, // Mic coded 3pack
                        'MEMP055' => 2, // Top Speaker 15in x2
                        'MEMP058' => 2, // Speaker Cables x2
                        'MEMP059' => 1, // Pole Stand
                        'TSPS060' => 1,
                        'TCPS063' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MEMP052' => 1, // Mixer 12ch
                        'MEMP054' => 1, // Mic codeless x2
                        'MEMP055' => 2, // Top Speaker 15in x2
                        'MEMP056' => 1, // Base Bin Speaker
                        'MEMP057' => 1, // Amplifier
                        'MEMP058' => 3, // Speaker Cables x3
                        'MEMP059' => 1, // Pole Stand
                        'TSPS060' => 1,
                        'TCPS064' => 1,
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
            $this->command->info('✅ Seeded PA Systems successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
