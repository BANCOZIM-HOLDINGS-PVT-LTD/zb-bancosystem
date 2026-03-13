<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem;
use App\Models\MicrobizPackage;
use Illuminate\Support\Facades\DB;

class MicrobizPersonalDevSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $category = MicrobizCategory::firstOrCreate(
                ['name' => 'Personal Development'],
                ['emoji' => '📚', 'domain' => 'microbiz']
            );

            $subcategory = MicrobizSubcategory::firstOrCreate(
                ['microbiz_category_id' => $category->id, 'name' => 'Vocational Courses'],
                ['image_url' => 'microbiz/vocational_courses.jpg']
            );

            // Items from Excel: PERSONAL DEVELOPMENT - Vocational Courses
            $itemsData = [
                // Delight courses
                ['code' => 'MPDT128', 'name' => '1 Month (2hr) Mon-Fri Beauty & Cosmetics', 'unit_cost' => 70.00],
                ['code' => 'MPDT129', 'name' => '1 Month (2hr) Mon-Fri Sewing', 'unit_cost' => 70.00],
                ['code' => 'MPDT130', 'name' => '1 Month (2hr) Mon-Fri Baking', 'unit_cost' => 70.00],
                ['code' => 'MPDT131', 'name' => '1 Month Weekend (6hr) Sat Beauty/Sewing/Baking', 'unit_cost' => 70.00],
                ['code' => 'MPDT132', 'name' => '1 Month Evening (2hr) Mon-Fri Beauty/Sewing/Baking', 'unit_cost' => 70.00],
                ['code' => 'MPDT133', 'name' => '3 Month Lessons', 'unit_cost' => 250.00],
                ['code' => 'MPDT134', 'name' => '6 Month Lessons', 'unit_cost' => 300.00],
                // Ixar Academy
                ['code' => 'MPDT135', 'name' => '1 Month Lessons Barbering Professional', 'unit_cost' => 120.00],
                ['code' => 'MPDT136', 'name' => '1 Month Cellphones & Tablet Repairs (Beginners)', 'unit_cost' => 100.00],
                ['code' => 'MPDT137', 'name' => '1 Month Computer Repairs (Beginners)', 'unit_cost' => 100.00],
                ['code' => 'MPDT138', 'name' => '1 Month Car Key Programming & Locksmith', 'unit_cost' => 100.00],
                ['code' => 'MPDT139', 'name' => '1 Month CCTV, Access Point & Alarm Systems', 'unit_cost' => 100.00],
                ['code' => 'MPDT140', 'name' => '1 Month Solar Installation', 'unit_cost' => 100.00],
                // Graduation
                ['code' => 'MPDT141', 'name' => 'Graduation Fees Delight', 'unit_cost' => 50.00],
                ['code' => 'MPDT142', 'name' => 'Graduation Fees Ixar', 'unit_cost' => 50.00],
                // Starter Kits
                ['code' => 'MPDT143', 'name' => 'Mini Starter Kit', 'unit_cost' => 80.00],
                ['code' => 'MPDT144', 'name' => 'Full Starter Kit', 'unit_cost' => 350.00],
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
            $this->command->info('Personal Development items created/updated.');

            // Packages as per Excel (Lite, Standard, Full House)
            $packages = [
                'lite' => [
                    'name' => 'Lite Package',
                    'price' => 260.00,
                    'items' => [
                        'MPDT128' => 1, // 1 Month Beauty
                        'MPDT141' => 1, // Graduation Delight
                        'MPDT143' => 1, // Mini Starter Kit
                    ],
                ],
                'standard' => [
                    'name' => 'Standard Package',
                    'price' => 455.00,
                    'items' => [
                        'MPDT133' => 1, // 3 Month Lessons
                        'MPDT141' => 1, // Graduation Delight
                        'MPDT143' => 1, // Mini Starter Kit
                    ],
                ],
                'full_house' => [
                    'name' => 'Full House Package',
                    'price' => 864.00,
                    'items' => [
                        'MPDT133' => 1, // 3 Month Lessons
                        'MPDT141' => 1, // Graduation Delight
                        'MPDT143' => 1, // Mini Starter Kit
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
            $this->command->info('✅ Seeded Vocational Courses successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
