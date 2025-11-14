<?php

namespace App\Console\Commands;

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use Illuminate\Console\Command;

class TestCompletePDFData extends Command
{
    protected $signature = 'test:complete-pdf';

    protected $description = 'Test PDF generation with complete form data';

    public function handle()
    {
        $formType = $this->choice(
            'Which form type would you like to test?',
            ['ssb', 'zb-account', 'account-holders', 'sme-business', 'sme-account', 'all'],
            'all'
        );

        if ($formType === 'all') {
            $this->testAllFormTypes();
        } else {
            $this->testSingleFormType($formType);
        }

        return Command::SUCCESS;
    }

    private function testAllFormTypes()
    {
        $formTypes = ['ssb', 'zb-account', 'account-holders', 'sme-business', 'sme-account'];

        foreach ($formTypes as $type) {
            $this->info("\n=== Testing {$type} form ===");
            $this->testSingleFormType($type);
        }
    }

    private function testSingleFormType(string $formType)
    {
        $completeFormData = $this->getCompleteFormData($formType);

        // Create test application state
        $testApp = ApplicationState::create([
            'session_id' => 'test_'.$formType.'_'.uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test_user',
            'current_step' => 'completed',
            'form_data' => $completeFormData,
            'metadata' => [
                'form_type' => $formType,
                'test_mode' => true,
            ],
        ]);

        $this->info("Created test application with ID: {$testApp->id}");
        $this->info("Session ID: {$testApp->session_id}");

        // Generate PDF
        try {
            $pdfGenerator = app(PDFGeneratorService::class);
            $result = $pdfGenerator->generatePDF($testApp, [
                'formType' => $formType,
                'admin' => true,
                'skipValidation' => true,
            ]);

            $this->info('PDF generated successfully!');
            $this->info("Path: {$result['path']}");
            $this->info("Size: {$result['size_human']}");

            $this->info("\nYou can view the application at:");
            $this->info("http://localhost:8000/admin/p-d-f-managements/{$testApp->id}");

            $this->info("\nDownload the PDF at:");
            $this->info("http://localhost:8000/admin/pdf/download/{$testApp->session_id}");

        } catch (\Exception $e) {
            $this->error("Error generating PDF: {$e->getMessage()}");
            $this->error("Stack trace:\n{$e->getTraceAsString()}");
        }
    }

    private function getCompleteFormData(string $formType): array
    {
        switch ($formType) {
            case 'ssb':
                return $this->getSSBFormData();
            case 'zb-account':
                return $this->getZBAccountFormData();
            case 'account-holders':
                return $this->getAccountHoldersFormData();
            case 'sme-business':
                return $this->getSMEBusinessFormData();
            case 'sme-account':
                return $this->getSMEAccountFormData();
            default:
                return $this->getSSBFormData();
        }
    }

