<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizNailsMakeupSeeder extends Seeder
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
                ['microbiz_category_id' => $category->id, 'name' => 'Nails and makeup'],
                ['image_url' => 'microbiz/nails_makeup.jpg']
            );

            // Items from Excel: NAILS
            $itemsData = [
                // Equipment
                ['code' => 'MBNM215', 'name' => 'Nail station', 'unit_cost' => 130.00],
                ['code' => 'MBNM216', 'name' => 'Chairs', 'unit_cost' => 45.00],
                ['code' => 'MBNM217', 'name' => 'Nail lamp', 'unit_cost' => 10.00],
                ['code' => 'MBNM218', 'name' => 'UV light', 'unit_cost' => 10.00],
                ['code' => 'MBNM219', 'name' => 'Acrylic brushes set', 'unit_cost' => 5.00],
                ['code' => 'MBNM220', 'name' => 'Buffer', 'unit_cost' => 0.50],
                ['code' => 'MBNM221', 'name' => 'Filers set', 'unit_cost' => 0.50],
                ['code' => 'MBNM222', 'name' => 'Bowl', 'unit_cost' => 3.00],
                ['code' => 'MBNM223', 'name' => 'Cuticle set (tweezer, nail cutter)', 'unit_cost' => 5.00],
                ['code' => 'MBNM224', 'name' => 'Drill set', 'unit_cost' => 5.00],
                ['code' => 'MBNM225', 'name' => 'Cosmetic storage box', 'unit_cost' => 25.00],
                // Nails Consumables
                ['code' => 'MBNM226', 'name' => 'Cotton 1kg', 'unit_cost' => 2.00],
                ['code' => 'MBNM227', 'name' => 'Regular polish', 'unit_cost' => 1.00],
                ['code' => 'MBNM228', 'name' => 'Gel polisher', 'unit_cost' => 1.00],
                ['code' => 'MBNM229', 'name' => 'Color clear', 'unit_cost' => 1.00],
                ['code' => 'MBNM230', 'name' => 'Basecoat', 'unit_cost' => 1.00],
                ['code' => 'MBNM231', 'name' => 'Top coat', 'unit_cost' => 1.00],
                ['code' => 'MBNM232', 'name' => 'Printed stick on finger nails', 'unit_cost' => 0.50],
                ['code' => 'MBNM233', 'name' => 'Plain stick on finger nails', 'unit_cost' => 0.50],
                ['code' => 'MBNM234', 'name' => 'Printed stick on toes nails', 'unit_cost' => 0.50],
                ['code' => 'MBNM235', 'name' => 'Plain stick on toes nails', 'unit_cost' => 0.50],
                ['code' => 'MBNM236', 'name' => 'Glass tips', 'unit_cost' => 2.50],
                ['code' => 'MBNM237', 'name' => 'White tips', 'unit_cost' => 4.50],
                ['code' => 'MBNM238', 'name' => 'Nail glue set', 'unit_cost' => 1.00],
                ['code' => 'MBNM239', 'name' => 'Nail polish remover/acetone 1L', 'unit_cost' => 5.00],
                ['code' => 'MBNM240', 'name' => 'Acrylic liquid 1L', 'unit_cost' => 5.00],
                ['code' => 'MBNM241', 'name' => 'Acrylic powders (colors) assorted set', 'unit_cost' => 6.00],
                ['code' => 'MBNM242', 'name' => 'Natural nails box', 'unit_cost' => 0.25],
                ['code' => 'MBNM243', 'name' => 'Stickers pack', 'unit_cost' => 1.00],
                ['code' => 'MBNM244', 'name' => 'Stones pack', 'unit_cost' => 2.00],
                ['code' => 'MBNM245', 'name' => 'Towels (Face)', 'unit_cost' => 1.00],
                ['code' => 'MBNM246', 'name' => 'Magnolia/baby oil', 'unit_cost' => 2.00],
                // Makeup Consumables
                ['code' => 'MBNM247', 'name' => 'Brushes', 'unit_cost' => 3.00],
                ['code' => 'MBNM248', 'name' => 'Fan', 'unit_cost' => 4.00],
                ['code' => 'MBNM249', 'name' => 'Lip stick', 'unit_cost' => 1.00],
                ['code' => 'MBNM250', 'name' => 'Lip gloss set', 'unit_cost' => 5.00],
                ['code' => 'MBNM251', 'name' => 'Eye brow pencils', 'unit_cost' => 0.25],
                ['code' => 'MBNM252', 'name' => 'Eye Liners', 'unit_cost' => 0.25],
                ['code' => 'MBNM253', 'name' => 'Lip liners', 'unit_cost' => 0.50],
                ['code' => 'MBNM254', 'name' => 'Eye shadow', 'unit_cost' => 2.00],
                ['code' => 'MBNM255', 'name' => 'Eye lashes (singles) different sizes', 'unit_cost' => 1.50],
                ['code' => 'MBNM256', 'name' => 'Eye lashes (belts) different sizes', 'unit_cost' => 2.00],
                ['code' => 'MBNM257', 'name' => 'Lash glue', 'unit_cost' => 2.00],
                ['code' => 'MBNM258', 'name' => 'Mascara', 'unit_cost' => 1.00],
                ['code' => 'MBNM259', 'name' => 'Foundation (assorted) Mary Kay', 'unit_cost' => 3.00],
                ['code' => 'MBNM260', 'name' => 'Face Powder Kiss Beauty', 'unit_cost' => 3.00],
                ['code' => 'MBNM261', 'name' => 'Sponge', 'unit_cost' => 0.50],
                ['code' => 'MBNM262', 'name' => 'Make-up spritz', 'unit_cost' => 3.00],
                ['code' => 'MBNM263', 'name' => 'Facial wipes', 'unit_cost' => 2.00],
                ['code' => 'MBNM264', 'name' => 'Banana powder', 'unit_cost' => 3.00],
                ['code' => 'MBNM265', 'name' => 'Concealers', 'unit_cost' => 0.50],
                ['code' => 'MBNM266', 'name' => 'Primers', 'unit_cost' => 1.00],
                ['code' => 'MBNM267', 'name' => 'Blushes', 'unit_cost' => 2.00],
                // Transport
                ['code' => 'TSNM268', 'name' => 'From source to Courier', 'unit_cost' => 5.00],
                ['code' => 'TCNM269', 'name' => 'Courier charge', 'unit_cost' => 31.00],
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
            $this->command->info('Nails & Makeup items created/updated.');

            // 3 tiers only (no Gold) — standardised selling prices
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MBNM218' => 1,  // UV light
                        'MBNM219' => 1,  // Acrylic brushes
                        'MBNM220' => 1,  // Buffer
                        'MBNM221' => 2,  // Filers x2
                        'MBNM222' => 1,  // Bowl
                        'MBNM223' => 1,  // Cuticle set
                        'MBNM224' => 1,  // Drill set
                        'MBNM225' => 1,  // Storage box
                        'MBNM226' => 1,  // Cotton
                        'MBNM227' => 5,  // Regular polish x5
                        'MBNM228' => 5,  // Gel polisher x5
                        'MBNM229' => 5,
                        'MBNM230' => 5,
                        'MBNM231' => 5,
                        'MBNM232' => 5,
                        'MBNM233' => 5,
                        'MBNM234' => 5,
                        'MBNM235' => 5,
                        'MBNM236' => 2,
                        'MBNM237' => 2,
                        'MBNM238' => 5,
                        'MBNM239' => 1,
                        'MBNM240' => 1,
                        'MBNM241' => 1,
                        'MBNM242' => 24,
                        'MBNM243' => 2,
                        'MBNM244' => 2,
                        'MBNM245' => 2,
                        'MBNM246' => 2,
                        'MBNM247' => 1,  // Brushes
                        'MBNM248' => 1,  // Fan
                        'MBNM249' => 1,  // Lip stick
                        'MBNM250' => 1,  // Lip gloss set
                        'MBNM251' => 2,
                        'MBNM252' => 2,
                        'MBNM253' => 5,
                        'MBNM254' => 2,
                        'MBNM255' => 2,
                        'MBNM256' => 2,
                        'MBNM257' => 2,
                        'MBNM258' => 3,
                        'MBNM259' => 2,
                        'MBNM260' => 2,
                        'MBNM261' => 2,
                        'MBNM262' => 2,
                        'MBNM263' => 2,
                        'MBNM264' => 2,
                        'MBNM265' => 2,
                        'MBNM266' => 2,
                        'MBNM267' => 2,
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MBNM218' => 1,
                        'MBNM219' => 1,
                        'MBNM220' => 1,
                        'MBNM221' => 2,
                        'MBNM222' => 1,
                        'MBNM223' => 1,
                        'MBNM224' => 1,
                        'MBNM225' => 1,
                        'MBNM226' => 2,
                        'MBNM227' => 10,
                        'MBNM228' => 10,
                        'MBNM229' => 10,
                        'MBNM230' => 10,
                        'MBNM231' => 10,
                        'MBNM232' => 10,
                        'MBNM233' => 10,
                        'MBNM234' => 10,
                        'MBNM235' => 10,
                        'MBNM236' => 4,
                        'MBNM237' => 4,
                        'MBNM238' => 10,
                        'MBNM239' => 2,
                        'MBNM240' => 2,
                        'MBNM241' => 2,
                        'MBNM242' => 48,
                        'MBNM243' => 4,
                        'MBNM244' => 4,
                        'MBNM245' => 4,
                        'MBNM246' => 4,
                        'MBNM247' => 1,
                        'MBNM248' => 1,
                        'MBNM249' => 1,
                        'MBNM250' => 1,
                        'MBNM251' => 2,
                        'MBNM252' => 2,
                        'MBNM253' => 5,
                        'MBNM254' => 2,
                        'MBNM255' => 2,
                        'MBNM256' => 2,
                        'MBNM257' => 2,
                        'MBNM258' => 3,
                        'MBNM259' => 2,
                        'MBNM260' => 2,
                        'MBNM261' => 2,
                        'MBNM262' => 2,
                        'MBNM263' => 2,
                        'MBNM264' => 2,
                        'MBNM265' => 2,
                        'MBNM266' => 2,
                        'MBNM267' => 2,
                        'TSNM268' => 1,
                        'TCNM269' => 1,
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MBNM215' => 1,  // Nail station
                        'MBNM216' => 1,  // Chairs
                        'MBNM217' => 1,  // Nail lamp
                        'MBNM218' => 1,
                        'MBNM219' => 1,
                        'MBNM220' => 1,
                        'MBNM221' => 2,
                        'MBNM222' => 1,
                        'MBNM223' => 1,
                        'MBNM224' => 1,
                        'MBNM225' => 1,
                        'MBNM226' => 2,
                        'MBNM227' => 10,
                        'MBNM228' => 10,
                        'MBNM229' => 10,
                        'MBNM230' => 10,
                        'MBNM231' => 10,
                        'MBNM232' => 10,
                        'MBNM233' => 10,
                        'MBNM234' => 10,
                        'MBNM235' => 10,
                        'MBNM236' => 4,
                        'MBNM237' => 4,
                        'MBNM238' => 10,
                        'MBNM239' => 2,
                        'MBNM240' => 2,
                        'MBNM241' => 2,
                        'MBNM242' => 48,
                        'MBNM243' => 4,
                        'MBNM244' => 4,
                        'MBNM245' => 4,
                        'MBNM246' => 4,
                        'MBNM247' => 2,
                        'MBNM248' => 2,
                        'MBNM249' => 2,
                        'MBNM250' => 2,
                        'MBNM251' => 4,
                        'MBNM252' => 4,
                        'MBNM253' => 10,
                        'MBNM254' => 4,
                        'MBNM255' => 4,
                        'MBNM256' => 4,
                        'MBNM257' => 4,
                        'MBNM258' => 6,
                        'MBNM259' => 4,
                        'MBNM260' => 4,
                        'MBNM261' => 4,
                        'MBNM262' => 4,
                        'MBNM263' => 4,
                        'MBNM264' => 4,
                        'MBNM265' => 4,
                        'MBNM266' => 4,
                        'MBNM267' => 4,
                        'TSNM268' => 1,
                        'TCNM269' => 1,
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
            $this->command->info('✅ Seeded Nails & Makeup successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
