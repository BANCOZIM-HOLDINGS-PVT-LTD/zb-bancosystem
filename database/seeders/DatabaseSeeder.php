<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create a deterministic test user but avoid duplicate key errors when
        // running the seeder multiple times.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                // Use a known password so tests that rely on this user can authenticate.
                'password' => bcrypt('password'),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );

        // Seed the product catalog, users, and agents
        $this->call([
            AdminUserSeeder::class, // Create admin user first (admin@bancosystem.fly.dev)
            PortalUsersSeeder::class, // Create portal users (zbadmin, accounts, stores, hr, partners)
            
            SupplierSeeder::class, // Suppliers for products

            ProductCatalogSeeder::class, // Categories + General Microbiz Products
            ServicePackageSeeder::class, // Service domain products
            
            MicrobizBusinessSeeder::class, // Detailed Microbiz businesses
            MicrobizChickenProjectSeeder::class, // Broiler Production specific
            MicrobizLayersProjectSeeder::class, // Layers Production specific

            // New detailed item-level seeders (from Excel)
            MicrobizIncubatorsSeeder::class, // Agricultural Machinery > Incubators
            MicrobizPASystemsSeeder::class, // Events Management > PA Systems
            MicrobizBarEquipmentSeeder::class, // Entertainment > Bar Entertainment
            MicrobizVenueSetupSeeder::class, // Events Management > Venue Setup
            MicrobizSewingMachinesSeeder::class, // Tailoring Machinery > Sewing Machines
            MicrobizDriversLicenseSeeder::class, // Personal Development > Drivers Licence
            MicrobizCarWashSeeder::class, // Cleaning Services > Car Wash
            MicrobizPersonalDevSeeder::class, // Personal Development > Vocational Courses
            MicrobizSaloonEquipmentSeeder::class, // Beauty > Saloon Equipment
            MicrobizBarberSeeder::class, // Beauty > Barber & Rasta
            MicrobizBraidingWeavingSeeder::class, // Beauty > Braiding & Weaving
            MicrobizNailsMakeupSeeder::class, // Beauty > Nails & Makeup
            MicrobizPhotographySeeder::class, // Digital Multimedia > Photography

            HirePurchaseSeeder::class, // Main Hire Purchase products (Goods with Series)
            PersonalProductsSeeder::class, // Other Personal products (Services like Holiday, School Fees)
            
            AgentSeeder::class,
        ]);
    }
}