    private function getSSBFormData(): array
    {
        return [
            'language' => 'en',
            'intent' => 'hirePurchase',
            'employer' => 'goz-ssb',
            'amount' => 5000,
            'business' => 'Electronics Store',
            'scale' => 'Medium Scale',
            'formResponses' => [
                // Personal Information
                'title' => 'Mr',
                'surname' => 'Doe',
                'firstName' => 'John',
                'middleName' => 'Michael',
                'dateOfBirth' => '1985-06-15',
                'gender' => 'Male',
                'maritalStatus' => 'Married',
                'nationality' => 'Zimbabwean',
                'nationalID' => '44-123456-Z77',
                'nationalIdNumber' => '44-123456-Z77',

                // Contact Information
                'mobile' => '0772123456',
                'cellNumber' => '0772123456',
                'whatsApp' => '0772123456',
                'emailAddress' => 'john.doe@example.com',

                // Address Information
                'residentialAddress' => '123 Main Street, Harare',
                'permanentAddress' => '123 Main Street, Harare',
                'city' => 'Harare',
                'province' => 'Harare',
                'propertyOwnership' => 'Owned',
                'periodAtAddress' => 'More than 5 years',

                // Employment Information
                'responsibleMinistry' => 'Education',
                'department' => 'Secondary Education',
                'employerName' => 'Ministry of Education',
                'employerAddress' => 'Government Complex, Harare',
                'employmentStatus' => 'Permanent',
                'jobTitle' => 'Senior Teacher',
                'dateOfEmployment' => '2010-03-01',
                'employeeNumber' => 'EDU2010/1234',
                'employmentNumber' => 'EDU2010/1234',
                'paypoint' => 'Harare District',
                'headOfInstitution' => 'Mrs Jane Smith',
                'headOfInstitutionCell' => '0773456789',
                'currentNetSalary' => '850',

                // Spouse/Next of Kin Details
                'spouseDetails' => [
                    [
                        'fullName' => 'Mary Doe',
                        'relationship' => 'Wife',
                        'phoneNumber' => '0774567890',
                        'residentialAddress' => '123 Main Street, Harare',
                    ],
                    [
                        'fullName' => 'Peter Doe',
                        'relationship' => 'Brother',
                        'phoneNumber' => '0775678901',
                        'residentialAddress' => '456 Second Street, Bulawayo',
                    ],
                    [
                        'fullName' => 'Sarah Johnson',
                        'relationship' => 'Mother',
                        'phoneNumber' => '0776789012',
                        'residentialAddress' => '789 Third Avenue, Mutare',
                    ],
                ],

                // Banking Details
                'bankName' => 'CBZ Bank',
                'branch' => 'First Street Branch',
                'accountNumber' => '1234567890',

                // Other Loans
                'otherLoans' => [
                    [
                        'institution' => 'CABS',
                        'monthlyInstallment' => '150',
                        'currentBalance' => '2000',
                        'maturityDate' => '2025-12-31',
                    ],
                    [
                        'institution' => 'Steward Bank',
                        'monthlyInstallment' => '100',
                        'currentBalance' => '1200',
                        'maturityDate' => '2025-06-30',
                    ],
                ],

                // Loan Details
                'loanAmount' => '5000',
                'loanTenure' => '12',
                'loanTerm' => '12 months',
                'monthlyPayment' => '450.42',
                'interestRate' => '10.0',
                'creditFacilityType' => 'Hire Purchase Credit - Electronics Store',
                'purposeOfLoan' => 'Purchase of electronics for resale',
                'purposeAsset' => 'Electronics Store - Medium Scale',

                // Admin/Delivery Information
                'deliveryStatus' => 'Future',
                'agent' => 'Agent001',
                'team' => 'Harare Team',

                // Declaration
                'checkLetter' => 'A',
            ],
        ];

        // Create test application state
        $testApp = ApplicationState::create([
            'session_id' => 'test_complete_'.uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test_user',
            'current_step' => 'completed',
            'form_data' => $completeFormData,
            'metadata' => [
                'form_type' => 'ssb',
                'test_mode' => true,
            ],
        ]);

        $this->info("Created test application with ID: {$testApp->id}");
        $this->info("Session ID: {$testApp->session_id}");

        // Generate PDF
        try {
            $pdfGenerator = app(PDFGeneratorService::class);
            $result = $pdfGenerator->generatePDF($testApp, [
                'formType' => 'ssb',
                'admin' => true,
                'skipValidation' => true,
            ]);

            $this->info('PDF generated successfully!');
            $this->info("Path: {$result['path']}");
            $this->info("Size: {$result['size_human']}");

            $this->info("\nYou can view the application at:");
            $this->info("http://localhost:8000/admin/p-d-f-managements/{$testApp->id}");

            $this->info("\nDownload the PDF at:");
            $this->info("http://localhost:8000/admin/pdf/download/{$testApp->session_id}");

        } catch (\Exception $e) {
            $this->error("Error generating PDF: {$e->getMessage()}");
            $this->error("Stack trace:\n{$e->getTraceAsString()}");
        }

    }

