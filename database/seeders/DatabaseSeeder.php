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

        // Seed the product catalog and agents
        $this->call([
            AdminUserSeeder::class, // Create admin user first
            ProductCatalogSeeder::class,
            HirePurchaseSeeder::class, // Main Hire Purchase products (Goods with Series)
            PersonalProductsSeeder::class, // Other Personal products (Services like Holiday, School Fees)
            AgentSeeder::class,
        ]);
    }
}
