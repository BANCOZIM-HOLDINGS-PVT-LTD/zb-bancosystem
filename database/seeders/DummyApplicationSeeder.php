<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApplicationState;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DummyApplicationSeeder extends Seeder
{
    public function run()
    {
        // Ensure we have a placeholder image
        $placeholderPath = 'documents/placeholder.jpg';

        // 1. SSB Loan Application
        ApplicationState::create([
            'session_id' => Str::uuid(),
            'channel' => 'web',
            'user_identifier' => 'test_ssb_user',
            'current_step' => 'completed', // Shows in 'Pending' or 'Processing' usually
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),

            'form_data' => [
                'formType' => 'ssb',
                'formResponses' => [
                    'firstName' => 'John',
                    'surname' => 'Doe',
                    'dateOfBirth' => '1985-05-15',
                    'gender' => 'Male',
                    'nationalIdNumber' => '63-1234567 A 12',
                    'mobile' => '0771234567',
                    'emailAddress' => 'john.doe@example.com',
                    'employmentNumber' => '1234567A',
                    'ministry' => 'Primary and Secondary Education',
                    'netSalary' => '300+',
                    'responsiblePaymaster' => 'Jane Smith',
                    'responsibleMinistry' => 'Primary and Secondary Education',
                    'loanAmount' => '1500.00',
                    'loanTenure' => '12',
                    'residentialAddress' => '123 Samora Machel Ave, Harare',
                    'propertyOwnership' => 'Rented',
                    'periodAtAddress' => 'Between 2-5 years',
                    'hasOtherLoans' => 'no',
                    'spouseDetails' => [
                        [
                            'fullName' => 'Mary Doe',
                            'relationship' => 'Spouse',
                            'phoneNumber' => '0777654321',
                            'residentialAddress' => '123 Samora Machel Ave, Harare'
                        ],
                        [
                            'fullName' => 'Peter Doe',
                            'relationship' => 'Brother',
                            'phoneNumber' => '0771122334',
                            'residentialAddress' => '456 Borrowdale Rd, Harare'
                        ]
                    ]
                ],
                'documents' => [
                    'uploadedDocuments' => [
                        'national_id' => [$placeholderPath],
                        'payslip' => [$placeholderPath],
                        'employment_letter' => [$placeholderPath]
                    ],
                    'uploadedAt' => Carbon::now()->toIso8601String(),
                    'validationSummary' => [
                        'allDocumentsValid' => true
                    ]
                ]
            ]
        ]);

        // 2. SME Business Loan
        ApplicationState::create([
            'session_id' => Str::uuid(),
            'channel' => 'web',
            'user_identifier' => 'test_sme_user',
            'current_step' => 'completed',
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
            'form_data' => [
                'formType' => 'sme_business',
                'formResponses' => [
                    'firstName' => 'Sarah',
                    'surname' => 'Connor',
                    'businessName' => 'Skynet Solutions',
                    'tradingName' => 'Skynet',
                    'businessRegistrationNumber' => '199/2024',
                    'businessType' => 'Company',
                    'businessAddress' => '55 Cyberdyne Systems Way, Harare',
                    'businessPhone' => '0779876543',
                    'businessEmail' => 'sarah@skynet.com',
                    'yearsInBusiness' => '5',
                    'estimatedAnnualSales' => '50000',
                    'netProfit' => '1001+',
                    'loanAmount' => '5000.00',
                    'loanTenure' => '24',
                    'directorsPersonalDetails' => [
                        'firstName' => 'Sarah',
                        'surname' => 'Connor',
                        'idNumber' => '63-9876543 X 88',
                        'cellNumber' => '0779876543',
                        'residentialAddress' => 'Hidden Bunker, Matopos'
                    ]
                ],
                'documents' => [
                    'uploadedDocuments' => [
                        'national_id' => [$placeholderPath],
                        'business_registration' => [$placeholderPath],
                        'financial_statements' => [$placeholderPath],
                        'director_id' => [$placeholderPath]
                    ],
                    'uploadedAt' => Carbon::now()->toIso8601String(),
                    'validationSummary' => [
                        'allDocumentsValid' => true
                    ]
                ]
            ]
        ]);

        // 3. ZB Account Holder Loan
        ApplicationState::create([
            'session_id' => Str::uuid(),
            'channel' => 'web',
            'user_identifier' => 'test_account_holder',
            'current_step' => 'completed',
            'created_at' => Carbon::now()->subHours(5),
            'updated_at' => Carbon::now()->subHours(5),
            'form_data' => [
                'formType' => 'account_holder',
                'formResponses' => [
                    'firstName' => 'James',
                    'surname' => 'Bond',
                    'dateOfBirth' => '1980-01-01',
                    'gender' => 'Male',
                    'nationalIdNumber' => '00-007007 B 07',
                    'mobile' => '0777007007',
                    'emailAddress' => '007@mi6.gov.uk',
                    'accountNumber' => '415123456789',
                    'employerName' => 'Universal Exports',
                    'jobTitle' => 'Agent',
                    'netSalary' => '300+',
                    'loanAmount' => '2000.00',
                    'loanTenure' => '18',
                    'residentialAddress' => 'Shurugwi Heights, Shurugwi',
                    'spouseDetails' => [
                        [
                            'fullName' => 'Tracy Bond',
                            'relationship' => 'Spouse',
                            'phoneNumber' => '0771111111',
                            'residentialAddress' => 'Same'
                        ],
                        [
                            'fullName' => 'M',
                            'relationship' => 'Employer',
                            'phoneNumber' => '0770000000',
                            'residentialAddress' => 'London'
                        ]
                    ]
                ],
                'documents' => [
                    'uploadedDocuments' => [
                        'national_id' => [$placeholderPath],
                        'payslip' => [$placeholderPath],
                        'proof_of_residence' => [$placeholderPath]
                    ],
                    'uploadedAt' => Carbon::now()->toIso8601String(),
                    'validationSummary' => [
                        'allDocumentsValid' => true
                    ]
                ]
            ]
        ]);
    }
}
