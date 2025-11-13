<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDFLoggingService;
use App\Services\PDFVisualComparisonService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Mockery;

class PDFVisualComparisonTest extends TestCase
{
    protected $pdfLoggingService;
    protected $pdfGeneratorService;
    protected $pdfVisualComparisonService;
    protected $tempDir;
    protected $threshold = 5.0; // Default threshold for visual comparison (5%)
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temp directory for image comparisons
        $this->tempDir = storage_path('app/temp/pdf-visual-tests');
        if (!File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
        
        // Mock the PDFLoggingService
        $this->pdfLoggingService = Mockery::mock(PDFLoggingService::class);
        $this->pdfLoggingService->shouldReceive('logInfo')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logDebug')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logError')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logPerformance')->withAnyArgs()->andReturnNull();
        
        // Create the services
        $this->pdfGeneratorService = new PDFGeneratorService($this->pdfLoggingService);
        $this->pdfVisualComparisonService = new PDFVisualComparisonService();
        
        // Set up storage for tests
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('applications');
    }
    
    protected function tearDown(): void
    {
        // Clean up temp directory
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test that the ZB account opening PDF visually matches the design template
     */
    public function test_zb_account_opening_pdf_visual_comparison(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Create application state with test data
        $applicationState = $this->createZBAccountOpeningTestData();
        
        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);
        
        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath, 
            'zb_account_opening', 
            $this->threshold
        );
        
        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
        
        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'], 
            "ZB account opening PDF does not match design template. See report: {$reportPath}"
        );
        
        // Log the results
        $this->pdfLoggingService->logInfo('Visual comparison completed for ZB account opening', [
            'template' => 'zb_account_opening',
            'match' => $results['overall_match'],
            'report' => $reportPath
        ]);
    }
    
    /**
     * Test that the SSB form PDF visually matches the design template
     */
    public function test_ssb_form_pdf_visual_comparison(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Create application state with test data
        $applicationState = $this->createSSBFormTestData();
        
        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);
        
        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath, 
            'ssb', 
            $this->threshold
        );
        
        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
        
        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'], 
            "SSB form PDF does not match design template. See report: {$reportPath}"
        );
        
        // Log the results
        $this->pdfLoggingService->logInfo('Visual comparison completed for SSB form', [
            'template' => 'ssb',
            'match' => $results['overall_match'],
            'report' => $reportPath
        ]);
    }
    
    /**
     * Test that the SME account opening PDF visually matches the design template
     */
    public function test_sme_account_opening_pdf_visual_comparison(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Create application state with test data
        $applicationState = $this->createSMEAccountOpeningTestData();
        
        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);
        
        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath, 
            'sme_account_opening', 
            $this->threshold
        );
        
        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
        
        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'], 
            "SME account opening PDF does not match design template. See report: {$reportPath}"
        );
        
        // Log the results
        $this->pdfLoggingService->logInfo('Visual comparison completed for SME account opening', [
            'template' => 'sme_account_opening',
            'match' => $results['overall_match'],
            'report' => $reportPath
        ]);
    }
    
    /**
     * Test that the account holders PDF visually matches the design template
     */
    public function test_account_holders_pdf_visual_comparison(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Create application state with test data
        $applicationState = $this->createAccountHoldersTestData();
        
        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);
        
        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath, 
            'account_holders', 
            $this->threshold
        );
        
        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
        
        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'], 
            "Account holders PDF does not match design template. See report: {$reportPath}"
        );
        
        // Log the results
        $this->pdfLoggingService->logInfo('Visual comparison completed for account holders', [
            'template' => 'account_holders',
            'match' => $results['overall_match'],
            'report' => $reportPath
        ]);
    }
    
    /**
     * Test PDF generation with different data sets and verify visual consistency
     */
    public function test_pdf_generation_with_different_data_sets(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Define test data sets with different values
        $testDataSets = [
            [
                'template' => 'zb_account_opening',
                'state' => $this->createZBAccountOpeningTestData('John', 'Doe'),
                'description' => 'ZB Account Opening - Standard Data'
            ],
            [
                'template' => 'zb_account_opening',
                'state' => $this->createZBAccountOpeningTestData('Jane', 'Smith'),
                'description' => 'ZB Account Opening - Different Name'
            ],
            [
                'template' => 'ssb',
                'state' => $this->createSSBFormTestData('Robert', 'Johnson'),
                'description' => 'SSB Form - Standard Data'
            ],
            [
                'template' => 'ssb',
                'state' => $this->createSSBFormTestData('Emily', 'Williams'),
                'description' => 'SSB Form - Different Name'
            ],
            [
                'template' => 'sme_account_opening',
                'state' => $this->createSMEAccountOpeningTestData('Business A'),
                'description' => 'SME Account Opening - Business A'
            ],
            [
                'template' => 'sme_account_opening',
                'state' => $this->createSMEAccountOpeningTestData('Business B'),
                'description' => 'SME Account Opening - Business B'
            ],
            [
                'template' => 'account_holders',
                'state' => $this->createAccountHoldersTestData('Michael', 'Brown'),
                'description' => 'Account Holders - Standard Data'
            ],
            [
                'template' => 'account_holders',
                'state' => $this->createAccountHoldersTestData('Sarah', 'Davis'),
                'description' => 'Account Holders - Different Name'
            ]
        ];
        
        // Test each data set
        foreach ($testDataSets as $index => $dataSet) {
            // Generate PDF
            $pdfPath = $this->generatePDF($dataSet['state']);
            
            // Assert that the PDF was generated successfully
            $this->assertTrue(Storage::disk('public')->exists($pdfPath));
            
            // Compare with design template
            $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
                $pdfPath, 
                $dataSet['template'], 
                $this->threshold
            );
            
            // Generate visual report
            $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
            
            // Assert that the comparison was successful
            $this->assertTrue(
                $results['overall_match'], 
                "Data set {$dataSet['description']} PDF does not match design template. See report: {$reportPath}"
            );
            
            // Log the results
            $this->pdfLoggingService->logInfo("Visual comparison completed for data set {$dataSet['description']}", [
                'template' => $dataSet['template'],
                'match' => $results['overall_match'],
                'report' => $reportPath
            ]);
        }
    }
    
    /**
     * Test visual comparison with edge case data
     */
    public function test_visual_comparison_with_edge_case_data(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Define edge case test data
        $edgeCases = [
            // Very long names
            [
                'template' => 'zb_account_opening',
                'state' => $this->createZBAccountOpeningTestData(
                    'Johnathon-Christopher-Alexander', 
                    'Smith-Johnson-Williams-Brown-Davis-Miller-Wilson'
                ),
                'description' => 'Very long names'
            ],
            // Special characters
            [
                'template' => 'ssb',
                'state' => $this->createSSBFormTestData(
                    'John-Émile', 
                    "O'Connor-Müller"
                ),
                'description' => 'Names with special characters'
            ],
            // Maximum numeric values
            [
                'template' => 'account_holders',
                'state' => $this->createAccountHoldersTestDataWithCustomValues(
                    'John', 'Doe',
                    [
                        'grossMonthlySalary' => '999999999',
                        'loanAmount' => '999999999',
                        'repaymentPeriod' => '999'
                    ]
                ),
                'description' => 'Maximum numeric values'
            ],
            // Minimum values
            [
                'template' => 'sme_account_opening',
                'state' => $this->createSMEAccountOpeningTestDataWithCustomValues(
                    'Micro Business',
                    [
                        'annualTurnover' => '0',
                        'numberOfEmployees' => '1'
                    ]
                ),
                'description' => 'Minimum values'
            ]
        ];
        
        // Test each edge case
        foreach ($edgeCases as $index => $case) {
            $this->pdfLoggingService->logInfo("Testing edge case: {$case['description']}", [
                'template' => $case['template'],
                'case_index' => $index
            ]);
            
            try {
                // Generate PDF
                $pdfPath = $this->generatePDF($case['state']);
                
                // Compare with design template
                $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
                    $pdfPath, 
                    $case['template'], 
                    $this->threshold
                );
                
                // Generate visual report
                $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
                
                // Log the results
                $this->pdfLoggingService->logInfo("Edge case visual comparison results", [
                    'template' => $case['template'],
                    'description' => $case['description'],
                    'match' => $results['overall_match'],
                    'report' => $reportPath
                ]);
                
                // Assert that the comparison was successful
                $this->assertTrue(
                    $results['overall_match'], 
                    "Edge case '{$case['description']}' PDF does not match design template. See report: {$reportPath}"
                );
            } catch (\Exception $e) {
                $this->pdfLoggingService->logError("Error testing edge case: {$case['description']}", [
                    'template' => $case['template'],
                    'error' => $e->getMessage()
                ], $e);
                
                // Re-throw the exception to fail the test
                throw $e;
            }
        }
    }
    
    /**
     * Test visual comparison with form variations
     */
    public function test_visual_comparison_with_form_variations(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Define form variations
        $variations = [
            // ZB Account Opening variations
            [
                'template' => 'zb_account_opening',
                'state' => $this->createZBAccountOpeningTestDataWithCustomValues('John', 'Doe', [
                    'title' => 'Dr',
                    'gender' => 'Female',
                    'employmentStatus' => 'Contract',
                    'accountCurrency' => 'ZWL'
                ]),
                'description' => 'ZB Account with different titles and employment status'
            ],
            // SSB variations
            [
                'template' => 'ssb',
                'state' => $this->createSSBFormTestDataWithCustomValues('Jane', 'Smith', [
                    'department' => 'Health',
                    'loanPurpose' => 'Education',
                    'repaymentPeriod' => '48'
                ]),
                'description' => 'SSB with different department and loan purpose'
            ],
            // SME variations
            [
                'template' => 'sme_account_opening',
                'state' => $this->createSMEAccountOpeningTestDataWithCustomValues('Large Enterprise', [
                    'businessType' => 'Public Limited Company',
                    'natureOfBusiness' => 'Manufacturing',
                    'numberOfEmployees' => '500',
                    'accountCurrency' => 'ZWL'
                ]),
                'description' => 'SME with different business type and size'
            ],
            // Account holders variations
            [
                'template' => 'account_holders',
                'state' => $this->createAccountHoldersTestDataWithCustomValues('Robert', 'Johnson', [
                    'title' => 'Prof',
                    'occupation' => 'Medical Doctor',
                    'loanPurpose' => 'Business Investment',
                    'repaymentPeriod' => '60'
                ]),
                'description' => 'Account holders with different occupation and loan purpose'
            ]
        ];
        
        // Test each variation
        foreach ($variations as $index => $variation) {
            $this->pdfLoggingService->logInfo("Testing form variation: {$variation['description']}", [
                'template' => $variation['template'],
                'variation_index' => $index
            ]);
            
            try {
                // Generate PDF
                $pdfPath = $this->generatePDF($variation['state']);
                
                // Compare with design template
                $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
                    $pdfPath, 
                    $variation['template'], 
                    $this->threshold
                );
                
                // Generate visual report
                $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);
                
                // Log the results
                $this->pdfLoggingService->logInfo("Form variation visual comparison results", [
                    'template' => $variation['template'],
                    'description' => $variation['description'],
                    'match' => $results['overall_match'],
                    'report' => $reportPath
                ]);
                
                // Assert that the comparison was successful
                $this->assertTrue(
                    $results['overall_match'], 
                    "Variation '{$variation['description']}' PDF does not match design template. See report: {$reportPath}"
                );
            } catch (\Exception $e) {
                $this->pdfLoggingService->logError("Error testing form variation: {$variation['description']}", [
                    'template' => $variation['template'],
                    'error' => $e->getMessage()
                ], $e);
                
                // Re-throw the exception to fail the test
                throw $e;
            }
        }
    }
    
    /**
     * Test batch visual comparison for all templates
     */
    public function test_batch_visual_comparison(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Define templates to test
        $templates = [
            'zb_account_opening' => $this->createZBAccountOpeningTestData(),
            'ssb' => $this->createSSBFormTestData(),
            'sme_account_opening' => $this->createSMEAccountOpeningTestData(),
            'account_holders' => $this->createAccountHoldersTestData()
        ];
        
        $batchResults = [];
        $allPassed = true;
        
        // Start batch processing
        $startTime = microtime(true);
        
        // Test each template
        foreach ($templates as $templateName => $applicationState) {
            try {
                // Generate PDF
                $pdfPath = $this->generatePDF($applicationState);
                
                // Compare with design template
                $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
                    $pdfPath, 
                    $templateName, 
                    $this->threshold
                );
                
                // Store results
                $batchResults[$templateName] = [
                    'path' => $pdfPath,
                    'match' => $results['overall_match'],
                    'difference' => $this->calculateAverageDifference($results),
                    'page_count' => $results['pdf_pages']
                ];
                
                // Update overall status
                if (!$results['overall_match']) {
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->pdfLoggingService->logError("Error in batch processing for template: {$templateName}", [
                    'error' => $e->getMessage()
                ], $e);
                
                $batchResults[$templateName] = [
                    'error' => $e->getMessage(),
                    'match' => false
                ];
                
                $allPassed = false;
            }
        }
        
        // Calculate batch processing time
        $processingTime = microtime(true) - $startTime;
        
        // Log batch results
        $this->pdfLoggingService->logInfo("Batch visual comparison completed", [
            'processing_time' => $processingTime,
            'templates_tested' => count($templates),
            'all_passed' => $allPassed,
            'results' => $batchResults
        ]);
        
        // Assert that all templates passed
        $this->assertTrue($allPassed, "Not all templates passed visual comparison");
    }
    
    /**
     * Test visual comparison with different thresholds
     */
    public function test_visual_comparison_with_different_thresholds(): void
    {
        // Skip test if ImageMagick is not available
        if (!$this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }
        
        // Create application state with test data
        $applicationState = $this->createZBAccountOpeningTestData();
        
        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);
        
        // Test with different thresholds
        $thresholds = [1.0, 3.0, 5.0, 10.0];
        $results = [];
        
        foreach ($thresholds as $threshold) {
            // Compare with design template
            $comparisonResult = $this->pdfVisualComparisonService->comparePdfWithDesign(
                $pdfPath, 
                'zb_account_opening', 
                $threshold
            );
            
            // Generate visual report
            $reportPath = $this->pdfVisualComparisonService->generateVisualReport($comparisonResult);
            
            // Store results
            $results[$threshold] = [
                'match' => $comparisonResult['overall_match'],
                'average_difference' => $comparisonResult['average_difference'] ?? $this->calculateAverageDifference($comparisonResult),
                'report' => $reportPath
            ];
            
            // Log the results
            $this->pdfLoggingService->logInfo("Visual comparison with threshold {$threshold}%", [
                'template' => 'zb_account_opening',
                'threshold' => $threshold,
                'match' => $comparisonResult['overall_match'],
                'report' => $reportPath
            ]);
        }
        
        // Output results for informational purposes
        $this->pdfLoggingService->logInfo("Threshold comparison results", [
            'results' => $results
        ]);
        
        // We don't assert here because different thresholds may pass or fail
        // This test is for informational purposes to see how different thresholds affect the results
        $this->assertTrue(true);
    }
    
    /**
     * Generate a PDF from an application state
     * 
     * @param ApplicationState $applicationState
     * @return string Path to the generated PDF
     */
    private function generatePDF(ApplicationState $applicationState): string
    {
        // Ensure the applications directory exists
        Storage::disk('public')->makeDirectory('applications');
        
        // Generate PDF
        $pdfPath = $this->pdfGeneratorService->generateApplicationPDF($applicationState);
        
        // Assert that the PDF was generated successfully
        $this->assertTrue(Storage::disk('public')->exists($pdfPath));
        
        return $pdfPath;
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
     * Check if ImageMagick is available for visual comparison
     * 
     * @return bool True if ImageMagick is available, false otherwise
     */
    private function isImageMagickAvailable(): bool
    {
        // Check if the Spatie PDF to Image library is available
        if (class_exists('Spatie\PdfToImage\Pdf')) {
            return true;
        }
        
        // Check if ImageMagick's convert command is available
        exec('which convert', $output, $returnCode);
        if ($returnCode === 0) {
            return true;
        }
        
        // Check if ImageMagick's compare command is available
        exec('which compare', $output, $returnCode);
        if ($returnCode === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Create test data for ZB account opening
     * 
     * @param string $firstName First name for test data
     * @param string $lastName Last name for test data
     * @return ApplicationState
     */
    private function createZBAccountOpeningTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
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
     * @return ApplicationState
     */
    private function createZBAccountOpeningTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): ApplicationState
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
     * @return ApplicationState
     */
    private function createSSBFormTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
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
     * @return ApplicationState
     */
    private function createSSBFormTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): ApplicationState
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
     * @return ApplicationState
     */
    private function createSMEAccountOpeningTestData(string $businessName = 'Test Business'): ApplicationState
    {
        return new ApplicationState([
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
     * @return ApplicationState
     */
    private function createSMEAccountOpeningTestDataWithCustomValues(string $businessName, array $customValues): ApplicationState
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
     * @return ApplicationState
     */
    private function createAccountHoldersTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
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
     * @return ApplicationState
     */
    private function createAccountHoldersTestDataWithCustomValues(string $firstName, string $lastName, array $customValues): ApplicationState
    {
        $state = $this->createAccountHoldersTestData($firstName, $lastName);
        
        foreach ($customValues as $key => $value) {
            $state->form_data['formResponses'][$key] = $value;
        }
        
        return $state;
    }
}