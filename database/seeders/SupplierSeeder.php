<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds all key suppliers for the BancoZim system.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Seven Hundred Nine Hundred Pvt Ltd',
                'contact_person' => 'Supply Manager',
                'email' => 'supply@799pvt.co.zw',
                'phone' => '+263 77 123 4567',
                'address' => '123 Enterprise Road',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 5.0,
                'payment_terms' => ['net_days' => 30, 'discount_percentage' => 2, 'discount_days' => 10],
                'metadata' => ['warranty_provider' => true, 'ecommerce_seller' => true, 'preferred_supplier' => true],
            ],
            [
                'name' => 'Gain Hardware',
                'contact_person' => 'Sales Dept',
                'email' => 'sales@gainhardware.co.zw',
                'phone' => '+263 77 200 0001',
                'address' => 'Gain Hardware Building',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 4.5,
            ],
            [
                'name' => 'PG',
                'contact_person' => 'Procurement',
                'email' => 'info@pg.co.zw',
                'phone' => '+263 77 200 0002',
                'address' => 'PG Head Office',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 4.5,
            ],
            [
                'name' => 'Farm & City',
                'contact_person' => 'Sales',
                'email' => 'info@farmandcity.co.zw',
                'phone' => '+263 77 200 0003',
                'address' => 'Farm & City Centre',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 4.0,
            ],
            [
                'name' => 'Easy Go',
                'contact_person' => 'Manager',
                'email' => 'info@easygo.co.zw',
                'phone' => '+263 77 200 0004',
                'address' => 'Easy Go Offices',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 4.0,
            ],
            [
                'name' => 'Zimparks',
                'contact_person' => 'Bookings Office',
                'email' => 'bookings@zimparks.gov.zw',
                'phone' => '+263 24 270 6077',
                'address' => 'Zimparks Head Office, Botanical Gardens',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 4.5,
            ],
            [
                'name' => 'ZB Bank',
                'contact_person' => 'Corporate Division',
                'email' => 'corporate@zb.co.zw',
                'phone' => '+263 24 275 8081',
                'address' => 'ZB House, 46 Speke Avenue',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 5.0,
            ],
            [
                'name' => 'Harare City Council',
                'contact_person' => 'Licensing Office',
                'email' => 'info@hre.council.gov.zw',
                'phone' => '+263 24 275 3811',
                'address' => 'Town House, Julius Nyerere Way',
                'city' => 'Harare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 3.5,
                'metadata' => ['licensing_authority' => true],
            ],
            [
                'name' => 'Bulawayo City Council',
                'contact_person' => 'Licensing Office',
                'email' => 'info@byo.council.gov.zw',
                'phone' => '+263 29 260 402',
                'address' => 'City Hall, Fife Street',
                'city' => 'Bulawayo',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 3.5,
                'metadata' => ['licensing_authority' => true],
            ],
            [
                'name' => 'Mutare City Council',
                'contact_person' => 'Licensing Office',
                'email' => 'info@mutare.council.gov.zw',
                'phone' => '+263 20 263 131',
                'address' => 'Civic Centre, Aerodrome Road',
                'city' => 'Mutare',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 3.5,
                'metadata' => ['licensing_authority' => true],
            ],
            [
                'name' => 'Gweru City Council',
                'contact_person' => 'Licensing Office',
                'email' => 'info@gweru.council.gov.zw',
                'phone' => '+263 54 222 181',
                'address' => 'Municipal Offices, Main Street',
                'city' => 'Gweru',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 3.5,
                'metadata' => ['licensing_authority' => true],
            ],
            [
                'name' => 'Masvingo City Council',
                'contact_person' => 'Licensing Office',
                'email' => 'info@masvingo.council.gov.zw',
                'phone' => '+263 39 262 044',
                'address' => 'Municipal Offices, Robert Mugabe Street',
                'city' => 'Masvingo',
                'country' => 'Zimbabwe',
                'status' => 'active',
                'rating' => 3.5,
                'metadata' => ['licensing_authority' => true],
            ],
        ];

        foreach ($suppliers as $supplierData) {
            $code = Supplier::generateSupplierCode();
            Supplier::firstOrCreate(
                ['name' => $supplierData['name']],
                array_merge($supplierData, ['supplier_code' => $code])
            );
        }

        $this->command->info('Suppliers seeded: ' . count($suppliers) . ' suppliers.');
    }
}