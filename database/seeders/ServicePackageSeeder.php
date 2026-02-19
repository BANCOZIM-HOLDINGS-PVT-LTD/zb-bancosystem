<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicePackageSeeder extends Seeder
{
    /**
     * Seed service categories into the microbiz tables with domain='service'.
     * These represent: Home Construction, Drivers License, Zimparks Holiday,
     * School Fees, Mother-to-be Preparation, Nurse Aid, Small Business Support.
     * 
     * NOTE: This seeder does NOT touch the old product_categories / products tables.
     * Those are handled by PersonalProductsSeeder and HirePurchaseSeeder.
     */
    public function run(): void
    {
        // ==========================================
        // 1. HOME CONSTRUCTION
        // ==========================================
        $constructionId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Home Construction',
            'emoji' => '🏗️',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Core House
        $coreHouseId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $constructionId,
            'name' => 'Core House',
            'description' => 'Complete core house construction packages',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coreItems = [
            ['name' => 'Foundation Work', 'unit_cost' => 2000.00, 'unit' => 'job'],
            ['name' => 'Brick Walls', 'unit_cost' => 3000.00, 'unit' => 'job'],
            ['name' => 'Roofing (IBR Sheets)', 'unit_cost' => 2500.00, 'unit' => 'job'],
            ['name' => 'Cement (50kg bags)', 'unit_cost' => 12.00, 'unit' => 'bag'],
            ['name' => 'River Sand', 'unit_cost' => 80.00, 'unit' => 'cube'],
            ['name' => 'Pit Sand', 'unit_cost' => 60.00, 'unit' => 'cube'],
            ['name' => 'Plumbing', 'unit_cost' => 800.00, 'unit' => 'job'],
            ['name' => 'Electrical Wiring', 'unit_cost' => 700.00, 'unit' => 'job'],
            ['name' => 'Paint (interior)', 'unit_cost' => 45.00, 'unit' => 'bucket'],
            ['name' => 'Paint (exterior)', 'unit_cost' => 55.00, 'unit' => 'bucket'],
            ['name' => 'Windows (aluminium)', 'unit_cost' => 120.00, 'unit' => 'piece'],
            ['name' => 'Doors (wooden)', 'unit_cost' => 150.00, 'unit' => 'piece'],
        ];

        foreach ($coreItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $coreHouseId,
                'item_code' => 'SV-CH-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Durawall
        $durawallId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $constructionId,
            'name' => 'Durawall',
            'description' => 'Precast concrete wall systems for boundaries',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $durawallItems = [
            ['name' => 'Durawall Panel (Standard)', 'unit_cost' => 25.00, 'unit' => 'panel'],
            ['name' => 'Durawall Post', 'unit_cost' => 15.00, 'unit' => 'piece'],
            ['name' => 'Installation Labour', 'unit_cost' => 10.00, 'unit' => 'panel'],
        ];

        foreach ($durawallItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $durawallId,
                'item_code' => 'SV-DW-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Gate and Fencing
        $gateFenceId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $constructionId,
            'name' => 'Gate and Fencing',
            'description' => 'Gates, sliding gates, palisade and diamond mesh fencing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gateItems = [
            ['name' => 'Sliding Gate (3m manual)', 'unit_cost' => 1500.00, 'unit' => 'piece'],
            ['name' => 'Sliding Gate (4m motorized)', 'unit_cost' => 3500.00, 'unit' => 'piece'],
            ['name' => 'Swing Gate (3m manual)', 'unit_cost' => 1200.00, 'unit' => 'piece'],
            ['name' => 'Palisade Fencing', 'unit_cost' => 32.00, 'unit' => 'metre'],
            ['name' => 'Diamond Mesh Fencing', 'unit_cost' => 20.00, 'unit' => 'metre'],
        ];

        foreach ($gateItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $gateFenceId,
                'item_code' => 'SV-GF-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 2. DRIVERS LICENSE
        // ==========================================
        $driversId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Drivers License',
            'emoji' => '🚗',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $licenseCoursesId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $driversId,
            'name' => 'License Courses',
            'description' => 'Provisional and full driving license courses',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $licenseItems = [
            ['name' => 'Provisional License (Study Pack & Exam)', 'unit_cost' => 20.00, 'unit' => 'course'],
            ['name' => 'Drivers License Review & Test', 'unit_cost' => 150.00, 'unit' => 'course'],
            ['name' => 'Full Course (Lessons + Test)', 'unit_cost' => 300.00, 'unit' => 'course'],
        ];

        foreach ($licenseItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $licenseCoursesId,
                'item_code' => 'SV-DL-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 3. ZIMPARKS HOLIDAY
        // ==========================================
        $zimparksId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Zimparks Holiday Package',
            'emoji' => '🏕️',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $destinationsId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $zimparksId,
            'name' => 'Destinations',
            'description' => 'Zimparks vacation destinations and holiday packages',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $holidayItems = [
            ['name' => 'Accommodation (per night)', 'unit_cost' => 50.00, 'unit' => 'night'],
            ['name' => 'Park Entry Fees (adult)', 'unit_cost' => 15.00, 'unit' => 'person'],
            ['name' => 'Activity Pass (game drive)', 'unit_cost' => 40.00, 'unit' => 'trip'],
            ['name' => 'Meals Package', 'unit_cost' => 30.00, 'unit' => 'day'],
        ];

        foreach ($holidayItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $destinationsId,
                'item_code' => 'SV-ZP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 4. SCHOOL FEES
        // ==========================================
        $schoolFeesId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'School Fees Assistance',
            'emoji' => '👫📚',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primaryId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $schoolFeesId,
            'name' => 'Primary School',
            'description' => 'Primary school fees support',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('microbiz_items')->insert([
            'microbiz_subcategory_id' => $primaryId,
            'item_code' => 'SV-SF-001',
            'name' => 'Primary School Fees (per term)',
            'unit_cost' => 200.00,
            'unit' => 'term',
            'markup_percentage' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondaryId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $schoolFeesId,
            'name' => 'Secondary School',
            'description' => 'Secondary school fees support',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('microbiz_items')->insert([
            'microbiz_subcategory_id' => $secondaryId,
            'item_code' => 'SV-SF-002',
            'name' => 'Secondary School Fees (per term)',
            'unit_cost' => 300.00,
            'unit' => 'term',
            'markup_percentage' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tertiaryId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $schoolFeesId,
            'name' => 'Tertiary Education',
            'description' => 'Polytechnic and university fees',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tertiaryItems = [
            ['name' => 'Polytech Fees (per semester)', 'unit_cost' => 500.00, 'unit' => 'semester'],
            ['name' => 'University Fees (per semester)', 'unit_cost' => 600.00, 'unit' => 'semester'],
        ];

        foreach ($tertiaryItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $tertiaryId,
                'item_code' => 'SV-SF-' . str_pad($i + 3, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 5. MOTHER-TO-BE PREPARATION
        // ==========================================
        $motherToBeId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Mother-to-be Preparation',
            'emoji' => '🤰',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maternityKitId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $motherToBeId,
            'name' => 'Maternity Kit',
            'description' => 'Essential items for expectant mothers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maternityItems = [
            ['name' => 'Hospital Delivery Kit', 'unit_cost' => 80.00, 'unit' => 'kit'],
            ['name' => 'Baby Starter Pack', 'unit_cost' => 150.00, 'unit' => 'pack'],
            ['name' => 'Maternity Clothing Set', 'unit_cost' => 60.00, 'unit' => 'set'],
            ['name' => 'Antenatal Care Package', 'unit_cost' => 100.00, 'unit' => 'package'],
        ];

        foreach ($maternityItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $maternityKitId,
                'item_code' => 'SV-MT-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 6. NURSE AID COURSE
        // ==========================================
        $nurseAidId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Nurse Aid Course',
            'emoji' => '⚕️',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $certId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $nurseAidId,
            'name' => 'Certification',
            'description' => 'Nurse aid training and certification',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nurseItems = [
            ['name' => 'Registration & Theory', 'unit_cost' => 200.00, 'unit' => 'course'],
            ['name' => 'Practical Training', 'unit_cost' => 100.00, 'unit' => 'course'],
        ];

        foreach ($nurseItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $certId,
                'item_code' => 'SV-NA-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 7. SMALL BUSINESS SUPPORT
        // ==========================================
        $bizSupportId = DB::table('microbiz_categories')->insertGetId([
            'name' => 'Small Business Support',
            'emoji' => '💼',
            'domain' => 'service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bizServicesId = DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $bizSupportId,
            'name' => 'Business Services',
            'description' => 'Consultancy and company registration services',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bizItems = [
            ['name' => 'Business Consultancy Starter Session', 'unit_cost' => 100.00, 'unit' => 'session'],
            ['name' => 'Full Business Plan', 'unit_cost' => 300.00, 'unit' => 'plan'],
            ['name' => 'Company Registration', 'unit_cost' => 130.00, 'unit' => 'registration'],
        ];

        foreach ($bizItems as $i => $item) {
            DB::table('microbiz_items')->insert([
                'microbiz_subcategory_id' => $bizServicesId,
                'item_code' => 'SV-BS-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'unit_cost' => $item['unit_cost'],
                'unit' => $item['unit'],
                'markup_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Service packages seeded: 7 categories with items.');
    }
}
