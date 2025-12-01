<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalProductsSeeder extends Seeder
{
    /**
     * Run the database seeds for Personal Products (Hire Purchase)
     * Only Services: School Fees, Drivers License, Holiday Package
     */
    public function run(): void
    {
        // 15. School fees
        $schoolFeesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'School fees',
            'emoji' => '👫📚',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primarySchoolSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Primary School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primaryFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $primarySchoolSubcategory,
            'name' => 'Primary School Fees',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $primaryFeesId, 'name' => '1 Term', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $primaryFeesId, 'name' => '2 Terms', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $primaryFeesId, 'name' => '3 Terms', 'multiplier' => 3.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $secondarySchoolSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Secondary School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondaryFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $secondarySchoolSubcategory,
            'name' => 'Secondary School Fees',
            'base_price' => 300.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $secondaryFeesId, 'name' => '1 Term', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $secondaryFeesId, 'name' => '2 Terms', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $secondaryFeesId, 'name' => '3 Terms', 'multiplier' => 3.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $polytechSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Polytech',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $polytechFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $polytechSubcategory,
            'name' => 'Polytech Fees',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $polytechFeesId, 'name' => '1 Semester', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $polytechFeesId, 'name' => '2 Semesters', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $universitySubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'University',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $universityFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $universitySubcategory,
            'name' => 'University Fees',
            'base_price' => 600.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $universityFeesId, 'name' => '1 Semester', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $universityFeesId, 'name' => '2 Semesters', 'multiplier' => 2.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 17. Drivers License
        $licensingCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Drivers License',
            'emoji' => '🚗',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driversLicenseSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $licensingCategory,
            'name' => 'Drivers License',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driversLicenseId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $driversLicenseSubcategory,
            'name' => 'Drivers License Application',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $driversLicenseId, 'name' => '1 Application', 'multiplier' => 1.00, 'custom_price' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 18. Holiday Package
        $holidayPackageCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Holiday Package',
            'emoji' => '🎡',
            'type' => 'hire_purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacationPackagesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $holidayPackageCategory,
            'name' => 'Zimparks Lodges/Cottages',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacationId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $vacationPackagesSubcategory,
            'name' => 'Zimparks Vacation Package',
            'base_price' => 280.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $vacationId, 'name' => 'Lite', 'multiplier' => 1.00, 'custom_price' => 280.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $vacationId, 'name' => 'Standard', 'multiplier' => 1.75, 'custom_price' => 490.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $vacationId, 'name' => 'Full house', 'multiplier' => 3.32, 'custom_price' => 930.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Personal Products (Services) seeded successfully!');
    }
}