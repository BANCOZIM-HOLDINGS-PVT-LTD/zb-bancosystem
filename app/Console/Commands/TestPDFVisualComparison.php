<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PDFVisualComparisonService;
use App\Services\PDFLoggingService;
use App\Services\PDFGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestPDFVisualComparison extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:visual-comparison-test
                            {--template= : Specific template to test (zb_account_opening, ssb, sme_account_opening, account_holders)}
                            {--dataset= : Type of dataset to use (default, edge_case, variation, all)}
                            {--threshold= : Difference threshold percentage (0-100)}
                            {--report : Generate HTML report}
                            {--output= : Output directory for reports}
                            {--ci : Run in CI mode with simplified output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive visual comparison tests for PDF templates';

    /**
     * Execute the console command.
     */
    public function handle(PDFLoggingService $pdfLoggingService, PDFVisualComparisonService $comparisonService)
    {
        $template = $this->option('template');
        $dataset = $this->option('dataset') ?? 'all';
        $threshold = $this->option('threshold') ? (float) $this->option('threshold') : null;
        $generateReport = $this->option('report') || config('pdf_visual_testing.reports.generate_html', true);
        $outputDir = $this->option('output') ?? storage_path('app/temp/pdf-visual-tests/reports');
        $ciMode = $this->option('ci');
        
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
        
        // Create output directory if needed
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
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
        
        // Define dataset types to test
        $datasetTypes = ['default', 'edge_case', 'variation'];
        
        // Filter dataset types if specific one requested
        if ($dataset !== 'all' && in_array($dataset, $datasetTypes)) {
            $datasetTypes = [$dataset];
        } elseif ($dataset !== 'all' && !in_array($dataset, $datasetTypes)) {
            $this->error("Unknown dataset type: {$dataset}");
            return 1;
        }
        
        // Initialize results
        $results = [
            'timestamp' => now()->toDateTimeString(),
            'templates_tested' => count($templates),
            'datasets_tested' => 0,
            'tests_passed' => 0,
            'tests_failed' => 0,
            'template_results' => []
        ];
        
        // Create summary report file
        $summaryReportPath = $outputDir . '/summary_report_' . now()->format('Ymd_His') . '.html';
        
        if (!$ciMode) {
            $this->info("Starting PDF visual comparison tests");
            $this->info("Templates to test: " . implode(', ', array_keys($templates)));
            $this->info("Dataset types to test: " . implode(', ', $datasetTypes));
        }
        
        // Test each template
        foreach ($templates as $templateKey => $templateConfig) {
            // Get threshold for this template
            if ($threshold === null) {
                $templateThreshold = config("pdf_visual_testing.template_thresholds.{$templateKey}", 
                    config('pdf_visual_testing.default_threshold', 5.0));
            } else {
                $templateThreshold = $threshold;
            }
            
            if (!$ciMode) {
                $this->info("\nTesting template: {$templateConfig['name']}");
                $this->info("Using difference threshold: {$templateThreshold}%");
            }
            
            $templateResults = [
                'name' => $templateConfig['name'],
                'threshold' => $templateThreshold,
                'datasets_tested' => 0,
                'datasets_passed' => 0,
                'dataset_results' => []
            ];
            
            // Test each dataset type
            foreach ($datasetTypes as $datasetType) {
                if (!$ciMode) {
                    $this->info("\n  Testing dataset type: {$datasetType}");
                }
                
                // Get test data sets for this template and dataset type
                $dataSets = $this->getDataSets($templateKey, $datasetType);
                $templateResults['datasets_tested'] += count($dataSets);
                $results['datasets_tested'] += count($dataSets);
                
                // Test each data set
                foreach ($dataSets as $dataSetKey => $dataSet) {
                    if (!$ciMode) {
                        $this->info("    Testing dataset: {$dataSetKey}");
                    }
                    
                    try {
                        // Generate PDF
                        $pdfPath = $pdfGeneratorService->generateApplicationPDF($dataSet['state']);
                        
                        if (!$ciMode) {
                            $this->info("    PDF generated: {$pdfPath}");
                        }
                        
                        // Compare with design template
                        $comparisonResult = $comparisonService->comparePdfWithDesign(
                            $pdfPath, 
                            $templateConfig['design'], 
                            $templateThreshold
                        );
                        
                        // Generate report if requested
                        $reportPath = null;
                        if ($generateReport) {
                            $reportName = "{$templateKey}_{$datasetType}_{$dataSetKey}";
                            $reportPath = $comparisonService->generateVisualReport($comparisonResult, $reportName);
                            
                            if (!$ciMode) {
                                $this->info("    Visual report generated: {$reportPath}");
                            }
                        }
                        
                        // Store dataset result
                        $datasetResult = [
                            'match' => $comparisonResult['overall_match'],
                            'difference' => $this->calculateAverageDifference($comparisonResult),
                            'max_difference' => $comparisonResult['max_difference'] ?? 0,
                            'report' => $reportPath,
                            'page_results' => []
                        ];
                        
                        // Store page results
                        foreach ($comparisonResult['page_results'] as $pageResult) {
                            $datasetResult['page_results'][] = [
                                'page' => $pageResult['page'],
                                'match' => $pageResult['match'],
                                'difference' => $pageResult['difference']
                            ];
                        }
                        
                        $templateResults['dataset_results']["{$datasetType}_{$dataSetKey}"] = $datasetResult;
                        
                        // Update counts
                        if ($comparisonResult['overall_match']) {
                            $templateResults['datasets_passed']++;
                            $results['tests_passed']++;
                            
                            if (!$ciMode) {
                                $this->info("    ✅ Dataset {$dataSetKey} matches design within threshold of {$templateThreshold}%");
                            }
                        } else {
                            $results['tests_failed']++;
                            
                            if (!$ciMode) {
                                $this->error("    ❌ Dataset {$dataSetKey} does not match design within threshold of {$templateThreshold}%");
                                
                                // Show page-by-page results
                                foreach ($comparisonResult['page_results'] as $pageResult) {
                                    $status = $pageResult['match'] ? '✅' : '❌';
                                    $this->line("      {$status} Page {$pageResult['page']}: Difference " . number_format($pageResult['difference'], 2) . "%");
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $results['tests_failed']++;
                        
                        if (!$ciMode) {
                            $this->error("    ❌ Error testing dataset {$dataSetKey}: {$e->getMessage()}");
                        }
                        
                        // Log the error
                        Log::error("Error in PDF visual comparison test", [
                            'template' => $templateKey,
                            'dataset_type' => $datasetType,
                            'dataset' => $dataSetKey,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Store error result
                        $templateResults['dataset_results']["{$datasetType}_{$dataSetKey}"] = [
                            'match' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
            
            // Store template results
            $results['template_results'][$templateKey] = $templateResults;
            
            // Output template summary
            if (!$ciMode) {
                $this->info("\n  Template {$templateConfig['name']} summary:");
                $this->info("  Datasets tested: {$templateResults['datasets_tested']}");
                $this->info("  Datasets passed: {$templateResults['datasets_passed']}");
                $this->info("  Success rate: " . $this->calculateSuccessRate($templateResults['datasets_passed'], $templateResults['datasets_tested']) . "%");
            }
        }
        
        // Generate summary report
        $this->generateSummaryReport($results, $summaryReportPath);
        
        // Output overall result
        if (!$ciMode) {
            $this->newLine();
            $this->info("Overall results:");
            $this->info("Templates tested: {$results['templates_tested']}");
            $this->info("Datasets tested: {$results['datasets_tested']}");
            $this->info("Tests passed: {$results['tests_passed']}");
            $this->info("Tests failed: {$results['tests_failed']}");
            $this->info("Success rate: " . $this->calculateSuccessRate($results['tests_passed'], $results['datasets_tested']) . "%");
            $this->info("Summary report: {$summaryReportPath}");
        } else {
            // In CI mode, just output the basic results
            $this->line("PDF Visual Comparison: {$results['tests_passed']}/{$results['datasets_tested']} tests passed (" . 
                $this->calculateSuccessRate($results['tests_passed'], $results['datasets_tested']) . "%)");
        }
        
        // Return success if all tests passed
        return $results['tests_failed'] === 0 ? 0 : 1;
    } 
   
    /**
     * Generate a summary HTML report of all test results
     * 
     * @param array $results Test results
     * @param string $reportPath Path to save the report
     * @return void
     */
    private function generateSummaryReport(array $results, string $reportPath): void
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PDF Visual Comparison Summary Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .summary { margin-bottom: 20px; padding: 15px; background-color: #f5f5f5; border-radius: 5px; }
        .template { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .template-header { margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #ddd; }
        .dataset { margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border-radius: 5px; }
        .dataset-header { margin-bottom: 5px; }
        .match { color: green; font-weight: bold; }
        .no-match { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .stats { display: flex; flex-wrap: wrap; margin-bottom: 15px; }
        .stat-box { background-color: #eef; padding: 10px; margin-right: 15px; margin-bottom: 10px; border-radius: 5px; min-width: 150px; }
        .progress-bar-container { width: 100%; background-color: #f0f0f0; height: 20px; border-radius: 10px; margin-bottom: 10px; }
        .progress-bar { height: 100%; border-radius: 10px; text-align: center; line-height: 20px; color: white; }
        .progress-low { background-color: #4CAF50; }
        .progress-medium { background-color: #FFC107; }
        .progress-high { background-color: #F44336; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>PDF Visual Comparison Summary Report</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="stats">
            <div class="stat-box">
                <strong>Generated:</strong> ' . $results['timestamp'] . '<br>
                <strong>Templates Tested:</strong> ' . $results['templates_tested'] . '<br>
                <strong>Datasets Tested:</strong> ' . $results['datasets_tested'] . '
            </div>
            <div class="stat-box">
                <strong>Tests Passed:</strong> ' . $results['tests_passed'] . '<br>
                <strong>Tests Failed:</strong> ' . $results['tests_failed'] . '<br>
                <strong>Success Rate:</strong> ' . $this->calculateSuccessRate($results['tests_passed'], $results['datasets_tested']) . '%
            </div>
        </div>
        
        <h3>Template Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Datasets Tested</th>
                    <th>Datasets Passed</th>
                    <th>Success Rate</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($results['template_results'] as $templateKey => $templateResult) {
            $html .= '
                <tr>
                    <td>' . $templateResult['name'] . '</td>
                    <td>' . $templateResult['datasets_tested'] . '</td>
                    <td>' . $templateResult['datasets_passed'] . '</td>
                    <td>' . $this->calculateSuccessRate($templateResult['datasets_passed'], $templateResult['datasets_tested']) . '%</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>
    </div>';
        
        // Add template details
        foreach ($results['template_results'] as $templateKey => $templateResult) {
            $html .= '
    <div class="template">
        <div class="template-header">
            <h2>' . $templateResult['name'] . '</h2>
            <div class="stats">
                <div class="stat-box">
                    <strong>Datasets Tested:</strong> ' . $templateResult['datasets_tested'] . '<br>
                    <strong>Datasets Passed:</strong> ' . $templateResult['datasets_passed'] . '<br>
                    <strong>Success Rate:</strong> ' . $this->calculateSuccessRate($templateResult['datasets_passed'], $templateResult['datasets_tested']) . '%
                </div>
                <div class="stat-box">
                    <strong>Threshold:</strong> ' . $templateResult['threshold'] . '%
                </div>
            </div>
        </div>
        
        <h3>Dataset Results</h3>
        <table>
            <thead>
                <tr>
                    <th>Dataset</th>
                    <th>Match</th>
                    <th>Difference</th>
                    <th>Max Difference</th>
                    <th>Report</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($templateResult['dataset_results'] as $datasetKey => $datasetResult) {
                $match = isset($datasetResult['match']) && $datasetResult['match'] ? '<span class="match">Yes</span>' : '<span class="no-match">No</span>';
                $difference = isset($datasetResult['difference']) ? number_format($datasetResult['difference'], 2) . '%' : 'N/A';
                $maxDifference = isset($datasetResult['max_difference']) ? number_format($datasetResult['max_difference'], 2) . '%' : 'N/A';
                $report = isset($datasetResult['report']) ? '<a href="file://' . $datasetResult['report'] . '">View Report</a>' : 'N/A';
                
                if (isset($datasetResult['error'])) {
                    $match = '<span class="no-match">Error</span>';
                    $difference = '<span class="no-match">' . $datasetResult['error'] . '</span>';
                }
                
                $html .= '
                <tr>
                    <td>' . $datasetKey . '</td>
                    <td>' . $match . '</td>
                    <td>' . $difference . '</td>
                    <td>' . $maxDifference . '</td>
                    <td>' . $report . '</td>
                </tr>';
            }
            
            $html .= '
            </tbody>
        </table>';
            
            // Add page details for each dataset
            foreach ($templateResult['dataset_results'] as $datasetKey => $datasetResult) {
                if (isset($datasetResult['page_results']) && !empty($datasetResult['page_results'])) {
                    $html .= '
        <div class="dataset">
            <div class="dataset-header">
                <h4>Page Details for ' . $datasetKey . '</h4>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Match</th>
                        <th>Difference</th>
                    </tr>
                </thead>
                <tbody>';
                    
                    foreach ($datasetResult['page_results'] as $pageResult) {
                        $match = $pageResult['match'] ? '<span class="match">Yes</span>' : '<span class="no-match">No</span>';
                        $difference = number_format($pageResult['difference'], 2) . '%';
                        
                        $html .= '
                    <tr>
                        <td>Page ' . $pageResult['page'] . '</td>
                        <td>' . $match . '</td>
                        <td>' . $difference . '</td>
                    </tr>';
                    }
                    
                    $html .= '
                </tbody>
            </table>
        </div>';
                }
            }
            
            $html .= '
    </div>';
        }
        
        $html .= '
</body>
</html>';
        
        // Write the report to file
        File::put($reportPath, $html);
    }
    
    /**
     * Calculate success rate percentage
     * 
     * @param int $passed Number of passed tests
     * @param int $total Total number of tests
     * @return string Formatted success rate percentage
     */
    private function calculateSuccessRate(int $passed, int $total): string
    {
        if ($total === 0) {
            return '0';
        }
        
        return number_format(($passed / $total) * 100, 1);
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