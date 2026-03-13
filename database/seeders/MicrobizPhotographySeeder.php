<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizPhotographySeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Events Management'],
                ['emoji' => '🎪', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Photography'],
                ['image_url' => 'microbiz/photography.jpg']
            );

            // Items from Excel: MEDIA PRODUCTION - Photography
            $itemsData = [
                ['code' => 'MBMP267', 'name' => 'SD card Sandisk 128gb extreme 200mb', 'unit_cost' => 50.00],
                ['code' => 'MBMP268', 'name' => 'Speed light Godox TT 600', 'unit_cost' => 120.00],
                ['code' => 'MBMP269', 'name' => 'Trigger', 'unit_cost' => 60.00],
                ['code' => 'MBMP270', 'name' => 'Studio Umbrella 80cm', 'unit_cost' => 45.00],
                ['code' => 'MBMP271', 'name' => 'Studio umbrella stand', 'unit_cost' => 50.00],
                ['code' => 'MBMP272', 'name' => 'Soft box 80cm', 'unit_cost' => 70.00],
                ['code' => 'MBMP273', 'name' => 'Soft box 90cm', 'unit_cost' => 95.00],
                ['code' => 'MBMP274', 'name' => 'Soft box 120cm', 'unit_cost' => 130.00],
                ['code' => 'MBMP275', 'name' => 'Soft box stand', 'unit_cost' => 100.00],
                ['code' => 'MBMP276', 'name' => 'Camera Canon 2000D', 'unit_cost' => 500.00],
                ['code' => 'MBMP277', 'name' => 'Extra battery', 'unit_cost' => 30.00],
                ['code' => 'MBMP278', 'name' => 'Tripod Stand', 'unit_cost' => 120.00],
                ['code' => 'MBMP279', 'name' => 'Back Drops 3m x 6m', 'unit_cost' => 150.00],
                ['code' => 'MBMP280', 'name' => 'Back Drops 1.5m x 6m', 'unit_cost' => 75.00],
                ['code' => 'MBMP281', 'name' => 'Back Drops Bars', 'unit_cost' => 250.00],
                ['code' => 'MBMP282', 'name' => 'Lighting kit', 'unit_cost' => 120.00],
                ['code' => 'MBMP283', 'name' => 'Stand lighting kit', 'unit_cost' => 10.00],
                // Transport
                ['code' => 'TSMP284', 'name' => 'From source to Courier', 'unit_cost' => 5.00],
                ['code' => 'TSMP285', 'name' => 'Courier charge', 'unit_cost' => 17.00],
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
            $this->command->info('Photography items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MBMP270' => 1, // Studio Umbrella
                        'MBMP271' => 1, // Umbrella stand
                        'MBMP280' => 1, // Back Drops 1.5m
                        'TSMP284' => 1,
                        'TSMP285' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MBMP268' => 1, // Speed light
                        'MBMP269' => 1, // Trigger
                        'MBMP270' => 1, // Studio Umbrella
                        'MBMP271' => 1, // Umbrella stand
                        'MBMP280' => 1, // Back Drops 1.5m
                        'TSMP284' => 1,
                        'TSMP285' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MBMP276' => 1, // Camera Canon 2000D
                        'MBMP278' => 1, // Tripod Stand
                        'TSMP284' => 1,
                        'TSMP285' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MBMP267' => 1, // SD card
                        'MBMP268' => 1, // Speed light
                        'MBMP269' => 1, // Trigger
                        'MBMP270' => 1, // Studio Umbrella
                        'MBMP271' => 1, // Umbrella stand
                        'MBMP273' => 1, // Soft box 90cm
                        'MBMP274' => 1, // Soft box 120cm
                        'MBMP275' => 1, // Soft box stand
                        'MBMP276' => 1, // Camera Canon 2000D
                        'MBMP278' => 1, // Tripod Stand
                        'MBMP279' => 1, // Back Drops 3m
                        'MBMP282' => 1, // Lighting kit
                        'MBMP283' => 1, // Stand lighting kit
                        'TSMP284' => 1,
                        'TSMP285' => 1,
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
            $this->command->info('✅ Seeded Photography successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
