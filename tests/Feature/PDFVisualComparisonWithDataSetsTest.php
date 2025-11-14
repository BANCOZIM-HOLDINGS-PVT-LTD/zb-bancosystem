<?php

namespace Tests\Feature;

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDFLoggingService;
use App\Services\PDFVisualComparisonService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PDFVisualComparisonWithDataSetsTest extends TestCase
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
        if (! File::exists($this->tempDir)) {
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
        $this->pdfVisualComparisonService = new PDFVisualComparisonService;

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
     * Test that PDFs with standard data match their design templates
     *
     * @dataProvider standardDataProvider
     */
    public function test_pdfs_with_standard_data_match_design(string $templateName, callable $dataCreator): void
    {
        // Skip test if ImageMagick is not available
        if (! $this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }

        // Create application state with test data
        $applicationState = $dataCreator();

        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);

        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath,
            $templateName,
            $this->threshold
        );

        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);

        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'],
            "{$templateName} PDF with standard data does not match design template. See report: {$reportPath}"
        );
    }

    /**
     * Test that PDFs with edge case data match their design templates
     *
     * @dataProvider edgeCaseDataProvider
     */
    public function test_pdfs_with_edge_case_data_match_design(string $templateName, callable $dataCreator, string $description): void
    {
        // Skip test if ImageMagick is not available
        if (! $this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }

        // Create application state with test data
        $applicationState = $dataCreator();

        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);

        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath,
            $templateName,
            $this->threshold
        );

        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);

        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'],
            "{$templateName} PDF with {$description} does not match design template. See report: {$reportPath}"
        );
    }

    /**
     * Test that PDFs with form variations match their design templates
     *
     * @dataProvider formVariationDataProvider
     */
    public function test_pdfs_with_form_variations_match_design(string $templateName, callable $dataCreator, string $description): void
    {
        // Skip test if ImageMagick is not available
        if (! $this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }

        // Create application state with test data
        $applicationState = $dataCreator();

        // Generate PDF
        $pdfPath = $this->generatePDF($applicationState);

        // Compare with design template
        $results = $this->pdfVisualComparisonService->comparePdfWithDesign(
            $pdfPath,
            $templateName,
            $this->threshold
        );

        // Generate visual report
        $reportPath = $this->pdfVisualComparisonService->generateVisualReport($results);

        // Assert that the comparison was successful
        $this->assertTrue(
            $results['overall_match'],
            "{$templateName} PDF with {$description} does not match design template. See report: {$reportPath}"
        );
    }

    /**
     * Test batch processing of multiple templates and datasets
     */
    public function test_batch_processing_of_multiple_templates_and_datasets(): void
    {
        // Skip test if ImageMagick is not available
        if (! $this->isImageMagickAvailable()) {
            $this->markTestSkipped('ImageMagick is not available for visual comparison');
        }

        // Define templates to test
        $templates = [
            'zb_account_opening' => [
                'name' => 'ZB Account Opening',
                'datasets' => [
                    'standard' => fn () => $this->createZBAccountOpeningTestData(),
                    'variation' => fn () => $this->createZBAccountOpeningTestDataWithCustomValues('Jane', 'Smith', [
                        'title' => 'Mrs',
                        'gender' => 'Female',
                    ]),
                ],
            ],
            'ssb' => [
                'name' => 'SSB Form',
                'datasets' => [
                    'standard' => fn () => $this->createSSBFormTestData(),
                    'variation' => fn () => $this->createSSBFormTestDataWithCustomValues('Robert', 'Johnson', [
                        'department' => 'Finance',
                    ]),
                ],
            ],
        ];

        $results = [];
        $allPassed = true;

        // Process each template
        foreach ($templates as $templateKey => $templateConfig) {
            $templateResults = [
                'name' => $templateConfig['name'],
                'datasets_tested' => count($templateConfig['datasets']),
                'datasets_passed' => 0,
            ];

            // Process each dataset
            foreach ($templateConfig['datasets'] as $datasetKey => $datasetCreator) {
                try {
                    // Create application state
                    $applicationState = $datasetCreator();

                    // Generate PDF
                    $pdfPath = $this->generatePDF($applicationState);

                    // Compare with design template
                    $comparisonResult = $this->pdfVisualComparisonService->comparePdfWithDesign(
                        $pdfPath,
                        $templateKey,
                        $this->threshold
                    );

                    // Store result
                    if ($comparisonResult['overall_match']) {
                        $templateResults['datasets_passed']++;
                    } else {
                        $allPassed = false;
                    }
                } catch (\Exception $e) {
                    $allPassed = false;
                    $this->fail("Error processing {$templateKey} with {$datasetKey} dataset: {$e->getMessage()}");
                }
            }

            $results[$templateKey] = $templateResults;
        }

        // Assert that all templates and datasets passed
        $this->assertTrue($allPassed, 'Not all templates and datasets passed visual comparison');

        // Assert that all datasets were processed
        foreach ($results as $templateKey => $templateResult) {
            $this->assertEquals(
                $templateResult['datasets_tested'],
                $templateResult['datasets_passed'],
                "Not all datasets passed for {$templateResult['name']}"
            );
        }
    }

    /**
     * Data provider for standard test data
     */
    public function standardDataProvider(): array
    {
        return [
            'ZB Account Opening' => ['zb_account_opening', fn () => $this->createZBAccountOpeningTestData()],
            'SSB Form' => ['ssb', fn () => $this->createSSBFormTestData()],
            'SME Account Opening' => ['sme_account_opening', fn () => $this->createSMEAccountOpeningTestData()],
            'Account Holders' => ['account_holders', fn () => $this->createAccountHoldersTestData()],
        ];
    }

    /**
     * Data provider for edge case test data
     */
    public function edgeCaseDataProvider(): array
    {
        return [
            'ZB Account Opening - Long Names' => [
                'zb_account_opening',
                fn () => $this->createZBAccountOpeningTestData(
                    'Johnathon-Christopher-Alexander',
                    'Smith-Johnson-Williams-Brown-Davis-Miller-Wilson'
                ),
                'long names',
            ],
            'ZB Account Opening - Special Characters' => [
                'zb_account_opening',
                fn () => $this->createZBAccountOpeningTestData(
                    'John-Émile',
                    "O'Connor-Müller"
                ),
                'special characters',
            ],
            'SSB Form - Maximum Values' => [
                'ssb',
                fn () => $this->createSSBFormTestDataWithCustomValues('John', 'Doe', [
                    'grossMonthlySalary' => '999999999',
                    'loanAmount' => '999999999',
                    'repaymentPeriod' => '999',
                ]),
                'maximum values',
            ],
            'SME Account Opening - Long Business Name' => [
                'sme_account_opening',
                fn () => $this->createSMEAccountOpeningTestData(
                    'Extremely Long Business Name That Exceeds Normal Limits For Testing Purposes Only'
                ),
                'long business name',
            ],
            'SME Account Opening - Minimum Values' => [
                'sme_account_opening',
                fn () => $this->createSMEAccountOpeningTestDataWithCustomValues('Micro Business', [
                    'annualTurnover' => '0',
                    'numberOfEmployees' => '1',
                ]),
                'minimum values',
            ],
            'Account Holders - Maximum Values' => [
                'account_holders',
                fn () => $this->createAccountHoldersTestDataWithCustomValues('John', 'Doe', [
                    'grossMonthlySalary' => '999999999',
                    'loanAmount' => '999999999',
                    'repaymentPeriod' => '999',
                ]),
                'maximum values',
            ],
        ];
    }

    /**
     * Data provider for form variation test data
     */
    public function formVariationDataProvider(): array
    {
        return [
            'ZB Account Opening - Female Doctor' => [
                'zb_account_opening',
                fn () => $this->createZBAccountOpeningTestDataWithCustomValues('Jane', 'Smith', [
                    'title' => 'Dr',
                    'gender' => 'Female',
                    'employmentStatus' => 'Contract',
                    'accountCurrency' => 'ZWL',
                ]),
                'female doctor variation',
            ],
            'SSB Form - Health Department' => [
                'ssb',
                fn () => $this->createSSBFormTestDataWithCustomValues('Jane', 'Smith', [
                    'department' => 'Health',
                    'loanPurpose' => 'Education',
                    'repaymentPeriod' => '48',
                ]),
                'health department variation',
            ],
            'SME Account Opening - Manufacturing Company' => [
                'sme_account_opening',
                fn () => $this->createSMEAccountOpeningTestDataWithCustomValues('Large Enterprise', [
                    'businessType' => 'Public Limited Company',
                    'natureOfBusiness' => 'Manufacturing',
                    'numberOfEmployees' => '500',
                    'accountCurrency' => 'ZWL',
                ]),
                'manufacturing company variation',
            ],
            'Account Holders - Professor' => [
                'account_holders',
                fn () => $this->createAccountHoldersTestDataWithCustomValues('Robert', 'Johnson', [
                    'title' => 'Prof',
                    'occupation' => 'Medical Doctor',
                    'loanPurpose' => 'Business Investment',
                    'repaymentPeriod' => '60',
                ]),
                'professor variation',
            ],
        ];
    }

    /**
     * Generate a PDF from an application state
     *
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
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     */
    private function createZBAccountOpeningTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-zb-account-'.uniqid(),
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
                    'emailAddress' => $firstName.'.'.$lastName.'@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'ABC Company',
                    'occupation' => 'Software Developer',
                    'employmentStatus' => 'Permanent',
                    'grossMonthlySalary' => '5000',
                    'accountCurrency' => 'USD',
                    'serviceCenter' => 'Harare Main Branch',
                ],
            ],
        ]);
    }

    /**
     * Create test data for ZB account opening with custom values
     *
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     * @param  array  $customValues  Custom values to override defaults
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
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     */
    private function createSSBFormTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-ssb-form-'.uniqid(),
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
                    'emailAddress' => $firstName.'.'.$lastName.'@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'Government of Zimbabwe',
                    'department' => 'Education',
                    'employeeNumber' => 'EC12345',
                    'grossMonthlySalary' => '3000',
                    'loanAmount' => '10000',
                    'loanPurpose' => 'Home Improvement',
                    'repaymentPeriod' => '24',
                ],
            ],
        ]);
    }

    /**
     * Create test data for SSB form with custom values
     *
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     * @param  array  $customValues  Custom values to override defaults
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
     * @param  string  $businessName  Business name for test data
     */
    private function createSMEAccountOpeningTestData(string $businessName = 'Test Business'): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-sme-account-'.uniqid(),
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'entrepreneur',
                'hasAccount' => false,
                'formId' => 'smes_business_account_opening.json',
                'formResponses' => [
                    'businessName' => $businessName,
                    'tradingName' => $businessName.' Trading',
                    'registrationNumber' => 'REG'.rand(10000, 99999),
                    'businessType' => 'Private Limited Company',
                    'dateOfIncorporation' => '2010-01-01',
                    'natureOfBusiness' => 'Technology Services',
                    'physicalAddress' => '456 Business Park, Harare',
                    'postalAddress' => 'P.O. Box 789, Harare',
                    'contactPerson' => 'Jane Manager',
                    'position' => 'General Manager',
                    'telephone' => '0772345678',
                    'email' => 'info@'.strtolower(str_replace(' ', '', $businessName)).'.com',
                    'annualTurnover' => '500000',
                    'numberOfEmployees' => '15',
                    'accountCurrency' => 'USD',
                ],
            ],
        ]);
    }

    /**
     * Create test data for SME account opening with custom values
     *
     * @param  string  $businessName  Business name for test data
     * @param  array  $customValues  Custom values to override defaults
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
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     */
    private function createAccountHoldersTestData(string $firstName = 'John', string $lastName = 'Doe'): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-account-holders-'.uniqid(),
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
                    'emailAddress' => $firstName.'.'.$lastName.'@example.com',
                    'mobile' => '0771234567',
                    'residentialAddress' => '123 Main Street, Harare',
                    'employerName' => 'ABC Company',
                    'occupation' => 'Software Developer',
                    'employmentStatus' => 'Permanent',
                    'grossMonthlySalary' => '5000',
                    'accountNumber' => '4001234567890',
                    'loanAmount' => '15000',
                    'loanPurpose' => 'Vehicle Purchase',
                    'repaymentPeriod' => '36',
                ],
            ],
        ]);
    }

    /**
     * Create test data for account holders with custom values
     *
     * @param  string  $firstName  First name for test data
     * @param  string  $lastName  Last name for test data
     * @param  array  $customValues  Custom values to override defaults
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
