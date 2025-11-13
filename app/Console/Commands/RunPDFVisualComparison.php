<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PDFVisualComparisonService;
use App\Services\PDFLoggingService;
use App\Services\PDFGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RunPDFVisualComparison extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:compare
                            {pdf_path : Path to the PDF file to compare}
                            {template : Template to compare with (zb_account_opening, ssb, sme_account_opening, account_holders)}
                            {--threshold= : Difference threshold percentage (0-100)}
                            {--report : Generate HTML report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare a generated PDF with design templates';

    /**
     * Execute the console command.
     */
    public function handle(PDFLoggingService $pdfLoggingService, PDFVisualComparisonService $comparisonService)
    {
        $pdfPath = $this->argument('pdf_path');
        $template = $this->argument('template');
        $threshold = $this->option('threshold') ? (float) $this->option('threshold') : null;
        $generateReport = $this->option('report') || config('pdf_visual_testing.reports.generate_html', true);
        
        // Validate PDF path
        if (!Storage::disk('public')->exists($pdfPath)) {
            $this->error("PDF file not found: {$pdfPath}");
            return 1;
        }
        
        // Validate template
        $validTemplates = ['zb_account_opening', 'ssb', 'sme_account_opening', 'account_holders'];
        if (!in_array($template, $validTemplates)) {
            $this->error("Invalid template: {$template}. Valid templates are: " . implode(', ', $validTemplates));
            return 1;
        }
        
        // Get threshold from config if not provided
        if ($threshold === null) {
            $threshold = config("pdf_visual_testing.template_thresholds.{$template}", 
                config('pdf_visual_testing.default_threshold', 5.0));
        }
        
        $this->info("Starting PDF visual comparison for template: {$template}");
        $this->info("Using difference threshold: {$threshold}%");
        
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
        
        try {
            // Compare with design template
            $this->info("Comparing PDF with design template...");
            $results = $comparisonService->comparePdfWithDesign(
                $pdfPath, 
                $template, 
                $threshold
            );
            
            // Generate report if requested
            if ($generateReport) {
                $reportPath = $comparisonService->generateVisualReport($results);
                $this->info("Visual report generated: {$reportPath}");
            }
            
            // Output results
            $this->newLine();
            $this->info("Comparison Results:");
            $this->info("Template: {$template}");
            $this->info("PDF Pages: {$results['pdf_pages']}");
            $this->info("Design Pages: {$results['design_pages']}");
            $this->info("Pages Match: " . ($results['pages_match'] ? 'Yes' : 'No'));
            $this->info("Overall Match: " . ($results['overall_match'] ? 'Yes' : 'No'));
            
            if (isset($results['average_difference'])) {
                $this->info("Average Difference: " . number_format($results['average_difference'], 2) . "%");
            }
            
            if (isset($results['min_difference'])) {
                $this->info("Min Difference: " . number_format($results['min_difference'], 2) . "%");
            }
            
            if (isset($results['max_difference'])) {
                $this->info("Max Difference: " . number_format($results['max_difference'], 2) . "%");
            }
            
            // Show page-by-page results
            $this->newLine();
            $this->info("Page-by-Page Results:");
            
            foreach ($results['page_results'] as $pageResult) {
                $status = $pageResult['match'] ? '✅' : '❌';
                $this->line("  {$status} Page {$pageResult['page']}: Difference " . number_format($pageResult['difference'], 2) . "%");
            }
            
            // Return success or failure based on overall match
            return $results['overall_match'] ? 0 : 1;
            
        } catch (\Exception $e) {
            $this->error("Error comparing PDF: {$e->getMessage()}");
            return 1;
        }
    }
}