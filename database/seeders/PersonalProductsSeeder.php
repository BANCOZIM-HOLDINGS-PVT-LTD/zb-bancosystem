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
        // 1. School Fees Support
        $schoolFeesCategory = DB::table('product_categories')->insertGetId([
            'name' => 'School Fees Support',
            'emoji' => '👫📚',
            'type' => 'personalServices',
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

        $tertiarySubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $schoolFeesCategory,
            'name' => 'Tertiary Education',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $polytechFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tertiarySubcategory,
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

        $universityFeesId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $tertiarySubcategory,
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

        // 2. Drivers License (Provisional & Drivers Edition)
        $licensingCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Drivers License',
            'emoji' => '🚗',
            'type' => 'personalServices',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driversLicenseSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $licensingCategory,
            'name' => 'License Courses',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Provisional
        $provisionalId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $driversLicenseSubcategory,
            'name' => 'Provisional License',
            'base_price' => 20.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $provisionalId, 'name' => 'Study Pack & Exam', 'multiplier' => 1.00, 'custom_price' => 20.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Drivers Edition (Full License lessons/test)
        $driversEditionId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $driversLicenseSubcategory,
            'name' => 'Drivers License Edition',
            'base_price' => 150.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $driversEditionId, 'name' => 'Review & Test', 'multiplier' => 1.00, 'custom_price' => 150.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $driversEditionId, 'name' => 'Full Course (Lessons + Test)', 'multiplier' => 2.00, 'custom_price' => 300.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Nurse Aid Course
        $nurseAidCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Nurse Aid Course',
            'emoji' => '⚕️',
            'type' => 'personalServices',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nurseAidSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $nurseAidCategory,
            'name' => 'Certification',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nurseAidId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $nurseAidSubcategory,
            'name' => 'Nurse Aid Certification',
            'base_price' => 200.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $nurseAidId, 'name' => 'Registration & Theory', 'multiplier' => 1.00, 'custom_price' => 200.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $nurseAidId, 'name' => 'Full Course (Theory + Practicals)', 'multiplier' => 1.50, 'custom_price' => 300.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Holiday Package
        $holidayPackageCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Holiday Package',
            'emoji' => '🎡',
            'type' => 'personalServices',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vacationPackagesSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $holidayPackageCategory,
            'name' => 'Destinations',
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
            ['product_id' => $vacationId, 'name' => 'Bronze Package', 'multiplier' => 1.00, 'custom_price' => 280.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $vacationId, 'name' => 'Silver Package', 'multiplier' => 1.75, 'custom_price' => 490.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $vacationId, 'name' => 'Gold Package', 'multiplier' => 3.32, 'custom_price' => 930.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 5. Small Business Support
        $businessSupportCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Small Business Support',
            'emoji' => '💼',
            'type' => 'personalServices',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessSupportSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $businessSupportCategory,
            'name' => 'Business Services',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consultancyId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $businessSupportSubcategory,
            'name' => 'Business Consultancy',
            'base_price' => 100.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $consultancyId, 'name' => 'Starter Session', 'multiplier' => 1.00, 'custom_price' => 100.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $consultancyId, 'name' => 'Full Business Plan', 'multiplier' => 3.00, 'custom_price' => 300.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $feesLicensingSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $businessSupportCategory,
            'name' => 'Fees and Licensing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyRegId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $feesLicensingSubcategory,
            'name' => 'Company Registration',
            'base_price' => 130.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $companyRegId, 'name' => 'Standard', 'multiplier' => 1.00, 'custom_price' => 130.00, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // 6. Starlink Internet Kit (Personal Gadget)
        $starlinkCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Starlink Internet',
            'emoji' => '📡',
            'type' => 'personalGadgets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $starlinkSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $starlinkCategory,
            'name' => 'Starlink Kit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $starlinkId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $starlinkSubcategory,
            'name' => 'Starlink Internet Kit',
            'base_price' => 500.00,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $starlinkId, 'name' => 'Residential', 'multiplier' => 1.00, 'custom_price' => 500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $starlinkId, 'name' => 'Roam (Mobile Regional)', 'multiplier' => 1.00, 'custom_price' => 500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $starlinkId, 'name' => 'Mobile Priority', 'multiplier' => 5.69, 'custom_price' => 2846.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 7. Construction Category (Home Construction)
        $constructionCategory = DB::table('product_categories')->insertGetId([
            'name' => 'Construction',
            'emoji' => '🏗️',
            'type' => 'homeConstruction', // Type Update
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Core House Subcategory
        $coreHouseSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $constructionCategory,
            'name' => 'Core House',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Core House Option 1: Basic without finishings
        $basicNoFinishId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $coreHouseSubcategory,
            'name' => 'Basic Core House (No Finishings)',
            'base_price' => 8500.00,
            'description' => 'A compact, affordable starter home with essential structural elements. Includes foundation, walls, roof, doors, and windows. Perfect for those who prefer to customize their own interior finishes over time.',
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $basicNoFinishId, 'name' => 'Standard', 'multiplier' => 1.00, 'custom_price' => 8500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $basicNoFinishId, 'name' => 'With Painting Add-on', 'multiplier' => 1.12, 'custom_price' => 9500.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core House Option 2: Basic core house complete
        $basicCompleteId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $coreHouseSubcategory,
            'name' => 'Basic Core House (Complete)',
            'base_price' => 12000.00,
            'description' => 'A fully finished basic home ready for immediate occupancy. Includes plastered walls, floor screed, ceiling, electrical wiring, plumbing, interior and exterior paint, and all essential fixtures.',
            'image_url' => null,
            'interior_colors' => json_encode(['White', 'Cream', 'Light Grey', 'Peach', 'Sky Blue']),
            'exterior_colors' => json_encode(['White', 'Cream', 'Sand', 'Terracotta', 'Light Grey']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $basicCompleteId, 'name' => 'Standard', 'multiplier' => 1.00, 'custom_price' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core House Option 3: Deluxe complete
        $deluxeCompleteId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $coreHouseSubcategory,
            'name' => 'Deluxe Core House (Complete)',
            'base_price' => 18000.00,
            'description' => 'A premium finished home with upgraded features including quality tiles, modern kitchen fittings, built-in wardrobes, premium paint finishes, and enhanced security features. Move-in ready with a touch of luxury.',
            'image_url' => null,
            'interior_colors' => json_encode(['White', 'Cream', 'Light Grey', 'Peach', 'Sky Blue', 'Sage Green', 'Lavender']),
            'exterior_colors' => json_encode(['White', 'Cream', 'Sand', 'Terracotta', 'Light Grey', 'Charcoal', 'Olive']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $deluxeCompleteId, 'name' => 'Standard', 'multiplier' => 1.00, 'custom_price' => 18000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Core House Option 4: Deluxe non complete
        $deluxeNonCompleteId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $coreHouseSubcategory,
            'name' => 'Deluxe Core House (Non-Complete)',
            'base_price' => 14500.00,
            'description' => 'A deluxe-spec structure with premium materials and design, leaving interior touches for personal customization. Features upgraded roofing, pre-wired electrics, plumbing rough-ins, and quality plastering. Ideal for those wanting luxury foundations with custom finishes.',
            'image_url' => null,
            'interior_colors' => json_encode(['White', 'Cream', 'Light Grey', 'Peach', 'Sky Blue', 'Sage Green', 'Lavender']),
            'exterior_colors' => json_encode(['White', 'Cream', 'Sand', 'Terracotta', 'Light Grey', 'Charcoal', 'Olive']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $deluxeNonCompleteId, 'name' => 'Standard', 'multiplier' => 1.00, 'custom_price' => 14500.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Durawall Subcategory
        $durawallSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $constructionCategory,
            'name' => 'Durawall',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Durawall Products (Placeholders)
        $durawallStandardId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $durawallSubcategory,
            'name' => 'Durawall Standard',
            'base_price' => 2500.00,
            'description' => 'Standard precast concrete wall system for residential boundary walls.',
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $durawallStandardId, 'name' => '50m Length', 'multiplier' => 1.00, 'custom_price' => 2500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $durawallStandardId, 'name' => '100m Length', 'multiplier' => 1.90, 'custom_price' => 4750.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $durawallStandardId, 'name' => '150m Length', 'multiplier' => 2.70, 'custom_price' => 6750.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $durawallPremiumId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $durawallSubcategory,
            'name' => 'Durawall Premium',
            'base_price' => 3500.00,
            'description' => 'Premium precast wall with decorative patterns and enhanced durability.',
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $durawallPremiumId, 'name' => '50m Length', 'multiplier' => 1.00, 'custom_price' => 3500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $durawallPremiumId, 'name' => '100m Length', 'multiplier' => 1.90, 'custom_price' => 6650.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $durawallPremiumId, 'name' => '150m Length', 'multiplier' => 2.70, 'custom_price' => 9450.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Gate and Fencing Subcategory
        $gateFencingSubcategory = DB::table('product_sub_categories')->insertGetId([
            'product_category_id' => $constructionCategory,
            'name' => 'Gate and Fencing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Gate Products (Placeholders)
        $slidingGateId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gateFencingSubcategory,
            'name' => 'Sliding Gate',
            'base_price' => 1500.00,
            'description' => 'Steel sliding gate with motor option for residential or commercial properties.',
            'image_url' => null,
            'colors' => json_encode(['Black', 'Grey', 'Green', 'Brown']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $slidingGateId, 'name' => 'Manual (3m)', 'multiplier' => 1.00, 'custom_price' => 1500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $slidingGateId, 'name' => 'Manual (4m)', 'multiplier' => 1.33, 'custom_price' => 2000.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $slidingGateId, 'name' => 'Motorized (3m)', 'multiplier' => 2.00, 'custom_price' => 3000.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $slidingGateId, 'name' => 'Motorized (4m)', 'multiplier' => 2.33, 'custom_price' => 3500.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $swingGateId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gateFencingSubcategory,
            'name' => 'Swing Gate',
            'base_price' => 1200.00,
            'description' => 'Double-leaf swing gate for driveways and entrances.',
            'image_url' => null,
            'colors' => json_encode(['Black', 'Grey', 'Green', 'Brown']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $swingGateId, 'name' => 'Manual (3m)', 'multiplier' => 1.00, 'custom_price' => 1200.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $swingGateId, 'name' => 'Manual (4m)', 'multiplier' => 1.42, 'custom_price' => 1700.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $swingGateId, 'name' => 'Motorized (3m)', 'multiplier' => 2.08, 'custom_price' => 2500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $swingGateId, 'name' => 'Motorized (4m)', 'multiplier' => 2.50, 'custom_price' => 3000.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $palisadeFencingId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gateFencingSubcategory,
            'name' => 'Palisade Fencing',
            'base_price' => 800.00,
            'description' => 'Steel palisade security fencing for perimeter protection.',
            'image_url' => null,
            'colors' => json_encode(['Black', 'Green', 'Grey']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $palisadeFencingId, 'name' => '25m Length', 'multiplier' => 1.00, 'custom_price' => 800.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $palisadeFencingId, 'name' => '50m Length', 'multiplier' => 1.88, 'custom_price' => 1500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $palisadeFencingId, 'name' => '100m Length', 'multiplier' => 3.50, 'custom_price' => 2800.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $diamondMeshId = DB::table('products')->insertGetId([
            'product_sub_category_id' => $gateFencingSubcategory,
            'name' => 'Diamond Mesh Fencing',
            'base_price' => 500.00,
            'description' => 'Galvanized diamond mesh fencing for residential boundaries.',
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_package_sizes')->insert([
            ['product_id' => $diamondMeshId, 'name' => '25m Length', 'multiplier' => 1.00, 'custom_price' => 500.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $diamondMeshId, 'name' => '50m Length', 'multiplier' => 1.80, 'custom_price' => 900.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $diamondMeshId, 'name' => '100m Length', 'multiplier' => 3.20, 'custom_price' => 1600.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Personal Products (Services & Construction) seeded successfully!');
    }
}