    private function getZBAccountFormData(): array
    {
        return [
            'language' => 'en',
            'intent' => 'zbAccount',
            'employer' => 'private',
            'hasAccount' => false,
            'business' => 'ZB Account Opening',
            'formResponses' => [
                // Account Information
                'serviceCenter' => 'Harare Branch',
                'accountCurrency' => 'USD',
                'accountType' => 'Savings Account',
                'initialDeposit' => '100',

                // Personal Information
                'title' => 'Mr',
                'firstName' => 'Michael',
                'surname' => 'Johnson',
                'maidenName' => '',
                'otherNames' => 'Robert',
                'dateOfBirth' => '1988-03-22',
                'placeOfBirth' => 'Harare',
                'nationality' => 'Zimbabwean',
                'maritalStatus' => 'Married',
                'citizenship' => 'Zimbabwean',
                'dependents' => '2',
                'nationalIdNumber' => '44-567890-A88',
                'driversLicense' => 'DL123456',
                'passportNumber' => 'ZW1234567',
                'passportExpiry' => '2030-03-22',
                'countryOfResidence' => 'Zimbabwe',
                'highestEducation' => 'University Degree',
                'hobbies' => 'Reading, Sports',

                // Contact Information
                'residentialAddress' => '456 Second Street, Harare',
                'telephoneRes' => '024-123456',
                'mobile' => '0773456789',
                'bus' => '',
                'emailAddress' => 'michael.johnson@example.com',

                // Employment
                'employerName' => 'ABC Corporation',
                'occupation' => 'Software Engineer',
                'businessDescription' => 'IT Services',
                'employerType' => ['localCompany' => true],
                'employerAddress' => '789 Corporate Drive, Harare',
                'employerContact' => '024-789012',
                'grossMonthlySalary' => '1200',
                'otherIncome' => '200',

                // Spouse Information
                'spouseDetails' => [
                    [
                        'fullName' => 'Sarah Johnson',
                        'relationship' => 'Wife',
                        'phoneNumber' => '0774567890',
                        'residentialAddress' => '456 Second Street, Harare',
                        'emailAddress' => 'sarah.johnson@example.com',
                    ],
                ],

                // Services
                'smsNumber' => '0773456789',
                'eStatementsEmail' => 'michael.johnson@example.com',
                'mobileMoneyNumber' => '0773456789',
                'eWalletNumber' => '0773456789',
            ],
        ];
    }

    private function getAccountHoldersFormData(): array
    {
        return [
            'language' => 'en',
            'intent' => 'hirePurchase',
            'employer' => 'private',
            'hasAccount' => true,
            'amount' => 3000,
            'business' => 'Home Electronics',
            'formResponses' => [
                // Header information
                'deliveryStatus' => 'Future',
                'agent' => 'Agent002',
                'province' => 'Bulawayo',
                'team' => 'Bulawayo Team',

                // Personal Information
                'title' => 'Mrs',
                'surname' => 'Williams',
                'firstName' => 'Patricia',
                'gender' => 'Female',
                'dateOfBirth' => '1982-07-15',
                'maritalStatus' => 'Married',
                'nationality' => 'Zimbabwean',
                'nationalIdNumber' => '44-789012-B82',
                'mobile' => '0775678901',
                'whatsApp' => '0775678901',
                'emailAddress' => 'patricia.williams@example.com',

                // Address and Property
                'residentialAddress' => '123 High Street, Bulawayo',
                'propertyOwnership' => 'Owned',
                'periodAtAddress' => 'More than 5 years',

                // Employment
                'responsiblePaymaster' => 'Church',
                'employerName' => 'St. Mary\'s Church',
                'employerAddress' => '456 Church Road, Bulawayo',
                'employmentStatus' => 'Permanent',
                'jobTitle' => 'Secretary',
                'dateOfEmployment' => '2015-01-01',
                'headOfInstitution' => 'Rev. James Smith',
                'headOfInstitutionCell' => '0776789012',
                'employmentNumber' => 'CHU2015/789',
                'currentNetSalary' => '650',

                // Loan details
                'loanTenure' => '12',
                'monthlyPayment' => '275.50',

                // Next of Kin
                'nextOfKin' => [
                    ['fullName' => 'David Williams', 'relationship' => 'Husband', 'phoneNumber' => '0777890123', 'residentialAddress' => '123 High Street, Bulawayo'],
                    ['fullName' => 'Mary Williams', 'relationship' => 'Mother', 'phoneNumber' => '0778901234', 'residentialAddress' => '789 Elder Street, Bulawayo'],
                ],

                // Banking
                'bankName' => 'ZB Bank',
                'branch' => 'Bulawayo Main Branch',
                'accountNumber' => '9876543210',

                // Other Loans
                'otherLoans' => [
                    ['institution' => 'Building Society', 'repayment' => '120'],
                ],
            ],
        ];
    }

