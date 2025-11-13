<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDFLoggingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class PDFGenerationIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    protected $pdfLoggingService;
    protected $pdfGeneratorService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the PDFLoggingService
        $this->pdfLoggingService = Mockery::mock(PDFLoggingService::class);
        $this->pdfLoggingService->shouldReceive('logInfo')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logDebug')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logError')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logPerformance')->withAnyArgs()->andReturnNull();
        
        // Create the service with the mock logger
        $this->pdfGeneratorService = new PDFGeneratorService($this->pdfLoggingService);
        
        // Set up storage for tests
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('applications');
        
        // Clear cache
        Cache::flush();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test end-to-end PDF generation flow for ZB Account Opening
     */
    public function test_zb_account_opening_pdf_generation_flow(): void
    {
        // Create application state with test data
        $applicationState = $this->createZBAccountOpeningTestData();
        $sessionId = $applicationState->session_id;
        
        // Save to database
        $applicationState->save();
        
        // Test PDF download endpoint
        $response = $this->get("/application/{$sessionId}/pdf/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=');
        
        // Test PDF view endpoint
        $response = $this->get("/application/{$sessionId}/pdf/view");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=');
        
        // Test PDF regeneration endpoint
        $response = $this->post("/application/{$sessionId}/pdf/regenerate", [
            'force' => true,
            'reason' => 'Testing regeneration'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'path',
            'generated_at',
            'download_url',
            'view_url'
        ]);
        
        // Verify that the PDF was generated and stored
        $responseData = $response->json();
        $this->assertTrue(Storage::disk('public')->exists($responseData['path']));
    }
    
    /**
     * Test end-to-end PDF generation flow for SSB Form
     */
    public function test_ssb_form_pdf_generation_flow(): void
    {
        // Create application state with test data
        $applicationState = $this->createSSBFormTestData();
        $sessionId = $applicationState->session_id;
        
        // Save to database
        $applicationState->save();
        
        // Test PDF download endpoint
        $response = $this->get("/application/{$sessionId}/pdf/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=');
        
        // Test PDF view endpoint
        $response = $this->get("/application/{$sessionId}/pdf/view");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=');
        
        // Test PDF regeneration endpoint
        $response = $this->post("/application/{$sessionId}/pdf/regenerate", [
            'force' => true,
            'reason' => 'Testing regeneration'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'path',
            'generated_at',
            'download_url',
            'view_url'
        ]);
        
        // Verify that the PDF was generated and stored
        $responseData = $response->json();
        $this->assertTrue(Storage::disk('public')->exists($responseData['path']));
    }
    
    /**
     * Test end-to-end PDF generation flow for SME Account Opening
     */
    public function test_sme_account_opening_pdf_generation_flow(): void
    {
        // Create application state with test data
        $applicationState = $this->createSMEAccountOpeningTestData();
        $sessionId = $applicationState->session_id;
        
        // Save to database
        $applicationState->save();
        
        // Test PDF download endpoint
        $response = $this->get("/application/{$sessionId}/pdf/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=');
        
        // Test PDF view endpoint
        $response = $this->get("/application/{$sessionId}/pdf/view");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=');
        
        // Test PDF regeneration endpoint
        $response = $this->post("/application/{$sessionId}/pdf/regenerate", [
            'force' => true,
            'reason' => 'Testing regeneration'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'path',
            'generated_at',
            'download_url',
            'view_url'
        ]);
        
        // Verify that the PDF was generated and stored
        $responseData = $response->json();
        $this->assertTrue(Storage::disk('public')->exists($responseData['path']));
    }
    
    /**
     * Test end-to-end PDF generation flow for Account Holders
     */
    public function test_account_holders_pdf_generation_flow(): void
    {
        // Create application state with test data
        $applicationState = $this->createAccountHoldersTestData();
        $sessionId = $applicationState->session_id;
        
        // Save to database
        $applicationState->save();
        
        // Test PDF download endpoint
        $response = $this->get("/application/{$sessionId}/pdf/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=');
        
        // Test PDF view endpoint
        $response = $this->get("/application/{$sessionId}/pdf/view");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=');
        
        // Test PDF regeneration endpoint
        $response = $this->post("/application/{$sessionId}/pdf/regenerate", [
            'force' => true,
            'reason' => 'Testing regeneration'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'path',
            'generated_at',
            'download_url',
            'view_url'
        ]);
        
        // Verify that the PDF was generated and stored
        $responseData = $response->json();
        $this->assertTrue(Storage::disk('public')->exists($responseData['path']));
    }
    
    /**
     * Test batch PDF generation flow
     */
    public function test_batch_pdf_generation_flow(): void
    {
        // Create multiple application states with test data
        $applicationStates = [
            $this->createZBAccountOpeningTestData(),
            $this->createSSBFormTestData(),
            $this->createSMEAccountOpeningTestData(),
            $this->createAccountHoldersTestData()
        ];
        
        // Save to database
        foreach ($applicationStates as $state) {
            $state->save();
        }
        
        // Get session IDs
        $sessionIds = array_map(function($state) {
            return $state->session_id;
        }, $applicationStates);
        
        // Test batch PDF download endpoint
        $response = $this->post("/application/pdf/batch-download", [
            'session_ids' => $sessionIds,
            'include_metadata' => true,
            'batch_name' => 'test_batch'
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition', 'attachment; filename=test_batch');
    }
    
    /**
     * Test PDF generation with incomplete application data
     */
    public function test_pdf_generation_with_incomplete_data(): void
    {
        // Create application state with incomplete data
        $applicationState = new ApplicationState([
            'session_id' => 'test-incomplete-' . uniqid(),
            'current_step' => 'personal_details', // Not completed
            'channel' => 'web',
            'user_identifier' => 'incomplete-user@example.com',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formResponses' => [
                    'firstName' => 'John',
                    'surname' => 'Doe'
                    // Missing many required fields
                ]
            ]
        ]);
        
        // Save to database
        $applicationState->save();
        $sessionId = $applicationState->session_id;
        
        // Test PDF download endpoint - should fail with 400 Bad Request
        $response = $this->get("/application/{$sessionId}/pdf/download");
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'message',
            'code'
        ]);
        
        // Test PDF view endpoint - should fail with 400 Bad Request
        $response = $this->get("/application/{$sessionId}/pdf/view");
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'message',
            'code'
        ]);
    }
    
    /**
     * Test PDF generation with non-existent application
     */
    public function test_pdf_generation_with_non_existent_application(): void
    {
        $nonExistentSessionId = 'non-existent-session-id';
        
        // Test PDF download endpoint - should fail with 404 Not Found
        $response = $this->get("/application/{$nonExistentSessionId}/pdf/download");
        $response->assertStatus(404);
        $response->assertJsonStructure([
            'error',
            'message',
            'code'
        ]);
        
        // Test PDF view endpoint - should fail with 404 Not Found
        $response = $this->get("/application/{$nonExistentSessionId}/pdf/view");
        $response->assertStatus(404);
        $response->assertJsonStructure([
            'error',
            'message',
            'code'
        ]);
    }
    
    /**
     * Test PDF caching mechanism
     */
    public function test_pdf_caching_mechanism(): void
    {
        // Create application state with test data
        $applicationState = $this->createZBAccountOpeningTestData();
        $sessionId = $applicationState->session_id;
        
        // Save to database
        $applicationState->save();
        
        // First request should generate the PDF
        $response1 = $this->get("/application/{$sessionId}/pdf/download");
        $response1->assertStatus(200);
        
        // Get the generated PDF path from the application state
        $applicationState = ApplicationState::find($sessionId);
        $formData = $applicationState->form_data;
        $pdfPath = $formData['pdfPath'];
        
        // Modify the PDF file to simulate a change
        Storage::disk('public')->put($pdfPath, 'Modified content');
        
        // Second request should use the cached path and return the modified file
        $response2 = $this->get("/application/{$sessionId}/pdf/download");
        $response2->assertStatus(200);
        $this->assertEquals('Modified content', $response2->getContent());
        
        // Force regeneration
        $response3 = $this->post("/application/{$sessionId}/pdf/regenerate", [
            'force' => true,
            'reason' => 'Testing cache invalidation'
        ]);
        $response3->assertStatus(200);
        
        // Get the new PDF path
        $applicationState = ApplicationState::find($sessionId);
        $formData = $applicationState->form_data;
        $newPdfPath = $formData['pdfPath'];
        
        // Verify that a new PDF was generated
        $this->assertNotEquals($pdfPath, $newPdfPath);
        
        // Third request should use the new PDF
        $response4 = $this->get("/application/{$sessionId}/pdf/download");
        $response4->assertStatus(200);
        $this->assertNotEquals('Modified content', $response4->getContent());
    }
    
    /**
     * Create test data for ZB account opening
     * 
     * @return ApplicationState
     */
    private function createZBAccountOpeningTestData(): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-zb-account-' . uniqid(),
            'current_step' => 'completed',
            'channel' => 'web',
            'user_identifier' => 'test-user@example.com',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => false,
                'formId' => 'individual_account_opening.json',
                'formResponses' => [
                    'firstName' => 'John',
                    'surname' => 'Doe',
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => 'john.doe@example.com',
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
     * Create test data for SSB form
     * 
     * @return ApplicationState
     */
    private function createSSBFormTestData(): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-ssb-form-' . uniqid(),
            'current_step' => 'completed',
            'channel' => 'web',
            'user_identifier' => 'test-user@example.com',
            'form_data' => [
                'employer' => 'goz-ssb',
                'hasAccount' => false,
                'formId' => 'ssb_account_opening_form.json',
                'formResponses' => [
                    'firstName' => 'John',
                    'surname' => 'Doe',
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => 'john.doe@example.com',
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
     * Create test data for SME account opening
     * 
     * @return ApplicationState
     */
    private function createSMEAccountOpeningTestData(): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-sme-account-' . uniqid(),
            'current_step' => 'completed',
            'channel' => 'web',
            'user_identifier' => 'business@example.com',
            'form_data' => [
                'employer' => 'entrepreneur',
                'hasAccount' => false,
                'formId' => 'smes_business_account_opening.json',
                'formResponses' => [
                    'businessName' => 'Test Business',
                    'tradingName' => 'Test Business Trading',
                    'registrationNumber' => 'REG' . rand(10000, 99999),
                    'businessType' => 'Private Limited Company',
                    'dateOfIncorporation' => '2010-01-01',
                    'natureOfBusiness' => 'Technology Services',
                    'physicalAddress' => '456 Business Park, Harare',
                    'postalAddress' => 'P.O. Box 789, Harare',
                    'contactPerson' => 'Jane Manager',
                    'position' => 'General Manager',
                    'telephone' => '0772345678',
                    'email' => 'info@testbusiness.com',
                    'annualTurnover' => '500000',
                    'numberOfEmployees' => '15',
                    'accountCurrency' => 'USD'
                ]
            ]
        ]);
    }
    
    /**
     * Create test data for account holders
     * 
     * @return ApplicationState
     */
    private function createAccountHoldersTestData(): ApplicationState
    {
        return new ApplicationState([
            'session_id' => 'test-account-holders-' . uniqid(),
            'current_step' => 'completed',
            'channel' => 'web',
            'user_identifier' => 'account-holder@example.com',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formId' => 'account_holder_loan_application.json',
                'formResponses' => [
                    'firstName' => 'John',
                    'surname' => 'Doe',
                    'title' => 'Mr',
                    'gender' => 'Male',
                    'dateOfBirth' => '1980-01-01',
                    'nationalIdNumber' => '12-345678-A-90',
                    'emailAddress' => 'john.doe@example.com',
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
}