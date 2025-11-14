<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the default supplier as per note 19
        Supplier::firstOrCreate(
            ['name' => 'Seven Hundred Nine Hundred Pvt Ltd'],
            [
                'supplier_code' => 'SUP-0001',
                'contact_person' => 'Supply Manager',
                'email' => 'supply@799pvt.co.zw',
                'phone' => '+263 77 123 4567',
                'address' => '123 Enterprise Road',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 5.0,
                'payment_terms' => [
                    'net_days' => 30,
                    'discount_percentage' => 2,
                    'discount_days' => 10,
                ],
                'metadata' => [
                    'warranty_provider' => true,
                    'ecommerce_seller' => true,
                    'preferred_supplier' => true,
                ],
            ]
        );
    }
}
