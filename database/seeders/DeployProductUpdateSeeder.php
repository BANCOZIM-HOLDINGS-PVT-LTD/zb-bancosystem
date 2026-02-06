<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeployProductUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Safely updates product catalog by truncating and re-seeding.
     */
    public function run(): void
    {
        $this->command->warn('This will TRUNCATE all product-related tables. Ensure you have backups if needed.');

        // Disable foreign key checks to allow truncation
        Schema::disableForeignKeyConstraints();

        $tables = [
            'product_package_sizes',
            'products',
            'product_series',
            'product_sub_categories',
            'product_categories',
            'repayment_terms'
        ];

        foreach ($tables as $table) {
            $this->command->info("Truncating table: {$table}");
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        $this->command->info('Tables truncated. Starting re-seed...');

        $this->call([
            AdminUserSeeder::class,
            PortalUsersSeeder::class,
            ProductCatalogSeeder::class,
            HirePurchaseSeeder::class,
            PersonalProductsSeeder::class,
        ]);

        $this->command->info('Database update completed successfully.');
    }
}

