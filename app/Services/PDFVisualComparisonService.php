<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for comparing generated PDFs with design templates
 */
class PDFVisualComparisonService
{
    /**
     * Compare a generated PDF with design templates
     *
     * @param  string  $pdfPath  Path to the generated PDF
     * @param  string  $templateName  Name of the template to compare with
     * @param  float|null  $threshold  Difference threshold percentage (0-100), null to use config value
     * @return array Comparison results
     */
    public function comparePdfWithDesign(string $pdfPath, string $templateName, ?float $threshold = null): array
    {
        // Get threshold from config if not provided
        if ($threshold === null) {
            $threshold = $this->getThresholdForTemplate($templateName);
        }

        // Convert PDF to images
        $pdfImages = $this->convertPdfToImages($pdfPath);

        // Get design template images
        $designImages = $this->getDesignTemplateImages($templateName);

        // Compare images
        return $this->compareImages($pdfImages, $designImages, $templateName, $threshold);
    }

    /**
     * Get the threshold for a specific template from config
     *
     * @param  string  $templateName  Name of the template
     * @return float Threshold percentage
     */
    private function getThresholdForTemplate(string $templateName): float
    {
        // Get template-specific threshold if available
        $templateThreshold = config("pdf_visual_testing.template_thresholds.{$templateName}");

        // Fall back to default threshold if template-specific one is not set
        if ($templateThreshold === null) {
            return config('pdf_visual_testing.default_threshold', 5.0);
        }

        return (float) $templateThreshold;
    }

