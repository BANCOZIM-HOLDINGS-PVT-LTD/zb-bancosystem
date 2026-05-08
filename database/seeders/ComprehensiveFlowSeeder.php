<?php

namespace Database\Seeders;

use App\Models\ApplicationState;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComprehensiveFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->createMicroBizApplication();
        $this->createSmeBizApplication();
        $this->createPersonalServicesApplication();
        $this->createConstructionApplication();
        $this->createPersonalGadgetsApplication();
        $this->createRDCLoanApplication();
    }

    private function createRDCLoanApplication()
    {
        ApplicationState::create([
            'session_id' => 'rdc_' . Str::random(10),
            'user_identifier' => '63-0000000-X-06',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'rdcLoan',
                'category' => 'Electronics',
                'productName' => 'Solar Kit',
                'productCode' => 'HP-SOL-PANEL-123',
                'finalPrice' => 800,
                'monthlyInstallment' => 80,
                'creditDuration' => 10,
                'employer' => 'rural-district-council',
                'formResponses' => [
                    'firstName' => 'Blessing',
                    'lastName' => 'Ndlovu',
                    'nationalIdNumber' => '63-0000000-X-06',
                    'phoneNumber' => '+263776666666',
                ],
            ],
        ]);
        $this->command->info('✓ Created RDCLoan Application');
    }

    private function createMicroBizApplication()
    {
        ApplicationState::create([
            'session_id' => 'microbiz_' . Str::random(10),
            'user_identifier' => '63-0000000-X-01',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'microBiz',
                'category' => 'Chicken Projects',
                'productName' => 'Broiler Starter Pack',
                'productCode' => 'MB-CHICK-001',
                'finalPrice' => 500,
                'monthlyInstallment' => 50,
                'creditDuration' => 12,
                'employer' => 'entrepreneur',
                'formResponses' => [
                    'firstName' => 'Tinashe',
                    'lastName' => 'Makoni',
                    'nationalIdNumber' => '63-0000000-X-01',
                    'phoneNumber' => '+263771111111',
                ],
            ],
        ]);
        $this->command->info('✓ Created MicroBiz Application');
    }

    private function createSmeBizApplication()
    {
        ApplicationState::create([
            'session_id' => 'smebiz_' . Str::random(10),
            'user_identifier' => '63-0000000-X-02',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'smeBiz',
                'category' => 'Retail Shops',
                'productName' => 'SME Booster Growth Tier',
                'productCode' => 'SME-BOOSTER',
                'finalPrice' => 5000,
                'monthlyInstallment' => 450,
                'creditDuration' => 24,
                'employer' => 'sme-business',
                'formResponses' => [
                    'firstName' => 'Memory',
                    'lastName' => 'Sibanda',
                    'nationalIdNumber' => '63-0000000-X-02',
                    'phoneNumber' => '+263772222222',
                ],
            ],
        ]);
        $this->command->info('✓ Created SME Biz Application');
    }

    private function createPersonalServicesApplication()
    {
        ApplicationState::create([
            'session_id' => 'pers_serv_' . Str::random(10),
            'user_identifier' => '63-0000000-X-03',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'personalServices',
                'category' => 'Personal Development',
                'productName' => 'Class 4 Drivers License',
                'productCode' => 'SERV-DRIVE-04',
                'finalPrice' => 250,
                'monthlyInstallment' => 30,
                'creditDuration' => 10,
                'employer' => 'government-ssb',
                'formResponses' => [
                    'firstName' => 'Kudakwashe',
                    'lastName' => 'Moyo',
                    'nationalIdNumber' => '63-0000000-X-03',
                    'phoneNumber' => '+263773333333',
                ],
            ],
        ]);
        $this->command->info('✓ Created Personal Services Application');
    }

    private function createConstructionApplication()
    {
        ApplicationState::create([
            'session_id' => 'const_' . Str::random(10),
            'user_identifier' => '63-0000000-X-04',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'construction',
                'category' => 'Building materials',
                'productName' => 'Cement & Bricks Combo',
                'productCode' => 'BUILD-001',
                'finalPrice' => 1500,
                'monthlyInstallment' => 150,
                'creditDuration' => 12,
                'employer' => 'private-sector',
                'formResponses' => [
                    'firstName' => 'Farai',
                    'lastName' => 'Dube',
                    'nationalIdNumber' => '63-0000000-X-04',
                    'phoneNumber' => '+263774444444',
                ],
            ],
        ]);
        $this->command->info('✓ Created Construction Application');
    }

    private function createPersonalGadgetsApplication()
    {
        ApplicationState::create([
            'session_id' => 'gadget_' . Str::random(10),
            'user_identifier' => '63-0000000-X-05',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'intent' => 'personalGadgets',
                'category' => 'Electronics',
                'productName' => 'Starlink Standard Kit',
                'productCode' => 'ICT-STARLINK',
                'finalPrice' => 600,
                'monthlyInstallment' => 60,
                'creditDuration' => 12,
                'employer' => 'private-sector',
                'formResponses' => [
                    'firstName' => 'Nyasha',
                    'lastName' => 'Zhou',
                    'nationalIdNumber' => '63-0000000-X-05',
                    'phoneNumber' => '+263775555555',
                ],
            ],
        ]);
        $this->command->info('✓ Created Personal Gadgets Application');
    }
}
