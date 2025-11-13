<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:ssb-pdf', function () {
    $this->info('Testing SSB PDF Generation...');
    
    try {
        // Create test application state
        $testState = new \App\Models\ApplicationState();
        $testState->id = 9999;
        $testState->session_id = 'test-ssb-pdf-' . time();
        $testState->reference_code = 'SSB-TEST-' . time();
        $testState->form_data = [
            'employer' => 'goz-ssb',
            'formResponses' => [
                'title' => 'Mr',
                'firstName' => 'John',
                'surname' => 'Doe',
                'spouseDetails' => []
            ],
            'monthlyPayment' => '200'
        ];
        
        $this->info('Creating test application state...');
        $this->line('Session ID: ' . $testState->session_id);
        
        $pdfGenerator = app(\App\Services\PDFGeneratorService::class);
        $this->info('Generating PDF...');
        
        $pdfContent = $pdfGenerator->generatePDF($testState);
        $this->info('PDF generated successfully!');
        $this->line('PDF size: ' . strlen($pdfContent) . ' bytes');
        
        // Save to storage for testing
        $filename = 'test_ssb_' . time() . '.pdf';
        $filepath = 'applications/' . $filename;
        \Storage::disk('public')->put($filepath, $pdfContent);
        
        $this->info('PDF saved to: ' . $filepath);
        $this->line('Download URL: ' . url('/storage/' . $filepath));
        
        return 0;
        
    } catch (\Exception $e) {
        $this->error('PDF generation failed!');
        $this->error('Error: ' . $e->getMessage());
        $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        return 1;
    }
})->purpose('Test SSB PDF generation');
