<?php
// Quick SSB PDF Performance Test

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

echo "========================================\n";
echo "SSB PDF Performance Test\n";
echo "========================================\n\n";

// Test data
$formData = [
    'formResponses' => [
        'firstName' => 'John',
        'surname' => 'Doe',
        'nationalIdNumber' => '63-123456-A-01',
        'mobile' => '+263771234567',
        'emailAddress' => 'john@example.com',
        'employerName' => 'Test Employer',
        'loanAmount' => '1200.00',
        'loanTenure' => '12',
        'spouseDetails' => []
    ],
    'monthlyPayment' => '110.00'
];

echo "Starting PDF generation...\n";
$startTime = microtime(true);

try {
    $pdf = Pdf::loadView('forms.ssb_form_pdf', $formData);
    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions([
        'isRemoteEnabled' => false,
        'isHtml5ParserEnabled' => true,
        'isFontSubsettingEnabled' => false,
        'debugKeepTemp' => false,
        'debugCss' => false,
        'debugLayout' => false,
    ]);

    $pdfContent = $pdf->output();
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    echo "✓ PDF generated successfully!\n";
    echo "  Generation time: {$duration} seconds\n";
    echo "  PDF size: " . number_format(strlen($pdfContent)) . " bytes\n";

    // Save test PDF
    $testPath = storage_path('app/ssb_performance_test.pdf');
    file_put_contents($testPath, $pdfContent);
    echo "✓ Test PDF saved: $testPath\n\n";

    if ($duration < 5) {
        echo "✓ EXCELLENT: PDF generated in under 5 seconds!\n";
    } elseif ($duration < 10) {
        echo "✓ GOOD: PDF generated in under 10 seconds\n";
    } else {
        echo "⚠ SLOW: PDF took over 10 seconds. Check for issues.\n";
    }

    echo "\n========================================\n";
    echo "Test completed successfully!\n";
    echo "========================================\n";

} catch (Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    echo "✗ ERROR after {$duration} seconds:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\n  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

