<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PDFVisualComparisonService;
use App\Services\PDFLoggingService;
use App\Services\PDFGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RunPDFVisualTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:visual-tests
                            {--template= : Specific template to test (zb_account_opening, ssb, sme_account_opening, account_holders)}
                            {--dataset= : Type of dataset to use (default, edge_case, variation)}
                            {--threshold= : Difference threshold percentage (0-100)}
                            {--report : Generate HTML report}
                            {--batch : Run tests in batch mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run visual tests for PDF templates with different datasets';

    /**
     * Execute the console command.
     */
    public function handle(PDFLoggingService $pdfLoggingService, PDFVisualComparisonService $comparisonService)
    {
        $template = $this->option('template');
        $dataset = $this->option('dataset') ?? 'default';
        $threshold = $this->option('threshold') ? (float) $this->option('threshold') : null;
        $generateReport = $this->option('report') || config('pdf_visual_testing.reports.generate_html', true);
        $batchMode = $this->option('batch');
        
        // Create PDF generator service
        $pdfGeneratorService = new PDFGeneratorService($pdfLoggingService);
        
        // Set up storage for tests
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('applications');
        
        // Create temp directory for test files
        $tempDir = config('pdf_visual_testing.paths.temp_directory', storage_path('app/temp/pdf-visual-tests'));
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        // Create reports directory if needed
        $reportsDir = config('pdf_visual_testing.paths.reports_directory', storage_path('app/temp/pdf-visual-tests/reports'));
        if ($generateReport && !File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }
        
        // Define templates to test
        $templates = [
            'zb_account_opening' => [
                'name' => 'ZB Account Opening',
                'design' => 'zb_account_opening'
            ],
            'ssb' => [
                'name' => 'SSB Form',
                'design' => 'ssb'
            ],
            'sme_account_opening' => [
                'name' => 'SME Account Opening',
                'design' => 'sme_account_opening'
            ],
            'account_holders' => [
                'name' => 'Account Holders',
                'design' => 'account_holders'
            ]
        ];
        
        // Filter templates if specific one requested
        if ($template && isset($templates[$template])) {
            $templates = [
                $template => $templates[$template]
            ];
        } elseif ($template && !isset($templates[$template])) {
            $this->error("Unknown template: {$template}");
            return 1;
        }
        
        $this->info("Starting PDF visual tests with dataset: {$dataset}");
        
        $results = [];
        $overallSuccess = true;
        
        // Test each template
        foreach ($templates as $templateKey => $templateConfig) {
            $this->info("\nTesting template: {$templateConfig['name']}");
            
            // Get threshold for this template
            if ($threshold === null) {
                $threshold = config("pdf_visual_testing.template_thresholds.{$templateKey}", 
                    config('pdf_visual_testing.default_threshold', 5.0));
            }
            
            $this->info("Using difference threshold: {$threshold}%");
            
            try {
                // Get test data sets for this template
                $dataSets = $this->getDataSets($templateKey, $dataset);
                
                $templateResults = [
                    'name' => $templateConfig['name'],
                    'datasets_tested' => count($dataSets),
                    'datasets_passed' => 0,
                    'dataset_results' => []
                ];
                
                // Test each data set
                foreach ($dataSets as $dataSetKey => $dataSet) {
                    $this->info("  Testing dataset: {$dataSetKey}");
                    
                    // Generate PDF
                    $pdfPath = $pdfGeneratorService->generateApplicationPDF($dataSet['state']);
                    $this->info("  PDF generated: {$pdfPath}");
                    
                    // Compare with design template
                    $comparisonResult = $comparisonService->comparePdfWithDesign(
                        $pdfPath, 
                        $templateConfig['design'], 
                        $threshold
                    );
                    
                    // Generate report if requested
                    $reportPath = null;
                    if ($generateReport) {
                        $reportPath = $comparisonService->generateVisualReport($comparisonResult);
                        $this->info("  Visual report generated: {$reportPath}");
                    }
                    
                    // Store dataset result
                    $templateResults['dataset_results'][$dataSetKey] = [
                        'match' => $comparisonResult['overall_match'],
                        'difference' => $this->calculateAverageDifference($comparisonResult),
                        'report' => $reportPath
                    ];
                    
                    // Update counts
                    if ($comparisonResult['overall_match']) {
                        $templateResults['datasets_passed']++;
                        $this->info("  ✅ Dataset {$dataSetKey} matches design within threshold of {$threshold}%");
                    } else {
                        $this->error("  ❌ Dataset {$dataSetKey} does not match design within threshold of {$threshold}%");
                        $overallSuccess = false;
                        
                        // Show page-by-page results
                        foreach ($comparisonResult['page_results'] as $pageResult) {
                            $status = $pageResult['match'] ? '✅' : '❌';
                            $this->line("    {$status} Page {$pageResult['page']}: Difference {$pageResult['difference']}%");
                        }
                    }
                }
                
                // Store template results
                $results[$templateKey] = $templateResults;
                
                // Output template summary
                $this->info("\n  Template {$templateConfig['name']} summary:");
                $this->info("  Datasets tested: {$templateResults['datasets_tested']}");
                $this->info("  Datasets passed: {$templateResults['datasets_passed']}");
                $this->info("  Success rate: " . round(($templateResults['datasets_passed'] / $templateResults['datasets_tested']) * 100) . "%");
                
            } catch (\Exception $e) {
                $this->error("Error testing template {$templateConfig['name']}: {$e->getMessage()}");
                $overallSuccess = false;
            }
        }
        
        // Output overall result
        $this->newLine();
        if ($overallSuccess) {
            $this->info("✅ All templates and datasets match their designs within threshold");
            return 0;
        } else {
            $this->error("❌ Some templates or datasets do not match their designs within threshold");
            return 1;
        }
    }
    
    /**
     * Get test data sets for a template
     * 
     * @param string $template Template key
     * @param string $datasetType Type of dataset to use
     * @return array Array of test data sets
     */
    private function getDataSets(string $template, string $datasetType): array
    {
        $dataSets = [];
        
        switch ($template) {
            case 'zb_account_opening':
                if ($datasetType === 'default') {
                    $dataSets['standard'] = [
                        'state' => $this->createZBAccountOpeningTestData()
                    ];
                } elseif ($datasetType === 'edge_case') {
                    $dataSets['long_name'] = [
                        'state' => $this->createZBAccountOpeningTestData(
                            'Johnathon-Christopher-Alexander', 
                            'Smith-Johnson-Williams-Brown-Davis-Miller-Wilson'
                        )
                    ];
                    $dataSets['special_chars'] = [
                        'state' => $this->createZBAccountOpeningTestData(
                            'John-Émile', 
                            "O'Connor-Müller"
                        )
                    ];
                } elseif ($datasetType === 'variation') {
                    $dataSets['variation1'] = [
                        'state' => $this->createZBAccountOpeningTestDataWithCustomValues('John', 'Doe', [
                            'title' => 'Dr',
                            'gender' => 'Female',
                            'employmentStatus' => 'Contract',
                            'accountCurrency' => 'ZWL'
                        ])
                    ];
                    $dataSets['variation2'] = [
                        'state' => $this->createZBAccountOpeningTestDataWithCustomValues('Jane', 'Smith', [
                            'title' => 'Mrs',
                            'gender' => 'Female',
                            'employmentStatus' => 'Self-Employed',
                            'accountCurrency' => 'USD'
                        ])
                    ];
                }
                break;
                
            case 'ssb':
                if ($datasetType === 'default') {
                    $dataSets['standard'] = [
                        'state' => $this->createSSBFormTestData()
                    ];
                } elseif ($datasetType === 'edge_case') {
                    $dataSets['long_name'] = [
                        'state' => $this->createSSBFormTestData(
                            'Johnathon-Christopher-Alexander', 
                            'Smith-Johnson-Williams-Brown-Davis-Miller-Wilson'
                        )
                    ];
                    $dataSets['max_values'] = [
                        'state' => $this->createSSBFormTestDataWithCustomValues('John', 'Doe', [
                            'grossMonthlySalary' => '999999999',
                            'loanAmount' => '999999999',
                            'repaymentPeriod' => '999'
                        ])
                    ];
                } elseif ($datasetType === 'variation') {
                    $dataSets['variation1'] = [
                        'state' => $this->createSSBFormTestDataWithCustomValues('Jane', 'Smith', [
                            'department' => 'Health',
                            'loanPurpose' => 'Education',
                            'repaymentPeriod' => '48'
                        ])
                    ];
                    $dataSets['variation2'] = [
                        'state' => $this->createSSBFormTestDataWithCustomValues('Robert', 'Johnson', [
                            'department' => 'Finance',
                            'loanPurpose' => 'Vehicle Purchase',
                            'repaymentPeriod' => '36'
                        ])
                    ];
                }
                break;
                
            case 'sme_account_opening':
                if ($datasetType === 'default') {
                    $dataSets['standard'] = [
                        'state' => $this->createSMEAccountOpeningTestData()
                    ];
                } elseif ($datasetType === 'edge_case') {
                    $dataSets['long_name'] = [
                        'state' => $this->createSMEAccountOpeningTestData(
                            'Extremely Long Business Name That Exceeds Normal Limits For Testing Purposes Only'
                        )
                    ];
                    $dataSets['min_values'] = [
                        'state' => $this->createSMEAccountOpeningTestDataWithCustomValues('Micro Business', [
                            'annualTurnover' => '0',
                            'numberOfEmployees' => '1'
                        ])
                    ];
                } elseif ($datasetType === 'variation') {
                    $dataSets['variation1'] = [
                        'state' => $this->createSMEAccountOpeningTestDataWithCustomValues('Large Enterprise', [
                            'businessType' => 'Public Limited Company',
                            'natureOfBusiness' => 'Manufacturing',
                            'numberOfEmployees' => '500',
                            'accountCurrency' => 'ZWL'
                        ])
                    ];
                    $dataSets['variation2'] = [
                        'state' => $this->createSMEAccountOpeningTestDataWithCustomValues('Small Business', [
                            'businessType' => 'Sole Proprietorship',
                            'natureOfBusiness' => 'Retail',
                            'numberOfEmployees' => '5',
                            'accountCurrency' => 'USD'
                        ])
                    ];
                }
                break;
                
            case 'account_holders':
                if ($datasetType === 'default') {
                    $dataSets['standard'] = [
                        'state' => $this->createAccountHoldersTestData()
                    ];
                } elseif ($datasetType === 'edge_case') {
                    $dataSets['long_name'] = [
                        'state' => $this->createAccountHoldersTestData(
                            'Johnathon-Christopher-Alexander', 
                            'Smith-Johnson-Williams-Brown-Davis-Miller-Wilson'
                        )
                    ];
                    $dataSets['max_values'] = [
                        'state' => $this->createAccountHoldersTestDataWithCustomValues('John', 'Doe', [
                            'grossMonthlySalary' => '999999999',
                            'loanAmount' => '999999999',
                            'repaymentPeriod' => '999'
                        ])
                    ];
                } elseif ($datasetType === 'variation') {
                    $dataSets['variation1'] = [
                        'state' => $this->createAccountHoldersTestDataWithCustomValues('Robert', 'Johnson', [
                            'title' => 'Prof',
                            'occupation' => 'Medical Doctor',
                            'loanPurpose' => 'Business Investment',
                            'repaymentPeriod' => '60'
                        ])
                    ];
                    $dataSets['variation2'] = [
                        'state' => $this->createAccountHoldersTestDataWithCustomValues('Sarah', 'Davis', [
                            'title' => 'Ms',
                            'occupation' => 'Teacher',
                            'loanPurpose' => 'Education',
                            'repaymentPeriod' => '24'
                        ])
                    ];
                }
                break;
        }
        
        return $dataSets;
    }
    
    /**
     * Calculate average difference from comparison results
     * 
     * @param array $results Comparison results
     * @return float Average difference percentage
     */
    private function calculateAverageDifference(array $results): float
    {
        if (empty($results['page_results'])) {
            return 0;
        }
        
        $total = 0;
        foreach ($results['page_results'] as $pageResult) {
            $total += $pageResult['difference'];
        }
        
        return $total / count($results['page_results']);
    }
    
    /**
     * Create test data for ZB account opening
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @return \App\Models\ApplicationState
     */
    private function createZBAccountOpeningTestData(string $firstName = 'John', string $lastName = 'Doe'): \App\Models\ApplicationState
    {
        return new \App\Models\ApplicationState([
            'session_id' => 'test-zb-account-' . uniqid(),
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => false,
                'formId' => 'individual_account_opening.json',
                'formResponses' => [
                    'firstName' => $firstName,
                    'surname' => $lastName,
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => $firstName . '.' . $lastName . '@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'ABC Company',
                    'occupation' => 'Software Developer',
                    'employmentStatus' => 'Permanent',
                    'grossMonthlySalary' => '5000',
                    'accountCurrency' => 'USD',
                    'serviceCenter' => 'Harare Main Branch'
                ]
            ]
        ]);
    }
    
    /**
     * Create test data for ZB account opening with custom values
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @param array $customValues Custom values to override defaults
     * @return \App\Models\ApplicationState
     */
    private function createZBAccountOpeningTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): \App\Models\ApplicationState
    {
        $state = $this->createZBAccountOpeningTestData($firstName, $lastName);
        
        foreach ($customValues as $key => $value) {
            $state->form_data['formResponses'][$key] = $value;
        }
        
        return $state;
    }
    
    /**
     * Create test data for SSB form
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @return \App\Models\ApplicationState
     */
    private function createSSBFormTestData(string $firstName = 'John', string $lastName = 'Doe'): \App\Models\ApplicationState
    {
        return new \App\Models\ApplicationState([
            'session_id' => 'test-ssb-form-' . uniqid(),
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'goz-ssb',
                'hasAccount' => false,
                'formId' => 'ssb_account_opening_form.json',
                'formResponses' => [
                    'firstName' => $firstName,
                    'surname' => $lastName,
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => $firstName . '.' . $lastName . '@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'Government of Zimbabwe',
                    'department' => 'Education',
                    'employeeNumber' => 'EC12345',
                    'grossMonthlySalary' => '3000',
                    'loanAmount' => '10000',
                    'loanPurpose' => 'Home Improvement',
                    'repaymentPeriod' => '24'
                ]
            ]
        ]);
    }
    
    /**
     * Create test data for SSB form with custom values
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @param array $customValues Custom values to override defaults
     * @return \App\Models\ApplicationState
     */
    private function createSSBFormTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): \App\Models\ApplicationState
    {
        $state = $this->createSSBFormTestData($firstName, $lastName);
        
        foreach ($customValues as $key => $value) {
            $state->form_data['formResponses'][$key] = $value;
        }
        
        return $state;
    }
    
    /**
     * Create test data for SME account opening
     * 
     * @param string $businessName Business name for test data
     * @return \App\Models\ApplicationState
     */
    private function createSMEAccountOpeningTestData(string $businessName = 'Test Business'): \App\Models\ApplicationState
    {
        return new \App\Models\ApplicationState([
            'session_id' => 'test-sme-account-' . uniqid(),
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'entrepreneur',
                'hasAccount' => false,
                'formId' => 'smes_business_account_opening.json',
                'formResponses' => [
                    'businessName' => $businessName,
                    'tradingName' => $businessName . ' Trading',
                    'registrationNumber' => 'REG' . rand(10000, 99999),
                    'businessType' => 'Private Limited Company',
                    'dateOfIncorporation' => '2010-01-01',
                    'natureOfBusiness' => 'Technology Services',
                    'physicalAddress' => '456 Business Park, Harare',
                    'postalAddress' => 'P.O. Box 789, Harare',
                    'contactPerson' => 'Jane Manager',
                    'position' => 'General Manager',
                    'telephone' => '0772345678',
                    'email' => 'info@' . strtolower(str_replace(' ', '', $businessName)) . '.com',
                    'annualTurnover' => '500000',
                    'numberOfEmployees' => '15',
                    'accountCurrency' => 'USD'
                ]
            ]
        ]);
    }
    
    /**
     * Create test data for SME account opening with custom values
     * 
     * @param string $businessName Business name for test data
     * @param array $customValues Custom values to override defaults
     * @return \App\Models\ApplicationState
     */
    private function createSMEAccountOpeningTestDataWithCustomValues(string $businessName, array $customValues): \App\Models\ApplicationState
    {
        $state = $this->createSMEAccountOpeningTestData($businessName);
        
        foreach ($customValues as $key => $value) {
            $state->form_data['formResponses'][$key] = $value;
        }
        
        return $state;
    }
    
    /**
     * Create test data for account holders
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @return \App\Models\ApplicationState
     */
    private function createAccountHoldersTestData(string $firstName = 'John', string $lastName = 'Doe'): \App\Models\ApplicationState
    {
        return new \App\Models\ApplicationState([
            'session_id' => 'test-account-holders-' . uniqid(),
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formId' => 'account_holder_loan_application.json',
                'formResponses' => [
                    'firstName' => $firstName,
                    'surname' => $lastName,
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => $firstName . '.' . $lastName . '@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'ABC Company',
                    'occupation' => 'Software Developer',
                    'employmentStatus' => 'Permanent',
                    'grossMonthlySalary' => '5000',
                    'accountNumber' => '4001234567890',
                    'loanAmount' => '15000',
                    'loanPurpose' => 'Vehicle Purchase',
                    'repaymentPeriod' => '36'
                ]
            ]
        ]);
    }
    
    /**
     * Create test data for account holders with custom values
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @param array $customValues Custom values to override defaults
     * @return \App\Models\ApplicationState
     */
    private function createAccountHoldersTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): \App\Models\ApplicationState
    {
        $state = $this->createAccountHoldersTestData($firstName, $lastName);
        
        foreach ($customValues as $key => $value) {
            $state->form_data['formResponses'][$key] = $value;
        }
        
        return $state;
    }
}