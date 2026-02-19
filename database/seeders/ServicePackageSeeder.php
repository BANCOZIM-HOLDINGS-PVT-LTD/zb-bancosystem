<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MicrobizCategory;
use App\Models\MicrobizSubcategory;
use App\Models\MicrobizItem; // Assuming model exists or using DB facade

class ServicePackageSeeder extends Seeder
{
    /**
     * Seed service categories into the microbiz tables with domain='service'.
     */
    public function run(): void
    {
        // ==========================================
        // 1. HOME CONSTRUCTION
        // ==========================================
        $constructionId = $this->updateOrCreateCategory('Home Construction', '🏗️', 'service');

        // Core House
        $coreHouseId = $this->updateOrCreateSubcategory($constructionId, 'Core House', 'Complete core house construction packages');
        
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
            $this->updateOrCreateItem($coreHouseId, 'SV-CH-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // Durawall
        $durawallId = $this->updateOrCreateSubcategory($constructionId, 'Durawall', 'Precast concrete wall systems for boundaries');

        $durawallItems = [
            ['name' => 'Durawall Panel (Standard)', 'unit_cost' => 25.00, 'unit' => 'panel'],
            ['name' => 'Durawall Post', 'unit_cost' => 15.00, 'unit' => 'piece'],
            ['name' => 'Installation Labour', 'unit_cost' => 10.00, 'unit' => 'panel'],
        ];

        foreach ($durawallItems as $i => $item) {
            $this->updateOrCreateItem($durawallId, 'SV-DW-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // Gate and Fencing
        $gateFenceId = $this->updateOrCreateSubcategory($constructionId, 'Gate and Fencing', 'Gates, sliding gates, palisade and diamond mesh fencing');

        $gateItems = [
            ['name' => 'Sliding Gate (3m manual)', 'unit_cost' => 1500.00, 'unit' => 'piece'],
            ['name' => 'Sliding Gate (4m motorized)', 'unit_cost' => 3500.00, 'unit' => 'piece'],
            ['name' => 'Swing Gate (3m manual)', 'unit_cost' => 1200.00, 'unit' => 'piece'],
            ['name' => 'Palisade Fencing', 'unit_cost' => 32.00, 'unit' => 'metre'],
            ['name' => 'Diamond Mesh Fencing', 'unit_cost' => 20.00, 'unit' => 'metre'],
        ];

        foreach ($gateItems as $i => $item) {
            $this->updateOrCreateItem($gateFenceId, 'SV-GF-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 2. DRIVERS LICENSE
        // ==========================================
        $driversId = $this->updateOrCreateCategory('Drivers License', '🚗', 'service');
        $licenseCoursesId = $this->updateOrCreateSubcategory($driversId, 'License Courses', 'Provisional and full driving license courses');

        $licenseItems = [
            ['name' => 'Provisional License (Study Pack & Exam)', 'unit_cost' => 20.00, 'unit' => 'course'],
            ['name' => 'Drivers License Review & Test', 'unit_cost' => 150.00, 'unit' => 'course'],
            ['name' => 'Full Course (Lessons + Test)', 'unit_cost' => 300.00, 'unit' => 'course'],
        ];

        foreach ($licenseItems as $i => $item) {
             $this->updateOrCreateItem($licenseCoursesId, 'SV-DL-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 3. ZIMPARKS HOLIDAY
        // ==========================================
        $zimparksId = $this->updateOrCreateCategory('Zimparks Holiday Package', '🏕️', 'service');
        $destinationsId = $this->updateOrCreateSubcategory($zimparksId, 'Destinations', 'Zimparks vacation destinations and holiday packages');

        $holidayItems = [
            ['name' => 'Accommodation (per night)', 'unit_cost' => 50.00, 'unit' => 'night'],
            ['name' => 'Park Entry Fees (adult)', 'unit_cost' => 15.00, 'unit' => 'person'],
            ['name' => 'Activity Pass (game drive)', 'unit_cost' => 40.00, 'unit' => 'trip'],
            ['name' => 'Meals Package', 'unit_cost' => 30.00, 'unit' => 'day'],
        ];

        foreach ($holidayItems as $i => $item) {
             $this->updateOrCreateItem($destinationsId, 'SV-ZP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 4. SCHOOL FEES
        // ==========================================
        $schoolFeesId = $this->updateOrCreateCategory('School Fees Assistance', '👫📚', 'service');

        $primaryId = $this->updateOrCreateSubcategory($schoolFeesId, 'Primary School', 'Primary school fees support');
        $this->updateOrCreateItem($primaryId, 'SV-SF-001', ['name' => 'Primary School Fees (per term)', 'unit_cost' => 200.00, 'unit' => 'term']);

        $secondaryId = $this->updateOrCreateSubcategory($schoolFeesId, 'Secondary School', 'Secondary school fees support');
        $this->updateOrCreateItem($secondaryId, 'SV-SF-002', ['name' => 'Secondary School Fees (per term)', 'unit_cost' => 300.00, 'unit' => 'term']);

        $tertiaryId = $this->updateOrCreateSubcategory($schoolFeesId, 'Tertiary Education', 'Polytechnic and university fees');
        $tertiaryItems = [
            ['name' => 'Polytech Fees (per semester)', 'unit_cost' => 500.00, 'unit' => 'semester'],
            ['name' => 'University Fees (per semester)', 'unit_cost' => 600.00, 'unit' => 'semester'],
        ];

        foreach ($tertiaryItems as $i => $item) {
             $this->updateOrCreateItem($tertiaryId, 'SV-SF-' . str_pad($i + 3, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 5. MOTHER-TO-BE PREPARATION
        // ==========================================
        $motherToBeId = $this->updateOrCreateCategory('Mother-to-be Preparation', '🤰', 'service');
        $maternityKitId = $this->updateOrCreateSubcategory($motherToBeId, 'Maternity Kit', 'Essential items for expectant mothers');

        $maternityItems = [
            ['name' => 'Hospital Delivery Kit', 'unit_cost' => 80.00, 'unit' => 'kit'],
            ['name' => 'Baby Starter Pack', 'unit_cost' => 150.00, 'unit' => 'pack'],
            ['name' => 'Maternity Clothing Set', 'unit_cost' => 60.00, 'unit' => 'set'],
            ['name' => 'Antenatal Care Package', 'unit_cost' => 100.00, 'unit' => 'package'],
        ];

        foreach ($maternityItems as $i => $item) {
             $this->updateOrCreateItem($maternityKitId, 'SV-MT-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 6. NURSE AID COURSE
        // ==========================================
        $nurseAidId = $this->updateOrCreateCategory('Nurse Aid Course', '⚕️', 'service');
        $certId = $this->updateOrCreateSubcategory($nurseAidId, 'Certification', 'Nurse aid training and certification');

        $nurseItems = [
            ['name' => 'Registration & Theory', 'unit_cost' => 200.00, 'unit' => 'course'],
            ['name' => 'Practical Training', 'unit_cost' => 100.00, 'unit' => 'course'],
        ];

        foreach ($nurseItems as $i => $item) {
             $this->updateOrCreateItem($certId, 'SV-NA-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        // ==========================================
        // 7. SMALL BUSINESS SUPPORT
        // ==========================================
        $bizSupportId = $this->updateOrCreateCategory('Small Business Support', '💼', 'service');
        $bizServicesId = $this->updateOrCreateSubcategory($bizSupportId, 'Business Services', 'Consultancy and company registration services');

        $bizItems = [
            ['name' => 'Business Consultancy Starter Session', 'unit_cost' => 100.00, 'unit' => 'session'],
            ['name' => 'Full Business Plan', 'unit_cost' => 300.00, 'unit' => 'plan'],
            ['name' => 'Company Registration', 'unit_cost' => 130.00, 'unit' => 'registration'],
        ];

        foreach ($bizItems as $i => $item) {
             $this->updateOrCreateItem($bizServicesId, 'SV-BS-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $item);
        }

        $this->command->info('Service packages seeded: 7 categories with items (updated).');
    }

    private function updateOrCreateCategory($name, $emoji, $domain)
    {
        $id = DB::table('microbiz_categories')
            ->where('name', $name)
            ->where('domain', $domain)
            ->value('id');

        if ($id) {
            DB::table('microbiz_categories')
                ->where('id', $id)
                ->update(['emoji' => $emoji, 'updated_at' => now()]);
            return $id;
        }

        return DB::table('microbiz_categories')->insertGetId([
            'name' => $name,
            'emoji' => $emoji,
            'domain' => $domain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateOrCreateSubcategory($categoryId, $name, $description)
    {
        $id = DB::table('microbiz_subcategories')
            ->where('microbiz_category_id', $categoryId)
            ->where('name', $name)
            ->value('id');

        if ($id) {
            DB::table('microbiz_subcategories')
                ->where('id', $id)
                ->update(['description' => $description, 'updated_at' => now()]);
            return $id;
        }

        return DB::table('microbiz_subcategories')->insertGetId([
            'microbiz_category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateOrCreateItem($subcategoryId, $code, $data)
    {
        DB::table('microbiz_items')->updateOrInsert(
            ['microbiz_subcategory_id' => $subcategoryId, 'item_code' => $code],
            [
                'name' => $data['name'],
                'unit_cost' => $data['unit_cost'],
                'unit' => $data['unit'],
                'markup_percentage' => 0,
                'created_at' => now(), // only used on insert, but updateOrInsert handles duplicate keys
                'updated_at' => now(),
            ]
        );
    }
}
