<?php

namespace Tests\Unit\Services;

use App\Exceptions\PDF\PDFIncompleteDataException;
use App\Exceptions\PDF\PDFGenerationException;
use App\Exceptions\PDF\PDFStorageException;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDFLoggingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class PDFGeneratorServiceTest extends TestCase
{
    protected $pdfLoggingService;
    protected $pdfGeneratorService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the PDFLoggingService
        $this->pdfLoggingService = Mockery::mock(PDFLoggingService::class);
        
        // Create the service with the mock logger
        $this->pdfGeneratorService = new PDFGeneratorService($this->pdfLoggingService);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test template selection logic for SSB form
     */
    public function test_determine_template_selects_ssb_form_for_goz_ssb_employer()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'determineTemplate');
        $reflectionMethod->setAccessible(true);
        
        $template = $reflectionMethod->invoke($this->pdfGeneratorService, 'goz-ssb', false);
        
        $this->assertEquals('forms.ssb_form_pdf', $template);
    }
    
    /**
     * Test template selection logic for SME account opening
     */
    public function test_determine_template_selects_sme_account_opening_for_entrepreneur()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'determineTemplate');
        $reflectionMethod->setAccessible(true);
        
        $template = $reflectionMethod->invoke($this->pdfGeneratorService, 'entrepreneur', false);
        
        $this->assertEquals('forms.sme_account_opening_pdf', $template);
    }
    
    /**
     * Test template selection logic for ZB account opening
     */
    public function test_determine_template_selects_zb_account_opening_for_no_account()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'determineTemplate');
        $reflectionMethod->setAccessible(true);
        
        $template = $reflectionMethod->invoke($this->pdfGeneratorService, 'some-employer', false);
        
        $this->assertEquals('forms.zb_account_opening_pdf', $template);
    }
    
    /**
     * Test template selection logic for account holders
     */
    public function test_determine_template_selects_account_holders_for_existing_account()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'determineTemplate');
        $reflectionMethod->setAccessible(true);
        
        $template = $reflectionMethod->invoke($this->pdfGeneratorService, 'some-employer', true);
        
        $this->assertEquals('forms.account_holders_pdf', $template);
    }
    
    /**
     * Test that generateApplicationPDF throws PDFIncompleteDataException when form data is empty
     */
    public function test_generate_application_pdf_throws_exception_when_form_data_is_empty()
    {
        // Create application state with empty form data
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'current_step' => 'completed',
            'form_data' => []
        ]);
        
        // Set up logger expectations
        $this->pdfLoggingService->shouldReceive('logInfo')
            ->once()
            ->with('Starting PDF generation', Mockery::any());
            
        $this->pdfLoggingService->shouldReceive('logError')
            ->once()
            ->with('Application data is missing or incomplete', Mockery::any());
        
        // Expect exception
        $this->expectException(PDFIncompleteDataException::class);
        $this->expectExceptionMessage('Application data is missing or incomplete');
        
        // Call the method
        $this->pdfGeneratorService->generateApplicationPDF($applicationState);
    }
    
    /**
     * Test that generateApplicationPDF throws PDFIncompleteDataException when form responses are missing
     */
    public function test_generate_application_pdf_throws_exception_when_form_responses_missing()
    {
        // Create application state with form data but no form responses
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true
                // Missing formResponses
            ]
        ]);
        
        // Set up logger expectations
        $this->pdfLoggingService->shouldReceive('logInfo')
            ->once()
            ->with('Starting PDF generation', Mockery::any());
            
        $this->pdfLoggingService->shouldReceive('logError')
            ->once()
            ->with('Form responses are missing', Mockery::any());
        
        // Expect exception
        $this->expectException(PDFIncompleteDataException::class);
        $this->expectExceptionMessage('Form responses are missing');
        
        // Call the method
        $this->pdfGeneratorService->generateApplicationPDF($applicationState);
    }
    
    /**
     * Test successful PDF generation
     */
    public function test_generate_application_pdf_success()
    {
        // Mock Storage facade
        Storage::fake('public');
        
        // Create a mock PDF instance
        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('loadView')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('setOptions')->andReturnSelf();
        $pdfMock->shouldReceive('output')->andReturn('PDF content');
        
        // Mock the DomPDF instance
        $domPdfMock = Mockery::mock('Dompdf\Dompdf');
        $canvasMock = Mockery::mock('Dompdf\Canvas');
        
        $domPdfMock->shouldReceive('get_canvas')->andReturn($canvasMock);
        $pdfMock->shouldReceive('getDomPDF')->andReturn($domPdfMock);
        
        // Mock the canvas add_info method
        $canvasMock->shouldReceive('add_info')->withAnyArgs()->andReturnNull();
        
        // Replace the PDF facade with our mock
        Pdf::shouldReceive('loadView')->andReturn($pdfMock);
        
        // Create application state with valid form data
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com'
                ],
                'formId' => 'account_holder_loan_application.json'
            ]
        ]);
        
        // Set up logger expectations
        $this->pdfLoggingService->shouldReceive('logInfo')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logDebug')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logPerformance')->withAnyArgs()->andReturnNull();
        
        // Ensure the applications directory exists check
        Storage::disk('public')->shouldReceive('exists')
            ->with('applications')
            ->andReturn(false);
            
        Storage::disk('public')->shouldReceive('makeDirectory')
            ->with('applications')
            ->andReturn(true);
        
        // Expect the PDF to be stored
        Storage::disk('public')->shouldReceive('put')
            ->withArgs(function ($path, $content) {
                return strpos($path, 'applications/') === 0 && $content === 'PDF content';
            })
            ->andReturn(true);
            
        // Verify file exists after saving
        Storage::disk('public')->shouldReceive('exists')
            ->withArgs(function ($path) {
                return strpos($path, 'applications/') === 0;
            })
            ->andReturn(true);
            
        // Get file size
        Storage::disk('public')->shouldReceive('size')
            ->withArgs(function ($path) {
                return strpos($path, 'applications/') === 0;
            })
            ->andReturn(1024); // 1KB
        
        // Call the method
        $result = $this->pdfGeneratorService->generateApplicationPDF($applicationState);
        
        // Assert result is a string (path)
        $this->assertIsString($result);
        $this->assertStringStartsWith('applications/', $result);
    }
    
    /**
     * Test PDF generation with storage failure
     */
    public function test_generate_application_pdf_throws_exception_on_storage_failure()
    {
        // Mock Storage facade
        Storage::fake('public');
        
        // Create a mock PDF instance
        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('loadView')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('setOptions')->andReturnSelf();
        $pdfMock->shouldReceive('output')->andReturn('PDF content');
        
        // Mock the DomPDF instance
        $domPdfMock = Mockery::mock('Dompdf\Dompdf');
        $canvasMock = Mockery::mock('Dompdf\Canvas');
        
        $domPdfMock->shouldReceive('get_canvas')->andReturn($canvasMock);
        $pdfMock->shouldReceive('getDomPDF')->andReturn($domPdfMock);
        
        // Mock the canvas add_info method
        $canvasMock->shouldReceive('add_info')->withAnyArgs()->andReturnNull();
        
        // Replace the PDF facade with our mock
        Pdf::shouldReceive('loadView')->andReturn($pdfMock);
        
        // Create application state with valid form data
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com'
                ],
                'formId' => 'account_holder_loan_application.json'
            ]
        ]);
        
        // Set up logger expectations
        $this->pdfLoggingService->shouldReceive('logInfo')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logDebug')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logError')->withAnyArgs()->andReturnNull();
        
        // Ensure the applications directory exists check
        Storage::disk('public')->shouldReceive('exists')
            ->with('applications')
            ->andReturn(true);
        
        // Simulate storage failure
        Storage::disk('public')->shouldReceive('put')
            ->withArgs(function ($path, $content) {
                return strpos($path, 'applications/') === 0 && $content === 'PDF content';
            })
            ->andReturn(false);
            
        // Verify file exists after saving - return false to simulate failure
        Storage::disk('public')->shouldReceive('exists')
            ->withArgs(function ($path) {
                return strpos($path, 'applications/') === 0;
            })
            ->andReturn(false);
        
        // Expect exception
        $this->expectException(PDFStorageException::class);
        
        // Call the method
        $this->pdfGeneratorService->generateApplicationPDF($applicationState);
    }
    
    /**
     * Test PDF generation with rendering failure
     */
    public function test_generate_application_pdf_throws_exception_on_rendering_failure()
    {
        // Create application state with valid form data
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'current_step' => 'completed',
            'form_data' => [
                'employer' => 'some-employer',
                'hasAccount' => true,
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com'
                ],
                'formId' => 'account_holder_loan_application.json'
            ]
        ]);
        
        // Set up logger expectations
        $this->pdfLoggingService->shouldReceive('logInfo')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logDebug')->withAnyArgs()->andReturnNull();
        $this->pdfLoggingService->shouldReceive('logError')->withAnyArgs()->andReturnNull();
        
        // Simulate PDF rendering failure
        Pdf::shouldReceive('loadView')
            ->andThrow(new \Exception('PDF rendering failed'));
        
        // Expect exception
        $this->expectException(PDFGenerationException::class);
        
        // Call the method
        $this->pdfGeneratorService->generateApplicationPDF($applicationState);
    }
    
    /**
     * Test getApplicationTypeFromFormId method
     */
    public function test_get_application_type_from_form_id()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'getApplicationTypeFromFormId');
        $reflectionMethod->setAccessible(true);
        
        // Test known form IDs
        $this->assertEquals(
            'Account Holder Loan',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'account_holder_loan_application.json')
        );
        
        $this->assertEquals(
            'SSB Loan',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'ssb_account_opening_form.json')
        );
        
        $this->assertEquals(
            'ZB Account Opening',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'individual_account_opening.json')
        );
        
        $this->assertEquals(
            'SME Business Account',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'smes_business_account_opening.json')
        );
        
        // Test unknown form ID
        $this->assertEquals(
            'Application',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'unknown_form_id.json')
        );
    }
    
    /**
     * Test document type label generation
     */
    public function test_get_document_type_label()
    {
        // Use reflection to access private method
        $reflectionMethod = new \ReflectionMethod(PDFGeneratorService::class, 'getDocumentTypeLabel');
        $reflectionMethod->setAccessible(true);
        
        // Test known document types
        $this->assertEquals(
            'National ID',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'id')
        );
        
        $this->assertEquals(
            'Proof of Residence',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'proofOfResidence')
        );
        
        $this->assertEquals(
            'Payslip',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'payslip')
        );
        
        // Test unknown document type
        $this->assertEquals(
            'Custom Document',
            $reflectionMethod->invoke($this->pdfGeneratorService, 'customDocument')
        );
    }
}