    private function getSMEBusinessFormData(): array
    {
        return [
            'language' => 'en',
            'intent' => 'microBiz',
            'employer' => 'self-employed',
            'amount' => 8000,
            'business' => 'Retail Store',
            'formResponses' => [
                // Header
                'deliveryStatus' => 'Future',
                'agent' => 'Agent003',
                'province' => 'Mutare',
                'team' => 'Mutare Team',

                // Business Information
                'businessName' => 'ABC Trading Store',
                'businessRegistration' => 'REG123456',
                'businessType' => 'Private Limited Company',
                'dateEstablished' => '2020-06-01',
                'businessAddress' => '123 Commerce Street, Mutare',
                'businessCity' => 'Mutare',
                'businessProvince' => 'Manicaland',
                'postalAddress' => 'P.O. Box 456, Mutare',
                'businessTelephone' => '020-123456',
                'businessMobile' => '0778901234',
                'businessEmail' => 'info@abctrading.co.zw',
                'industrySector' => 'Retail',
                'numberOfEmployees' => '5',
                'monthlyTurnover' => '15000',

                // Owner Information
                'title' => 'Mr',
                'surname' => 'Mukamuri',
                'firstName' => 'Tendai',
                'gender' => 'Male',
                'dateOfBirth' => '1975-11-08',
                'nationality' => 'Zimbabwean',
                'nationalIdNumber' => '44-234567-C75',
                'positionInBusiness' => 'Owner/Managing Director',
                'mobile' => '0778901234',
                'whatsApp' => '0778901234',
                'emailAddress' => 'tendai.mukamuri@gmail.com',
                'residentialAddress' => '789 Residential Drive, Mutare',

                // Financial Information
                'monthlyRevenue' => '12000',
                'annualRevenue' => '144000',
                'otherMonthlyIncome' => '800',
                'otherAnnualIncome' => '9600',
                'totalMonthlyIncome' => '12800',
                'totalAnnualIncome' => '153600',

                // Account Information
                'accountType' => 'Business Current',
                'initialDeposit' => '500',
                'depositMethod' => 'Cash',
                'servicesRequired' => ['Internet Banking', 'Mobile Banking'],

                // Banking Details
                'bankName' => 'CBZ Bank',
                'branch' => 'Mutare Branch',
                'accountNumber' => '5432109876',
            ],
        ];
    }

    private function getSMEAccountFormData(): array
    {
        return [
            'language' => 'en',
            'intent' => 'microBizLoan',
            'employer' => 'entrepreneur',
            'amount' => 12000,
            'business' => 'Manufacturing',
            'formResponses' => [
                // Business Type
                'businessType' => 'Company',
                'loanType' => 'Working Capital Loan',

                // Business Details
                'registeredName' => 'XYZ Manufacturing (Pvt) Ltd',
                'tradingName' => 'XYZ Manufacturing',
                'typeOfBusiness' => 'Manufacturing',
                'businessAddress' => '456 Industrial Road, Gweru',
                'periodAtLocation' => '3 years',
                'initialCapital' => '25000',
                'incorporationDate' => '2021-03-15',
                'incorporationNumber' => 'INC987654',
                'contactPhone' => '054-123456',
                'businessEmail' => 'info@xyzmfg.co.zw',

                // Employee type
                'employeeType' => 'Fulltime and Owner',

                // Customer location
                'customerLocation' => 'This Town',

                // Capital Sources
                'capitalSources' => [
                    'ownSavings' => true,
                    'familyGift' => false,
                    'loan' => true,
                    'otherSpecify' => 'Business Partner Investment',
                ],

                // Customer Base
                'customerBase' => [
                    'individuals' => true,
                ],

                // Personal Information (Director/Owner)
                'title' => 'Ms',
                'surname' => 'Chikwanha',
                'firstName' => 'Grace',
                'gender' => 'Female',
                'dateOfBirth' => '1980-09-12',
                'nationality' => 'Zimbabwean',
                'nationalIdNumber' => '44-345678-D80',
                'mobile' => '0779012345',
                'emailAddress' => 'grace.chikwanha@xyzmfg.co.zw',
                'residentialAddress' => '321 Residential Avenue, Gweru',

                // Property ownership
                'propertyOwnership' => 'Owned',
            ],
        ];
    }
}
