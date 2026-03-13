<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizBarberSeeder extends Seeder
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
                ['microbiz_category_id' => $category->id, 'name' => 'Barber & Rasta'],
                ['image_url' => 'microbiz/barber.jpg']
            );

            // Items from Excel: BARBER
            $itemsData = [
                // Furniture & Equipment
                ['code' => 'MBCB139', 'name' => 'Barber station (with mirror)', 'unit_cost' => 130.00],
                ['code' => 'MBCB140', 'name' => 'Chair', 'unit_cost' => 40.00],
                ['code' => 'MBCB141', 'name' => 'Executive Barber Station', 'unit_cost' => 200.00],
                ['code' => 'MBCB142', 'name' => 'Hydraulic Chair', 'unit_cost' => 250.00],
                ['code' => 'MBCB143', 'name' => 'Electric hairclipper', 'unit_cost' => 20.00],
                ['code' => 'MBCB144', 'name' => 'Rechargeable hairclipper', 'unit_cost' => 20.00],
                ['code' => 'MBCB145', 'name' => 'Electric hairclipper Wahl/Kemei', 'unit_cost' => 70.00],
                ['code' => 'MBCB146', 'name' => 'Blower Sokany', 'unit_cost' => 10.00],
                ['code' => 'MBCB147', 'name' => 'Scissors set', 'unit_cost' => 5.00],
                ['code' => 'MBCB148', 'name' => 'Comb set (rat tail) set', 'unit_cost' => 2.00],
                ['code' => 'MBCB149', 'name' => 'Comb set (wide tooth) set', 'unit_cost' => 2.00],
                ['code' => 'MBCB150', 'name' => 'Bush sponge', 'unit_cost' => 5.00],
                ['code' => 'MBCB151', 'name' => 'Neck duster', 'unit_cost' => 6.00],
                ['code' => 'MBCB152', 'name' => 'Racket comb', 'unit_cost' => 2.00],
                ['code' => 'MBCB153', 'name' => 'Wave brush', 'unit_cost' => 3.00],
                ['code' => 'MBCB154', 'name' => 'Jack blade', 'unit_cost' => 10.00],
                ['code' => 'MBCB155', 'name' => 'Mixing bowl', 'unit_cost' => 3.00],
                ['code' => 'MBCB156', 'name' => 'Barber cape', 'unit_cost' => 10.00],
                ['code' => 'MBCB157', 'name' => 'Towel', 'unit_cost' => 3.00],
                ['code' => 'MBCB158', 'name' => 'Finger brush', 'unit_cost' => 3.00],
                ['code' => 'MBCB159', 'name' => 'UV Steriliser Box', 'unit_cost' => 75.00],
                ['code' => 'MBCB160', 'name' => 'Crochet needle set', 'unit_cost' => 1.00],
                ['code' => 'MBCB161', 'name' => 'Hand mirror', 'unit_cost' => 3.00],
                ['code' => 'MBCB162', 'name' => 'Spirit bottle', 'unit_cost' => 5.00],
                ['code' => 'MBCB163', 'name' => 'Smoothener', 'unit_cost' => 15.00],
                ['code' => 'MBCB164', 'name' => 'Apron plain', 'unit_cost' => 5.00],
                ['code' => 'MBCB165', 'name' => 'Professional Apron', 'unit_cost' => 20.00],
                ['code' => 'MBCB166', 'name' => 'Trolley', 'unit_cost' => 80.00],
                // Consumables
                ['code' => 'MBCB167', 'name' => 'Shaving cream', 'unit_cost' => 5.00],
                ['code' => 'MBCB168', 'name' => 'Gloves', 'unit_cost' => 0.30],
                ['code' => 'MBCB169', 'name' => 'After shave', 'unit_cost' => 5.00],
                ['code' => 'MBCB170', 'name' => 'Ethanol', 'unit_cost' => 5.00],
                ['code' => 'MBCB171', 'name' => 'Injector liquid black dye', 'unit_cost' => 3.00],
                ['code' => 'MBCB172', 'name' => 'Dermatol', 'unit_cost' => 5.00],
                ['code' => 'MBCB173', 'name' => 'Razor blade', 'unit_cost' => 0.10],
                ['code' => 'MBCB174', 'name' => 'Hairclipper Oil', 'unit_cost' => 3.50],
                ['code' => 'MBCB175', 'name' => 'Locking gel', 'unit_cost' => 2.50],
                ['code' => 'MBCB176', 'name' => 'Shampoo set', 'unit_cost' => 7.00],
                ['code' => 'MBCB177', 'name' => 'Mousse spray', 'unit_cost' => 2.50],
                // Transport
                ['code' => 'TCBR178', 'name' => 'From source to Courier', 'unit_cost' => 5.00],
                ['code' => 'TSBR179', 'name' => 'Courier charge', 'unit_cost' => 16.00],
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
            $this->command->info('Barber items created/updated.');

            // Packages — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MBCB143' => 1,  // Electric hairclipper
                        'MBCB144' => 1,  // Rechargeable hairclipper
                        'MBCB146' => 1,  // Blower
                        'MBCB147' => 1,  // Scissors
                        'MBCB148' => 1,  // Comb rat tail
                        'MBCB149' => 1,  // Comb wide tooth
                        'MBCB150' => 1,  // Bush sponge
                        'MBCB151' => 1,  // Neck duster
                        'MBCB152' => 1,  // Racket comb
                        'MBCB153' => 1,  // Wave brush
                        'MBCB154' => 1,  // Jack blade
                        'MBCB155' => 1,  // Mixing bowl
                        'MBCB156' => 1,  // Barber cape
                        'MBCB157' => 1,  // Towel
                        'MBCB158' => 1,  // Finger brush
                        'MBCB160' => 1,  // Crochet needle
                        'MBCB161' => 1,  // Hand mirror
                        'MBCB162' => 1,  // Spirit bottle
                        'MBCB164' => 1,  // Apron
                        'MBCB167' => 1,  // Shaving cream
                        'MBCB168' => 10, // Gloves x10
                        'MBCB169' => 1,  // After shave
                        'MBCB170' => 1,  // Ethanol
                        'MBCB171' => 1,  // Dye
                        'MBCB172' => 1,  // Dermatol
                        'MBCB173' => 25, // Razor blades x25
                        'MBCB174' => 1,  // Clipper Oil
                        'MBCB175' => 1,  // Locking gel
                        'MBCB176' => 1,  // Shampoo
                        'MBCB177' => 1,  // Mousse spray
                        'TCBR178' => 1,
                        'TSBR179' => 1,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MBCB143' => 1,
                        'MBCB144' => 1,
                        'MBCB146' => 1,
                        'MBCB147' => 1,
                        'MBCB148' => 1,
                        'MBCB149' => 1,
                        'MBCB150' => 1,
                        'MBCB151' => 1,
                        'MBCB152' => 1,
                        'MBCB153' => 1,
                        'MBCB154' => 1,
                        'MBCB155' => 1,
                        'MBCB156' => 1,
                        'MBCB157' => 3,  // Towel x3
                        'MBCB158' => 2,  // Finger brush x2
                        'MBCB159' => 1,  // UV Steriliser
                        'MBCB160' => 2,  // Crochet needle x2
                        'MBCB161' => 1,
                        'MBCB162' => 1,
                        'MBCB163' => 1,  // Smoothener
                        'MBCB164' => 1,
                        'MBCB167' => 3,  // Shaving cream x3
                        'MBCB168' => 30, // Gloves x30
                        'MBCB169' => 3,
                        'MBCB170' => 3,
                        'MBCB171' => 3,
                        'MBCB172' => 3,
                        'MBCB173' => 75, // Razor blades x75
                        'MBCB174' => 1,
                        'MBCB175' => 1,
                        'MBCB176' => 2,
                        'MBCB177' => 2,
                        'TCBR178' => 1,
                        'TSBR179' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MBCB139' => 1,  // Barber station
                        'MBCB140' => 1,  // Chair
                        'MBCB143' => 2,  // Clippers x2
                        'MBCB146' => 1,
                        'MBCB147' => 1,
                        'MBCB148' => 1,
                        'MBCB149' => 1,
                        'MBCB150' => 2,
                        'MBCB151' => 2,
                        'MBCB152' => 2,
                        'MBCB153' => 1,
                        'MBCB154' => 1,
                        'MBCB155' => 1,
                        'MBCB156' => 1,
                        'MBCB157' => 4,
                        'MBCB158' => 3,
                        'MBCB159' => 1,
                        'MBCB160' => 2,
                        'MBCB161' => 1,
                        'MBCB162' => 1,
                        'MBCB163' => 1,
                        'MBCB164' => 1,
                        'MBCB167' => 4,
                        'MBCB168' => 30,
                        'MBCB169' => 4,
                        'MBCB170' => 4,
                        'MBCB171' => 6,
                        'MBCB172' => 4,
                        'MBCB173' => 150,
                        'MBCB174' => 3,
                        'MBCB175' => 3,
                        'MBCB176' => 4,
                        'MBCB177' => 3,
                        'TCBR178' => 1,
                        'TSBR179' => 1,
                    ],
                ],
                'gold' => [
                    'name' => 'Gold Package',
                    'price' => 2210.00,
                    'items' => [
                        'MBCB141' => 2,  // Executive Station x2
                        'MBCB142' => 2,  // Hydraulic Chair x2
                        'MBCB143' => 3,  // Clippers x3
                        'MBCB145' => 1,  // Wahl/Kemei
                        'MBCB146' => 1,
                        'MBCB147' => 1,
                        'MBCB148' => 1,
                        'MBCB149' => 1,
                        'MBCB150' => 3,
                        'MBCB151' => 6,
                        'MBCB152' => 6,
                        'MBCB153' => 1,
                        'MBCB154' => 1,
                        'MBCB155' => 1,
                        'MBCB156' => 1,
                        'MBCB157' => 6,
                        'MBCB158' => 3,
                        'MBCB159' => 1,
                        'MBCB160' => 2,
                        'MBCB161' => 1,
                        'MBCB162' => 1,
                        'MBCB163' => 1,
                        'MBCB165' => 2,  // Professional Apron x2
                        'MBCB166' => 1,  // Trolley
                        'MBCB167' => 6,
                        'MBCB168' => 30,
                        'MBCB169' => 6,
                        'MBCB170' => 6,
                        'MBCB171' => 6,
                        'MBCB172' => 6,
                        'MBCB173' => 150,
                        'MBCB174' => 3,
                        'MBCB175' => 3,
                        'MBCB176' => 4,
                        'MBCB177' => 3,
                        'TCBR178' => 1,
                        'TSBR179' => 1,
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
            $this->command->info('✅ Seeded Barber successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