    /**
     * Convert a PDF to images for comparison
     *
     * @param  string  $pdfPath  Path to the PDF file
     * @return array Array of image paths
     */
    public function convertPdfToImages(string $pdfPath): array
    {
        // Get the full path to the PDF file
        $fullPdfPath = Storage::disk('public')->path($pdfPath);

        // Create a directory for the images
        $imageDir = storage_path('app/temp/pdf-visual-tests/'.basename($pdfPath, '.pdf'));
        if (! File::exists($imageDir)) {
            File::makeDirectory($imageDir, 0755, true);
        }

        // Convert PDF to images
        $images = [];

        try {
            // Check if the Spatie PDF to Image library is available
            if (class_exists('Spatie\PdfToImage\Pdf')) {
                // Use Spatie's PDF to Image library
                $pdf = new \Spatie\PdfToImage\Pdf($fullPdfPath);
                $pageCount = $pdf->getNumberOfPages();

                for ($i = 1; $i <= $pageCount; $i++) {
                    $imagePath = $imageDir."/page-{$i}.png";
                    $pdf->setPage($i)->saveImage($imagePath);
                    $images[] = $imagePath;
                }
            } else {
                // Fallback to ImageMagick if available
                $pageCount = $this->getPdfPageCount($fullPdfPath);

                for ($i = 0; $i < $pageCount; $i++) {
                    $imagePath = $imageDir.'/page-'.($i + 1).'.png';
                    $command = "convert -density 150 {$fullPdfPath}[{$i}] -quality 100 {$imagePath}";
                    exec($command, $output, $returnCode);

                    if ($returnCode === 0 && File::exists($imagePath)) {
                        $images[] = $imagePath;
                    } else {
                        Log::warning("Failed to convert PDF page {$i} to image", [
                            'pdf_path' => $pdfPath,
                            'return_code' => $returnCode,
                            'output' => $output,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error converting PDF to images', [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);
        }

        return $images;
    }

    /**
     * Get the number of pages in a PDF file
     *
     * @param  string  $pdfPath  Path to the PDF file
     * @return int Number of pages
     */
    private function getPdfPageCount(string $pdfPath): int
    {
        // Try to get page count using pdfinfo if available
        $command = "pdfinfo {$pdfPath} | grep Pages | awk '{print $2}'";
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && ! empty($output)) {
            return (int) $output[0];
        }

        // Fallback method: read the PDF file and count /Page objects
        $content = file_get_contents($pdfPath);
        preg_match_all('/\/Type\s*\/Page[^s]/i', $content, $matches);
        $count = count($matches[0]);

        return $count > 0 ? $count : 1; // Default to 1 if counting fails
    }

    /**
     * Get design template images
     *
     * @param  string  $templateName  Name of the template
     * @return array Array of image paths
     */
    public function getDesignTemplateImages(string $templateName): array
    {
        $designDir = public_path("design/{$templateName}");
        $images = [];

        // Check if the design directory exists
        if (File::exists($designDir)) {
            // Get all image files in the directory
            $files = File::files($designDir);

            // Sort files by name
            usort($files, function ($a, $b) {
                return strnatcmp($a->getFilename(), $b->getFilename());
            });

            // Add file paths to the array
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg'])) {
                    $images[] = $file->getPathname();
                }
            }
        }

        return $images;
    }

    /**
     * Compare generated PDF images with design template images
     *
     * @param  array  $pdfImages  Array of PDF image paths
     * @param  array  $designImages  Array of design image paths
     * @param  string  $templateName  Name of the template
     * @param  float  $threshold  Difference threshold percentage (0-100)
     * @return array Comparison results
     */
    public function compareImages(array $pdfImages, array $designImages, string $templateName, float $threshold = 5.0): array
    {
        $results = [
            'template' => $templateName,
            'pdf_pages' => count($pdfImages),
            'design_pages' => count($designImages),
            'pages_match' => count($pdfImages) === count($designImages),
            'page_results' => [],
            'overall_match' => true,
            'diff_images' => [],
            'timestamp' => now()->toDateTimeString(),
            'threshold' => $threshold,
        ];

        // If page counts don't match, we can't do a proper comparison
        if (! $results['pages_match']) {
            $results['overall_match'] = false;
            $results['error'] = "Page count mismatch: PDF has {$results['pdf_pages']} pages, design has {$results['design_pages']} pages";

            return $results;
        }

        // Create a directory for difference images with timestamp to avoid overwriting
        $timestamp = now()->format('Ymd_His');
        $diffDir = storage_path("app/temp/pdf-visual-tests/diff-{$templateName}-{$timestamp}");
        if (! File::exists($diffDir)) {
            File::makeDirectory($diffDir, 0755, true);
        }

        // Compare each page
        for ($i = 0; $i < count($pdfImages); $i++) {
            $pageResult = [
                'page' => $i + 1,
                'pdf_image' => $pdfImages[$i],
                'design_image' => $designImages[$i],
                'difference' => 0,
                'match' => true,
                'diff_image' => null,
                'comparison_details' => [],
            ];

            try {
                // Generate a difference image
                $diffImagePath = $diffDir.'/diff_page_'.($i + 1).'.png';

                // First try with ImageMagick's compare command
                $command = "compare -metric RMSE {$pdfImages[$i]} {$designImages[$i]} {$diffImagePath} 2>&1";
                exec($command, $output, $returnCode);

                // Parse the output to get the difference percentage
                // ImageMagick returns a value between 0 and 1, multiply by 100 for percentage
                if (! empty($output) && preg_match('/(\d+\.?\d*)/', $output[0], $matches)) {
                    $pageResult['difference'] = floatval($matches[1]) * 100;
                    $pageResult['comparison_details']['method'] = 'ImageMagick RMSE';
                    $pageResult['comparison_details']['raw_output'] = $output[0];
                } else {
                    // Try alternative comparison method with ImageMagick
                    $command = "compare -metric AE {$pdfImages[$i]} {$designImages[$i]} {$diffImagePath} 2>&1";
                    exec($command, $output, $returnCode);

                    if (! empty($output) && is_numeric($output[0])) {
                        // AE returns the number of different pixels
                        // Get image dimensions to calculate percentage
                        $dimensions = $this->getImageDimensions($pdfImages[$i]);
                        if ($dimensions && $dimensions['width'] > 0 && $dimensions['height'] > 0) {
                            $totalPixels = $dimensions['width'] * $dimensions['height'];
                            $differentPixels = floatval($output[0]);
                            $pageResult['difference'] = ($differentPixels / $totalPixels) * 100;
                            $pageResult['comparison_details']['method'] = 'ImageMagick AE';
                            $pageResult['comparison_details']['raw_output'] = $output[0];
                            $pageResult['comparison_details']['total_pixels'] = $totalPixels;
                            $pageResult['comparison_details']['different_pixels'] = $differentPixels;
                        } else {
                            // If we couldn't get dimensions, assume a high difference
                            $pageResult['difference'] = 100;
                            $pageResult['comparison_details']['method'] = 'Failed to get image dimensions';
                        }
                    } else {
                        // If all comparison methods fail, assume a high difference
                        $pageResult['difference'] = 100;
                        $pageResult['comparison_details']['method'] = 'Comparison failed';
                        $pageResult['comparison_details']['error'] = $output[0] ?? 'Unknown error';
                    }
                }

                // Check if the difference is below the threshold
                $pageResult['match'] = $pageResult['difference'] < $threshold;

                // If the diff image was created, include it in the results
                if (File::exists($diffImagePath)) {
                    $pageResult['diff_image'] = $diffImagePath;
                    $results['diff_images'][] = $diffImagePath;

                    // Analyze the difference image to identify areas with most differences
                    $pageResult['comparison_details']['hotspots'] = $this->analyzeHotspots($diffImagePath);
                }
            } catch (\Exception $e) {
                Log::error("Error comparing images for page {$i}", [
                    'template' => $templateName,
                    'error' => $e->getMessage(),
                ]);

                $pageResult['match'] = false;
                $pageResult['error'] = $e->getMessage();
            }

            // Add page result to overall results
            $results['page_results'][] = $pageResult;

            // Update overall match status
            if (! $pageResult['match']) {
                $results['overall_match'] = false;
            }
        }

        // Calculate overall statistics
        $results['average_difference'] = $this->calculateAverageDifference($results);
        $results['max_difference'] = $this->calculateMaxDifference($results);
        $results['min_difference'] = $this->calculateMinDifference($results);

        return $results;
    }

    /**
     * Get image dimensions
     *
     * @param  string  $imagePath  Path to the image
     * @return array|null Array with width and height, or null if failed
     */
    private function getImageDimensions(string $imagePath): ?array
    {
        try {
            // Try to get dimensions using ImageMagick
            $command = "identify -format \"%w %h\" {$imagePath}";
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && ! empty($output)) {
                [$width, $height] = explode(' ', $output[0]);

                return [
                    'width' => (int) $width,
                    'height' => (int) $height,
                ];
            }

            // Fallback to PHP's getimagesize
            $dimensions = getimagesize($imagePath);
            if ($dimensions) {
                return [
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error getting image dimensions', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Analyze hotspots in difference image
     *
     * @param  string  $diffImagePath  Path to the difference image
     * @return array Array of hotspot regions
     */
    private function analyzeHotspots(string $diffImagePath): array
    {
        $hotspots = [];

        try {
            // Use ImageMagick to identify areas with significant differences
            $command = "convert {$diffImagePath} -threshold 25% -define connected-components:verbose=true ".
                       '-define connected-components:area-threshold=100 -connected-components 8 null: 2>&1';
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && ! empty($output)) {
                // Parse the output to find regions with differences
                $regions = [];
                foreach ($output as $line) {
                    if (preg_match('/(\d+): (\d+)x(\d+)\+(\d+)\+(\d+)/', $line, $matches)) {
                        $regions[] = [
                            'width' => (int) $matches[2],
                            'height' => (int) $matches[3],
                            'x' => (int) $matches[4],
                            'y' => (int) $matches[5],
                            'area' => (int) $matches[2] * (int) $matches[3],
                        ];
                    }
                }

                // Sort regions by area (largest first) and take top 5
                usort($regions, function ($a, $b) {
                    return $b['area'] - $a['area'];
                });

                $hotspots = array_slice($regions, 0, 5);
            }
        } catch (\Exception $e) {
            Log::error('Error analyzing hotspots', [
                'diff_image' => $diffImagePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $hotspots;
    }

    /**
     * Calculate average difference from comparison results
     *
     * @param  array  $results  Comparison results
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
     * Calculate maximum difference from comparison results
     *
     * @param  array  $results  Comparison results
     * @return float Maximum difference percentage
     */
    private function calculateMaxDifference(array $results): float
    {
        if (empty($results['page_results'])) {
            return 0;
        }

        $max = 0;
        foreach ($results['page_results'] as $pageResult) {
            if ($pageResult['difference'] > $max) {
                $max = $pageResult['difference'];
            }
        }

        return $max;
    }

    /**
     * Calculate minimum difference from comparison results
     *
     * @param  array  $results  Comparison results
     * @return float Minimum difference percentage
     */
    private function calculateMinDifference(array $results): float
    {
        if (empty($results['page_results'])) {
            return 0;
        }

        $min = 100;
        foreach ($results['page_results'] as $pageResult) {
            if ($pageResult['difference'] < $min) {
                $min = $pageResult['difference'];
            }
        }

        return $min;
    }

    /**
     * Generate a visual report of the comparison results
     *
     * @param  array  $results  Comparison results from compareImages
     * @param  string|null  $reportName  Custom report name (optional)
     * @return string Path to the HTML report
     */
    public function generateVisualReport(array $results, ?string $reportName = null): string
    {
        $reportDir = storage_path('app/temp/pdf-visual-tests/reports');
        if (! File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0755, true);
        }

        // Include timestamp in report name to avoid overwriting
        $timestamp = now()->format('Ymd_His');

        // Use custom report name if provided, otherwise use template name
        $baseReportName = $reportName ?? $results['template'];
        $reportPath = $reportDir.'/'.$baseReportName.'_report_'.$timestamp.'.html';

        // Generate HTML report
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PDF Visual Comparison Report - '.$results['template'].'</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .summary { margin-bottom: 20px; padding: 15px; background-color: #f5f5f5; border-radius: 5px; }
        .page { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .page-header { margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #ddd; }
        .images { display: flex; flex-wrap: wrap; }
        .image-container { margin-right: 20px; margin-bottom: 20px; }
        .image-container img { max-width: 100%; height: auto; border: 1px solid #ddd; }
        .match { color: green; font-weight: bold; }
        .no-match { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .stats { display: flex; flex-wrap: wrap; margin-bottom: 15px; }
        .stat-box { background-color: #eef; padding: 10px; margin-right: 15px; margin-bottom: 10px; border-radius: 5px; min-width: 150px; }
        .hotspots { margin-top: 15px; }
        .hotspot { background-color: #fff0f0; padding: 8px; margin-bottom: 8px; border-radius: 4px; }
        .comparison-details { background-color: #f9f9f9; padding: 10px; margin-top: 10px; border-radius: 5px; font-family: monospace; font-size: 0.9em; }
        .tabs { display: flex; margin-bottom: 10px; }
        .tab { padding: 8px 16px; cursor: pointer; border: 1px solid #ddd; border-bottom: none; border-radius: 5px 5px 0 0; margin-right: 5px; }
        .tab.active { background-color: #f5f5f5; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .progress-bar-container { width: 100%; background-color: #f0f0f0; height: 20px; border-radius: 10px; margin-bottom: 10px; }
        .progress-bar { height: 100%; border-radius: 10px; text-align: center; line-height: 20px; color: white; }
        .progress-low { background-color: #4CAF50; }
        .progress-medium { background-color: #FFC107; }
        .progress-high { background-color: #F44336; }
    </style>
    <script>
        function showTab(tabId, element) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Deactivate all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add("active");
            
            // Activate the clicked tab
            element.classList.add("active");
        }
    </script>
</head>
<body>
    <h1>PDF Visual Comparison Report - '.$results['template'].'</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="stats">
            <div class="stat-box">
                <strong>Template:</strong> '.$results['template'].'<br>
                <strong>Generated:</strong> '.($results['timestamp'] ?? now()->toDateTimeString()).'<br>
                <strong>Threshold:</strong> '.($results['threshold'] ?? '5.0').'%
            </div>
            <div class="stat-box">
                <strong>PDF Pages:</strong> '.$results['pdf_pages'].'<br>
                <strong>Design Pages:</strong> '.$results['design_pages'].'<br>
                <strong>Pages Match:</strong> '.($results['pages_match'] ? '<span class="match">Yes</span>' : '<span class="no-match">No</span>').'
            </div>
            <div class="stat-box">
                <strong>Overall Match:</strong> '.($results['overall_match'] ? '<span class="match">Yes</span>' : '<span class="no-match">No</span>').'<br>';

        // Add average, min, max difference if available
        if (isset($results['average_difference'])) {
            $html .= '<strong>Average Difference:</strong> '.number_format($results['average_difference'], 2).'%<br>';
        }
        if (isset($results['min_difference'])) {
            $html .= '<strong>Min Difference:</strong> '.number_format($results['min_difference'], 2).'%<br>';
        }
        if (isset($results['max_difference'])) {
            $html .= '<strong>Max Difference:</strong> '.number_format($results['max_difference'], 2).'%';
        }

        $html .= '
            </div>
        </div>';

        // Add error message if present
        if (! empty($results['error'])) {
            $html .= '
        <div class="error-message" style="background-color: #ffeeee; padding: 10px; border-radius: 5px; color: red;">
            <strong>Error:</strong> '.$results['error'].'
        </div>';
        }

        // Add page summary
        $html .= '
        <h3>Page Summary</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #eee;">
                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Page</th>
                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Difference</th>
                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Status</th>
                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Visual</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($results['page_results'] as $pageResult) {
            // Determine color class based on difference percentage
            $colorClass = 'progress-low';
            if ($pageResult['difference'] > ($results['threshold'] ?? 5.0) * 0.8) {
                $colorClass = 'progress-high';
            } elseif ($pageResult['difference'] > ($results['threshold'] ?? 5.0) * 0.5) {
                $colorClass = 'progress-medium';
            }

            $html .= '
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd;">Page '.$pageResult['page'].'</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">
                        <div class="progress-bar-container">
                            <div class="progress-bar '.$colorClass.'" style="width: '.min(100, $pageResult['difference'] * 2).'%">
                                '.number_format($pageResult['difference'], 2).'%
                            </div>
                        </div>
                    </td>
                    <td style="padding: 8px; border: 1px solid #ddd;">
                        '.($pageResult['match'] ? '<span class="match">Pass</span>' : '<span class="no-match">Fail</span>').'
                    </td>
                    <td style="padding: 8px; border: 1px solid #ddd;">
                        <a href="#page-'.$pageResult['page'].'">View Details</a>
                    </td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>
    </div>';

        // Add page results
        foreach ($results['page_results'] as $pageResult) {
            $html .= '
    <div id="page-'.$pageResult['page'].'" class="page">
        <div class="page-header">
            <h2>Page '.$pageResult['page'].'</h2>
            <div class="stats">
                <div class="stat-box">
                    <strong>Difference:</strong> '.number_format($pageResult['difference'], 2).'%<br>
                    <strong>Status:</strong> '.($pageResult['match'] ? '<span class="match">Pass</span>' : '<span class="no-match">Fail</span>').'
                </div>';

            // Add comparison method if available
            if (! empty($pageResult['comparison_details']['method'])) {
                $html .= '
                <div class="stat-box">
                    <strong>Comparison Method:</strong> '.$pageResult['comparison_details']['method'].'<br>';

                if (! empty($pageResult['comparison_details']['total_pixels'])) {
                    $html .= '
                    <strong>Total Pixels:</strong> '.number_format($pageResult['comparison_details']['total_pixels']).'<br>
                    <strong>Different Pixels:</strong> '.number_format($pageResult['comparison_details']['different_pixels']).'';
                }

                $html .= '
                </div>';
            }

            $html .= '
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab(\'images-'.$pageResult['page'].'\', this)">Images</div>';

            // Add hotspots tab if available
            if (! empty($pageResult['comparison_details']['hotspots'])) {
                $html .= '
            <div class="tab" onclick="showTab(\'hotspots-'.$pageResult['page'].'\', this)">Hotspots</div>';
            }

            // Add details tab if available
            if (! empty($pageResult['comparison_details'])) {
                $html .= '
            <div class="tab" onclick="showTab(\'details-'.$pageResult['page'].'\', this)">Technical Details</div>';
            }

            $html .= '
        </div>
        
        <div id="images-'.$pageResult['page'].'" class="tab-content active">
            <div class="images">
                <div class="image-container">
                    <h3>PDF Image</h3>
                    <img src="file://'.$pageResult['pdf_image'].'" alt="PDF Page '.$pageResult['page'].'">
                </div>
                
                <div class="image-container">
                    <h3>Design Template</h3>
                    <img src="file://'.$pageResult['design_image'].'" alt="Design Page '.$pageResult['page'].'">
                </div>';

            if (! empty($pageResult['diff_image'])) {
                $html .= '
                <div class="image-container">
                    <h3>Difference</h3>
                    <img src="file://'.$pageResult['diff_image'].'" alt="Difference Page '.$pageResult['page'].'">
                </div>';
            }

            $html .= '
            </div>
        </div>';

            // Add hotspots tab content if available
            if (! empty($pageResult['comparison_details']['hotspots'])) {
                $html .= '
        <div id="hotspots-'.$pageResult['page'].'" class="tab-content">
            <h3>Areas with Significant Differences</h3>
            <div class="hotspots">';

                foreach ($pageResult['comparison_details']['hotspots'] as $index => $hotspot) {
                    $html .= '
                <div class="hotspot">
                    <strong>Hotspot #'.($index + 1).':</strong> 
                    Width: '.$hotspot['width'].'px, 
                    Height: '.$hotspot['height'].'px, 
                    Position: X='.$hotspot['x'].', Y='.$hotspot['y'].', 
                    Area: '.number_format($hotspot['area']).' pixels
                </div>';
                }

                $html .= '
            </div>
        </div>';
            }

            // Add technical details tab content if available
            if (! empty($pageResult['comparison_details'])) {
                $html .= '
        <div id="details-'.$pageResult['page'].'" class="tab-content">
            <h3>Technical Details</h3>
            <div class="comparison-details">
                <pre>'.json_encode($pageResult['comparison_details'], JSON_PRETTY_PRINT).'</pre>
            </div>
        </div>';
            }

            $html .= '
    </div>';
        }

        $html .= '
</body>
</html>';

        // Write the HTML report to file
        File::put($reportPath, $html);

        return $reportPath;
    }
}
