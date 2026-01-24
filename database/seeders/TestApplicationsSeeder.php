<?php

namespace Database\Seeders;

use App\Models\AccountOpening;
use App\Models\ApplicationState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Account Opening Application
        $accountOpening = AccountOpening::updateOrCreate(
            ['reference_code' => '63-1234567-A-89'],
            [
                'user_identifier' => '63-1234567-A-89',
                'form_data' => [
                'formResponses' => [
                    'title' => 'Mr',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'surname' => 'Doe',
                    'dateOfBirth' => '1990-05-15',
                    'nationalIdNumber' => '63-1234567-A-89',
                    'phoneNumber' => '+263771234567',
                    'mobile' => '771234567',
                    'emailAddress' => 'john.doe@example.com',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'ABC Company Ltd',
                    'occupation' => 'Software Developer',
                    'grossMonthlySalary' => '5000',
                    'maritalStatus' => 'Single',
                    'gender' => 'Male',
                    'nationality' => 'Zimbabwean',
                ],
                'accountType' => 'ZB Bank Account',
                'currency' => 'USD',
            ],
            'status' => 'pending',
            'loan_eligible' => false,
            'selected_product' => [
                'product_name' => 'Laptop',
                'product_code' => 'TECH-001',
                'category' => 'Electronics',
            ],
        ]);

        $this->command->info('✓ Created Account Opening: ' . $accountOpening->reference_code);

        // 2. Create SSB Loan Application
        $ssbApplication = ApplicationState::updateOrCreate(
            ['reference_code' => '639876543B21'],
            [
            'session_id' => 'ssb_' . Str::uuid(),
            'user_identifier' => '63-9876543-B-21',
            'channel' => 'web',
            'current_step' => 'approved',
            'form_data' => [
                'employer' => 'government-ssb',
                'intent' => 'purchase',
                'category' => 'Electronics',
                'productName' => 'Samsung Galaxy S24',
                'productCode' => 'PHONE-024',
                'finalPrice' => 1200,
                'monthlyInstallment' => 100,
                'creditDuration' => 12,
                'formResponses' => [
                    'title' => 'Mrs',
                    'firstName' => 'Sarah',
                    'lastName' => 'Moyo',
                    'ecNumber' => 'EC123456',
                    'nationalIdNumber' => '63-9876543-B-21',
                    'phoneNumber' => '+263772345678',
                    'emailAddress' => 'sarah.moyo@ssb.gov.zw',
                    'residentialAddress' => '456 Government Avenue, Bulawayo',
                    'employerName' => 'State Service Board',
                    'occupation' => 'Civil Servant',
                    'ministry' => 'Ministry of Finance',
                    'employeeNumber' => 'SSB-2024-001',
                    'netSalary' => 3500,
                    'nextOfKinName' => 'Peter Moyo',
                    'nextOfKinPhone' => '+263773456789',
                ],
                'selectedBusiness' => [
                    'name' => 'Electronics',
                    'category' => 'Technology',
                ],
            ],
            'metadata' => [
                'workflow_type' => 'ssb',
                'ssb_status' => 'pending_verification',
            ],
        ]);

        $this->command->info('✓ Created SSB Loan Application: ' . $ssbApplication->reference_code);

        // 3. Create ZB Account Holder Loan Application
        $zbApplication = ApplicationState::updateOrCreate(
            ['reference_code' => '635555555C33'],
            [
            'session_id' => 'zb_' . Str::uuid(),
            'user_identifier' => '63-5555555-C-33',
            'channel' => 'web',
            'current_step' => 'in_review',
            'form_data' => [
                'hasAccount' => true,
                'zbAccountNumber' => '4001-234-56789',
                'intent' => 'purchase',
                'category' => 'Home Appliances',
                'productName' => 'LG Refrigerator 500L',
                'productCode' => 'APPL-500',
                'finalPrice' => 2500,
                'monthlyInstallment' => 250,
                'creditDuration' => 10,
                'formResponses' => [
                    'title' => 'Mr',
                    'firstName' => 'Tendai',
                    'lastName' => 'Ncube',
                    'nationalIdNumber' => '63-5555555-C-33',
                    'phoneNumber' => '+263774567890',
                    'emailAddress' => 'tendai.ncube@gmail.com',
                    'residentialAddress' => '789 Park Lane, Mutare',
                    'employerName' => 'Delta Corporation',
                    'occupation' => 'Accountant',
                    'grossMonthlySalary' => 4500,
                    'nextOfKinName' => 'Grace Ncube',
                    'nextOfKinPhone' => '+263775678901',
                ],
                'selectedBusiness' => [
                    'name' => 'Home Appliances',
                    'category' => 'Household',
                ],
            ],
            'metadata' => [
                'workflow_type' => 'zb_account_holder',
                'zb_status' => 'credit_check_pending',
            ],
        ]);

        $this->command->info('✓ Created ZB Account Holder Loan: ' . $zbApplication->reference_code);

        // 4. Create an additional approved SSB application for CSV export testing
        $approvedSsb = ApplicationState::updateOrCreate(
            ['reference_code' => '631111111D44'],
            [
            'session_id' => 'ssb_approved_' . Str::uuid(),
            'user_identifier' => '63-1111111-D-44',
            'channel' => 'web',
            'current_step' => 'approved',
            'form_data' => [
                'employer' => 'government-ssb',
                'intent' => 'purchase',
                'category' => 'Furniture',
                'productName' => 'Office Desk Set',
                'productCode' => 'FURN-100',
                'finalPrice' => 800,
                'monthlyInstallment' => 80,
                'creditDuration' => 10,
                'formResponses' => [
                    'firstName' => 'Michael',
                    'lastName' => 'Chikwanha',
                    'ecNumber' => 'EC789012',
                    'nationalIdNumber' => '63-1111111-D-44',
                    'phoneNumber' => '+263776789012',
                    'residentialAddress' => '321 Independence Ave, Harare',
                    'nextOfKinName' => 'Mary Chikwanha',
                ],
            ],
        ]);

        $this->command->info('✓ Created Approved SSB Loan (for CSV export): ' . $approvedSsb->reference_code);

        // 5. Create an approved ZB application for CSV export testing
        $approvedZb = ApplicationState::updateOrCreate(
            ['reference_code' => '632222222E55'],
            [
            'session_id' => 'zb_approved_' . Str::uuid(),
            'user_identifier' => '63-2222222-E-55',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'hasAccount' => true,
                'zbAccountNumber' => '4001-567-89012',
                'intent' => 'purchase',
                'category' => 'Electronics',
                'productName' => 'HP Laptop i7',
                'productCode' => 'TECH-200',
                'finalPrice' => 1800,
                'monthlyInstallment' => 180,
                'creditDuration' => 10,
                'formResponses' => [
                    'firstName' => 'Patricia',
                    'lastName' => 'Mutasa',
                    'nationalIdNumber' => '63-2222222-E-55',
                    'phoneNumber' => '+263777890123',
                    'residentialAddress' => '654 Victoria Falls Road, Gweru',
                    'nextOfKinName' => 'James Mutasa',
                ],
            ],
        ]);

        $this->command->info('✓ Created Approved ZB Loan (for CSV export): ' . $approvedZb->reference_code);

        $this->command->info('');
        $this->command->info('=== Test Data Summary ===');
        $this->command->info('Account Openings: 1 (pending)');
        $this->command->info('SSB Loans: 2 (1 approved, 1 pending)');
        $this->command->info('ZB Loans: 2 (1 completed, 1 in review)');
        $this->command->info('');
        $this->command->info('You can now test:');
        $this->command->info('- Account Opening approval workflow in ZB Admin');
        $this->command->info('- SSB loan status updates in ZB Admin');
        $this->command->info('- ZB loan credit checks in ZB Admin');
        $this->command->info('- CSV exports for approved loans in both Admin and ZB Admin panels');
    }
}
