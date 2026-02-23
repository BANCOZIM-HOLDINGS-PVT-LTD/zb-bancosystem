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
                'branches' => [
                    ['name' => 'Harare Head Office', 'address' => '123 Enterprise Road', 'city' => 'Harare', 'phone' => '+263 77 123 4567'],
                ],
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
                'branches' => [
                    ['name' => 'Harare - Graniteside', 'address' => 'Graniteside Industrial Area', 'city' => 'Harare', 'phone' => '+263 77 200 0001'],
                    ['name' => 'Harare - Msasa', 'address' => 'Msasa, Harare', 'city' => 'Harare', 'phone' => '+263 77 200 0010'],
                    ['name' => 'Bulawayo', 'address' => 'Gain Hardware Bulawayo', 'city' => 'Bulawayo', 'phone' => '+263 77 200 0011'],
                    ['name' => 'Gweru', 'address' => 'Gain Hardware Gweru', 'city' => 'Gweru', 'phone' => '+263 77 200 0012'],
                    ['name' => 'Mutare', 'address' => 'Gain Hardware Mutare', 'city' => 'Mutare', 'phone' => '+263 77 200 0013'],
                    ['name' => 'Masvingo', 'address' => 'Gain Hardware Masvingo', 'city' => 'Masvingo', 'phone' => '+263 77 200 0014'],
                    ['name' => 'Kwekwe', 'address' => 'Gain Hardware Kwekwe', 'city' => 'Kwekwe', 'phone' => '+263 77 200 0015'],
                    ['name' => 'Chinhoyi', 'address' => 'Gain Hardware Chinhoyi', 'city' => 'Chinhoyi', 'phone' => '+263 77 200 0016'],
                    ['name' => 'Marondera', 'address' => 'Gain Hardware Marondera', 'city' => 'Marondera', 'phone' => '+263 77 200 0017'],
                ],
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
                'branches' => [
                    ['name' => 'PG Harare', 'address' => 'PG Head Office, Harare', 'city' => 'Harare', 'phone' => '+263 77 200 0002'],
                    ['name' => 'PG Bulawayo', 'address' => 'PG Bulawayo Branch', 'city' => 'Bulawayo', 'phone' => '+263 77 200 0020'],
                    ['name' => 'PG Mutare', 'address' => 'PG Mutare Branch', 'city' => 'Mutare', 'phone' => '+263 77 200 0021'],
                    ['name' => 'PG Gweru', 'address' => 'PG Gweru Branch', 'city' => 'Gweru', 'phone' => '+263 77 200 0022'],
                    ['name' => 'PG Masvingo', 'address' => 'PG Masvingo Branch', 'city' => 'Masvingo', 'phone' => '+263 77 200 0023'],
                ],
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
                'branches' => [
                    ['name' => 'Farm & City Harare', 'address' => 'Farm & City Centre, Harare', 'city' => 'Harare', 'phone' => '+263 77 200 0003'],
                    ['name' => 'Farm & City Bulawayo', 'address' => 'Farm & City Bulawayo', 'city' => 'Bulawayo', 'phone' => '+263 77 200 0030'],
                    ['name' => 'Farm & City Gweru', 'address' => 'Farm & City Gweru', 'city' => 'Gweru', 'phone' => '+263 77 200 0031'],
                    ['name' => 'Farm & City Mutare', 'address' => 'Farm & City Mutare', 'city' => 'Mutare', 'phone' => '+263 77 200 0032'],
                    ['name' => 'Farm & City Chinhoyi', 'address' => 'Farm & City Chinhoyi', 'city' => 'Chinhoyi', 'phone' => '+263 77 200 0033'],
                    ['name' => 'Farm & City Masvingo', 'address' => 'Farm & City Masvingo', 'city' => 'Masvingo', 'phone' => '+263 77 200 0034'],
                    ['name' => 'Farm & City Kwekwe', 'address' => 'Farm & City Kwekwe', 'city' => 'Kwekwe', 'phone' => '+263 77 200 0035'],
                    ['name' => 'Farm & City Marondera', 'address' => 'Farm & City Marondera', 'city' => 'Marondera', 'phone' => '+263 77 200 0036'],
                    ['name' => 'Farm & City Bindura', 'address' => 'Farm & City Bindura', 'city' => 'Bindura', 'phone' => '+263 77 200 0037'],
                    ['name' => 'Farm & City Chiredzi', 'address' => 'Farm & City Chiredzi', 'city' => 'Chiredzi', 'phone' => '+263 77 200 0038'],
                ],
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
                'branches' => [
                    ['name' => 'Harare CBD', 'address' => 'Easy Go Harare CBD', 'city' => 'Harare', 'phone' => '+263 77 200 0004'],
                    ['name' => 'Bulawayo', 'address' => 'Easy Go Bulawayo', 'city' => 'Bulawayo', 'phone' => '+263 77 200 0040'],
                    ['name' => 'Mutare', 'address' => 'Easy Go Mutare', 'city' => 'Mutare', 'phone' => '+263 77 200 0041'],
                    ['name' => 'Gweru', 'address' => 'Easy Go Gweru', 'city' => 'Gweru', 'phone' => '+263 77 200 0042'],
                    ['name' => 'Bindura', 'address' => 'Easy Go Bindura', 'city' => 'Bindura', 'phone' => '+263 77 200 0043'],
                    ['name' => 'Beitbridge', 'address' => 'Easy Go Beitbridge', 'city' => 'Beitbridge', 'phone' => '+263 77 200 0044'],
                    ['name' => 'Chitungwiza', 'address' => 'Easy Go Chitungwiza', 'city' => 'Chitungwiza', 'phone' => '+263 77 200 0045'],
                    ['name' => 'Marondera', 'address' => 'Easy Go Marondera', 'city' => 'Marondera', 'phone' => '+263 77 200 0046'],
                    ['name' => 'Masvingo', 'address' => 'Easy Go Masvingo', 'city' => 'Masvingo', 'phone' => '+263 77 200 0047'],
                    ['name' => 'Chinhoyi', 'address' => 'Easy Go Chinhoyi', 'city' => 'Chinhoyi', 'phone' => '+263 77 200 0048'],
                    ['name' => 'Victoria Falls', 'address' => 'Easy Go Victoria Falls', 'city' => 'Victoria Falls', 'phone' => '+263 77 200 0049'],
                    ['name' => 'Gwanda', 'address' => 'Easy Go Gwanda', 'city' => 'Gwanda', 'phone' => '+263 77 200 0050'],
                ],
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
                'branches' => [
                    ['name' => 'Zimparks Head Office', 'address' => 'Botanical Gardens, Harare', 'city' => 'Harare', 'phone' => '+263 24 270 6077'],
                ],
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
                'branches' => [
                    ['name' => 'ZB Bank Head Office', 'address' => 'ZB House, 46 Speke Avenue', 'city' => 'Harare', 'phone' => '+263 24 275 8081'],
                ],
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
                'branches' => null,
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
                'branches' => null,
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
                'branches' => null,
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
                'branches' => null,
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
                'branches' => null,
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