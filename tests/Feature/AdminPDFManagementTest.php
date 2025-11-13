<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Http\Controllers\Admin\PDFManagementController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class AdminPDFManagementTest extends TestCase
{
    use RefreshDatabase;

    protected PDFManagementController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PDFManagementController(app(\App\Services\PDFGeneratorService::class));
    }

    /**
     * Test PDF controller form type detection
     */
    public function test_pdf_controller_form_type_detection()
    {
        // Create test applications of different types
        $applications = [
            'ssb' => new ApplicationState([
                'session_id' => 'test_ssb_admin_001',
                'channel' => 'web',
                'user_identifier' => 'test@example.com',
                'current_step' => 'completed',
                'form_data' => [
                    'formType' => 'ssb',
                    'firstName' => 'John',
                    'surname' => 'Doe',
                    'responsibleMinistry' => 'Education'
                ]
            ]),
            'sme' => new ApplicationState([
                'session_id' => 'test_sme_admin_001',
                'channel' => 'web',
                'user_identifier' => 'test@example.com',
                'current_step' => 'completed',
                'form_data' => [
                    'formType' => 'sme_business',
                    'businessName' => 'Test Business',
                    'businessRegistration' => 'TEST123'
                ]
            ]),
            'zb' => new ApplicationState([
                'session_id' => 'test_zb_admin_001',
                'channel' => 'web',
                'user_identifier' => 'test@example.com',
                'current_step' => 'completed',
                'form_data' => [
                    'formType' => 'zb_account_opening',
                    'firstName' => 'Sarah',
                    'surname' => 'Johnson',
                    'accountType' => 'savings'
                ]
            ])
        ];

        foreach ($applications as $type => $app) {
            // Test that the controller can detect form types
            $detectedType = $this->invokeMethod($this->controller, 'detectFormType', [$app]);
            
            switch ($type) {
                case 'ssb':
                    $this->assertEquals('SSB', $detectedType);
                    break;
                case 'sme':
                    $this->assertEquals('SME Business', $detectedType);
                    break;
                case 'zb':
                    $this->assertEquals('ZB Account Opening', $detectedType);
                    break;
            }
        }
    }

    /**
     * Test PDF filename generation
     */
    public function test_pdf_filename_generation()
    {
        $application = new ApplicationState([
            'session_id' => 'test_filename_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'John',
                'surname' => 'Doe',
                'formType' => 'ssb'
            ],
            'created_at' => now()
        ]);

        $filename = $this->invokeMethod($this->controller, 'generatePDFFilename', [$application, 'ssb']);
        
        $expectedPattern = '/John_Doe_ssb_Application_\d{8}\.pdf/';
        $this->assertMatchesRegularExpression($expectedPattern, $filename);
    }

    /**
     * Test statistics generation
     */
    public function test_statistics_generation()
    {
        // Create some test data
        ApplicationState::create([
            'session_id' => 'stat_test_001',
            'channel' => 'web',
            'user_identifier' => 'test1@example.com',
            'current_step' => 'completed',
            'form_data' => ['firstName' => 'Test', 'surname' => 'User1']
        ]);

        ApplicationState::create([
            'session_id' => 'stat_test_002',
            'channel' => 'whatsapp',
            'user_identifier' => 'test2@example.com',
            'current_step' => 'form_step',
            'form_data' => ['firstName' => 'Test', 'surname' => 'User2']
        ]);

        $response = $this->controller->statistics();
        $data = $response->getData(true);

        $this->assertArrayHasKey('total_applications', $data);
        $this->assertArrayHasKey('completion_rates', $data);
        $this->assertArrayHasKey('channel_breakdown', $data);
        
        $this->assertEquals(2, $data['total_applications']);
        $this->assertIsArray($data['completion_rates']);
        $this->assertIsArray($data['channel_breakdown']);
    }

    /**
     * Test form type breakdown
     */
    public function test_form_type_breakdown()
    {
        // Create applications with different form types
        ApplicationState::create([
            'session_id' => 'breakdown_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => ['formType' => 'ssb'],
            'metadata' => ['form_type' => 'ssb']
        ]);

        ApplicationState::create([
            'session_id' => 'breakdown_002',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => ['formType' => 'sme_business'],
            'metadata' => ['form_type' => 'sme_business']
        ]);

        $breakdown = $this->invokeMethod($this->controller, 'getFormTypeBreakdown', []);
        
        $this->assertIsArray($breakdown);
        // Should have entries for different form types
    }

    /**
     * Helper method to invoke private/protected methods
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Test error handling for missing applications
     */
    public function test_error_handling_for_missing_applications()
    {
        $response = $this->controller->download(new Request(), 'nonexistent_session_id');
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test bulk download validation
     */
    public function test_bulk_download_validation()
    {
        $request = new Request([
            'session_ids' => ['nonexistent_id_1', 'nonexistent_id_2']
        ]);

        $response = $this->controller->bulkDownload($request);
        
        // Should handle gracefully when no applications found
        $this->assertEquals(404, $response->getStatusCode());
    }
}