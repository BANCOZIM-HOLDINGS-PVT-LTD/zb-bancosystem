<?php

namespace App\Console\Commands;

use App\Services\StateManager;
use Illuminate\Console\Command;

class TestWhatsAppFlow extends Command
{
    protected $signature = 'test:whatsapp-flow';

    protected $description = 'Test the complete WhatsApp application flow';

    public function handle()
    {
        $this->info('Testing WhatsApp Application Flow...');

        // Create a test application state
        $stateManager = new StateManager;
        $testPhone = '+1234567890';
        $sessionId = 'test_'.time();

        $this->info("Creating test application with session ID: {$sessionId}");

        // Step 1: Language Selection
        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'language',
            ['language' => 'en'],
            ['phone_number' => $testPhone, 'started_at' => now()]
        );
        $this->info('✓ Language selection saved');

        // Step 2: Intent Selection
        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'intent',
            ['language' => 'en', 'intent' => 'hirePurchase'],
            $state->metadata ?? []
        );
        $this->info('✓ Intent selection saved');

        // Step 3: Employer Selection
        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'employer',
            ['language' => 'en', 'intent' => 'hirePurchase', 'employer' => 'entrepreneur'],
            $state->metadata ?? []
        );
        $this->info('✓ Employer selection saved');

        // Step 4: Account Verification
        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'account',
            ['language' => 'en', 'intent' => 'hirePurchase', 'employer' => 'entrepreneur', 'hasAccount' => true],
            $state->metadata ?? []
        );
        $this->info('✓ Account verification saved');

        // Step 5: Product Selection
        $productData = [
            'language' => 'en',
            'intent' => 'hirePurchase',
            'employer' => 'entrepreneur',
            'hasAccount' => true,
            'selectedCategory' => ['id' => 'agriculture', 'name' => 'Agriculture'],
            'selectedBusiness' => ['name' => 'Cotton', 'basePrice' => 800],
            'selectedScale' => ['name' => '2 Ha', 'multiplier' => 2],
            'finalPrice' => 1600,
            'productSelectionComplete' => true,
        ];

        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'form',
            $productData,
            $state->metadata ?? []
        );
        $this->info('✓ Product selection saved');

        // Step 6: Form Filling
        $formData = array_merge($productData, [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '+263771234567',
                'email' => 'john.doe@example.com',
                'address' => '123 Main Street, Harare',
                'businessName' => 'Doe Farming',
                'businessType' => 'Agriculture',
                'yearsInBusiness' => '5',
                'monthlyIncome' => '2000',
            ],
            'applicationComplete' => true,
            'completedAt' => now()->toISOString(),
        ]);

        $state = $stateManager->saveState(
            $sessionId,
            'whatsapp',
            $testPhone,
            'completed',
            $formData,
            $state->metadata ?? []
        );
        $this->info('✓ Form completion saved');

        // Test PDF Generation
        $this->info('Testing PDF generation...');
        try {
            $pdfGenerator = new \App\Services\PDFGeneratorService;
            $pdfPath = $pdfGenerator->generateApplicationPDF($state);
            $this->info("✓ PDF generated successfully: {$pdfPath}");
        } catch (\Exception $e) {
            $this->error('✗ PDF generation failed: '.$e->getMessage());
        }

        // Test Resume Code Generation
        $this->info('Testing resume code generation...');
        try {
            $resumeCode = $stateManager->generateResumeCode($sessionId);
            $this->info("✓ Resume code generated: {$resumeCode}");

            // Test resume code retrieval
            $retrievedState = $stateManager->getStateByResumeCode($resumeCode);
            if ($retrievedState && $retrievedState->session_id === $sessionId) {
                $this->info('✓ Resume code retrieval works');
            } else {
                $this->error('✗ Resume code retrieval failed');
            }
        } catch (\Exception $e) {
            $this->error('✗ Resume code generation failed: '.$e->getMessage());
        }

        $this->info("\nTest Summary:");
        $this->info("Session ID: {$sessionId}");
        $this->info('Application completed successfully!');
        $this->info('You can view this application in the Filament admin panel.');
        $this->info('Admin URL: '.config('app.url').'/admin/applications');

        return 0;
    }
}
