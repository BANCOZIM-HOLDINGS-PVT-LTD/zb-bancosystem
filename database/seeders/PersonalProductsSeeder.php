<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PersonalProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * DEPRECATED: usage for Personal Services and Construction.
     * 
     * Services (School Fees, Drivers License, etc.) and Construction are now handled 
     * by ServicePackageSeeder which populates the 'microbiz_' tables with domain='service'.
     * 
     * Personal Gadgets (Starlink) have been moved to HirePurchaseSeeder under ICT Accessories.
     */
    public function run(): void
    {
        $this->command->info('PersonalProductsSeeder is deprecated. Services are in ServicePackageSeeder, Gadgets in HirePurchaseSeeder.');
    }
}
