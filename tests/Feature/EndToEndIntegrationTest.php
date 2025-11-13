<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Models\User;
use App\Services\StateManager;
use App\Services\ReferenceCodeService;
use App\Services\PDFGeneratorService;
use App\Services\TwilioWhatsAppService;
use App\Services\CrossPlatformSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\UploadedFile;
use Mockery;

class EndToEndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $stateManager;
    protected $referenceCodeService;
    protected $pdfGeneratorService;
    protected $crossPlatformSyncService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stateManager = app(StateManager::class);
        $this->referenceCodeService = app(ReferenceCodeService::class);
        $this->pdfGeneratorService = app(PDFGeneratorService::class);
        $this->crossPlatformSyncService = app(CrossPlatformSyncService::class);
        
        // Create storage disk for testing
        Storage::fake('public');
        
        // Prevent actual queue jobs from running
        Queue::fake();
    }

    /** @test */
    public function complete_application_flow_from_web_to_whatsapp_to_admin()
    {
        // Step 1: Start application on web
        $sessionId = $this->stateManager->generateSessionId('web');
        
        // Language selection
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'language',
            'data' => ['language' => 'en']
        ]);
        $response->assertStatus(200);
        
        // Intent selection
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'intent',
            'data' => ['intent' => 'hirePurchase']
        ]);
        $response->assertStatus(200);
        
        // Employer selection
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'employer',
            'data' => ['employer' => 'goz-ssb']
        ]);
        $response->assertStatus(200);
        
        // Product selection
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'product',
            'data' => [
                'category' => 'vehicles',
                'subcategory' => 'cars',
                'business' => 'Toyota Corolla',
                'amount' => 25000,
                'creditTerm' => 36
            ]
        ]);
        $response->assertStatus(200);
        
        // Account verification
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'account',
            'data' => [
                'hasAccount' => true,
                'accountNumber' => '1234567890'
            ]
        ]);
        $response->assertStatus(200);
        
        // Form completion
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'form',
            'data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'phone' => '+263771234567',
                    'nationalId' => '12345678901234',
                    'address' => '123 Main Street, Harare'
                ]
            ]
        ]);
        $response->assertStatus(200);
        
        // Document upload
        $file = UploadedFile::fake()->image('id_document.jpg');
        $response = $this->postJson('/api/documents/upload', [
            'sessionId' => $sessionId,
            'documentType' => 'nationalId',
            'file' => $file
        ]);
        $response->assertStatus(200);
        
        // Complete application and get reference code
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'summary',
            'data' => ['confirmed' => true]
        ]);
        $response->assertStatus(200);
        
        $applicationState = $this->stateManager->getState($sessionId);
        $this->assertNotNull($applicationState);
        $this->assertNotNull($applicationState->reference_code);
        $referenceCode = $applicationState->reference_code;
        
        // Step 2: Switch to WhatsApp
        $phoneNumber = '+263771234567';
        $response = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber
        ]);
        $response->assertStatus(200);
        
        // Verify WhatsApp state was created
        $whatsappSessionId = 'whatsapp_' . ltrim($phoneNumber, '+');
        $whatsappState = $this->stateManager->getState($whatsappSessionId);
        $this->assertNotNull($whatsappState);
        $this->assertEquals('whatsapp', $whatsappState->channel);
        
        // Step 3: Continue application via WhatsApp (simulate webhook)
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263771234567');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        // Simulate WhatsApp message with reference code
        $response = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263771234567',
            'Body' => $referenceCode,
            'MessageSid' => 'test_message_sid'
        ]);
        $response->assertStatus(200);
        
        // Step 4: Check application status via WhatsApp
        $response = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263771234567',
            'Body' => 'STATUS ' . $referenceCode,
            'MessageSid' => 'test_status_message_sid'
        ]);
        $response->assertStatus(200);
        
        // Step 5: Admin interface - Create admin user and login
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);
        
        $this->actingAs($admin);
        
        // Step 6: View application in admin interface
        $response = $this->get('/admin/applications');
        $response->assertStatus(200);
        
        // Step 7: Generate PDF from admin interface
        $response = $this->postJson('/admin/applications/generate-pdf', [
            'session_id' => $sessionId
        ]);
        $response->assertStatus(200);
        
        // Verify PDF was generated
        $applicationState->refresh();
        $this->assertNotNull($applicationState->form_data['pdfPath']);
        
        // Step 8: Bulk PDF generation
        $secondSessionId = $this->stateManager->generateSessionId('web');
        ApplicationState::create([
            'session_id' => $secondSessionId,
            'channel' => 'web',
            'user_identifier' => $secondSessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'formResponses' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'email' => 'jane.smith@example.com'
                ]
            ],
            'reference_code' => $this->referenceCodeService->generateReferenceCode($secondSessionId),
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        
        $response = $this->postJson('/admin/applications/bulk-generate-pdf', [
            'session_ids' => [$sessionId, $secondSessionId]
        ]);
        $response->assertStatus(200);
        
        // Verify both PDFs were generated
        $this->assertArrayHasKey('zip_path', $response->json());
        
        // Step 9: Test cross-platform synchronization
        $syncResponse = $this->postJson('/application/synchronize', [
            'primary_session_id' => $sessionId,
            'secondary_session_id' => $whatsappSessionId
        ]);
        $syncResponse->assertStatus(200);
        
        // Step 10: Test reference code functionality across platforms
        $webResumeResponse = $this->get('/application/resume/' . $referenceCode);
        $webResumeResponse->assertStatus(200);
        
        $apiStatusResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $apiStatusResponse->assertStatus(200);
        
        // Verify all data is consistent across platforms
        $webState = $this->stateManager->getState($sessionId);
        $whatsappState = $this->stateManager->getState($whatsappSessionId);
        
        $this->assertEquals($webState->form_data['language'], $whatsappState->form_data['language']);
        $this->assertEquals($webState->form_data['intent'], $whatsappState->form_data['intent']);
        $this->assertEquals($webState->form_data['employer'], $whatsappState->form_data['employer']);
    }

    /** @test */
    public function reference_code_works_across_all_platforms()
    {
        // Create application with reference code
        $sessionId = $this->stateManager->generateSessionId('web');
        $referenceCode = $this->referenceCodeService->generateReferenceCode($sessionId);
        
        $applicationState = ApplicationState::create([
            'session_id' => $sessionId,
            'channel' => 'web',
            'user_identifier' => $sessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'microBiz',
                'employer' => 'entrepreneur',
                'formResponses' => [
                    'firstName' => 'Test',
                    'lastName' => 'User',
                    'email' => 'test@example.com',
                    'phone' => '+263771111111'
                ],
                'selectedBusiness' => ['name' => 'Test Business'],
                'finalPrice' => 10000
            ],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        
        // Test 1: Web platform - Resume application
        $webResponse = $this->get('/application/resume/' . $referenceCode);
        $webResponse->assertStatus(200);
        
        // Test 2: API - Get status
        $apiResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $apiResponse->assertStatus(200)
            ->assertJsonStructure([
                'sessionId',
                'status',
                'applicantName',
                'business',
                'loanAmount',
                'timeline',
                'progressPercentage'
            ]);
        
        // Test 3: WhatsApp - Mock webhook with reference code
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263771111111');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        $whatsappResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263771111111',
            'Body' => $referenceCode,
            'MessageSid' => 'test_ref_code_message'
        ]);
        $whatsappResponse->assertStatus(200);
        
        // Test 4: Admin interface - View application
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $adminResponse = $this->get('/admin/applications');
        $adminResponse->assertStatus(200);
        
        // Test 5: Validate reference code service
        $this->assertTrue($this->referenceCodeService->validateReferenceCode($referenceCode));
        $retrievedState = $this->referenceCodeService->getStateByReferenceCode($referenceCode);
        $this->assertNotNull($retrievedState);
        $this->assertEquals($sessionId, $retrievedState->session_id);
    }

    /** @test */
    public function pdf_generation_works_from_admin_interface()
    {
        // Create completed application
        $sessionId = $this->stateManager->generateSessionId('web');
        $applicationState = ApplicationState::create([
            'session_id' => $sessionId,
            'channel' => 'web',
            'user_identifier' => $sessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'vehicles',
                'business' => 'Toyota Camry',
                'amount' => 30000,
                'formResponses' => [
                    'firstName' => 'PDF',
                    'lastName' => 'Test',
                    'email' => 'pdf.test@example.com',
                    'phone' => '+263772222222',
                    'nationalId' => '98765432109876',
                    'address' => '456 Test Street, Bulawayo'
                ],
                'hasAccount' => true,
                'accountNumber' => '9876543210'
            ],
            'reference_code' => $this->referenceCodeService->generateReferenceCode(),
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        
        // Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        // Test individual PDF generation
        $response = $this->postJson('/admin/applications/generate-pdf', [
            'session_id' => $sessionId
        ]);
        $response->assertStatus(200);
        
        // Verify PDF was generated and stored
        $applicationState->refresh();
        $this->assertNotNull($applicationState->form_data['pdfPath']);
        
        // Test PDF download
        $downloadResponse = $this->get('/application/pdf/download/' . $sessionId);
        $downloadResponse->assertStatus(200);
        
        // Test PDF view
        $viewResponse = $this->get('/application/pdf/view/' . $sessionId);
        $viewResponse->assertStatus(200);
        
        // Test PDF regeneration
        $regenerateResponse = $this->postJson('/application/pdf/regenerate/' . $sessionId);
        $regenerateResponse->assertStatus(200);
    }

    /** @test */
    public function cross_platform_data_synchronization_maintains_consistency()
    {
        // Create web application
        $webSessionId = $this->stateManager->generateSessionId('web');
        $webState = ApplicationState::create([
            'session_id' => $webSessionId,
            'channel' => 'web',
            'user_identifier' => $webSessionId,
            'current_step' => 'product',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'electronics',
                'business' => 'Samsung TV'
            ],
            'expires_at' => now()->addHours(24)
        ]);
        
        // Create WhatsApp application with additional data
        $whatsappSessionId = 'whatsapp_263773333333';
        $whatsappState = ApplicationState::create([
            'session_id' => $whatsappSessionId,
            'channel' => 'whatsapp',
            'user_identifier' => '263773333333',
            'current_step' => 'form',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'electronics',
                'business' => 'Samsung TV',
                'amount' => 15000,
                'formResponses' => [
                    'firstName' => 'Sync',
                    'lastName' => 'Test'
                ]
            ],
            'metadata' => ['phone_number' => '263773333333'],
            'expires_at' => now()->addDays(7)
        ]);
        
        // Test synchronization
        $syncResponse = $this->postJson('/application/synchronize', [
            'primary_session_id' => $whatsappSessionId, // WhatsApp has more data
            'secondary_session_id' => $webSessionId
        ]);
        $syncResponse->assertStatus(200);
        
        // Verify both states have synchronized data
        $webState->refresh();
        $whatsappState->refresh();
        
        $this->assertEquals('form', $webState->current_step);
        $this->assertEquals('form', $whatsappState->current_step);
        $this->assertEquals(15000, $webState->form_data['amount']);
        $this->assertEquals('Sync', $webState->form_data['formResponses']['firstName']);
        
        // Test sync status
        $syncStatusResponse = $this->getJson('/application/sync-status?' . http_build_query([
            'session_id_1' => $webSessionId,
            'session_id_2' => $whatsappSessionId
        ]));
        $syncStatusResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('sync_status.status', 'synchronized');
    }

    /** @test */
    public function error_handling_works_across_all_components()
    {
        // Test invalid reference code
        $invalidRefResponse = $this->getJson('/api/application/status/INVALID');
        $invalidRefResponse->assertStatus(404);
        
        // Test PDF generation with incomplete data
        $incompleteSessionId = $this->stateManager->generateSessionId('web');
        ApplicationState::create([
            'session_id' => $incompleteSessionId,
            'channel' => 'web',
            'user_identifier' => $incompleteSessionId,
            'current_step' => 'language', // Incomplete application
            'form_data' => ['language' => 'en'],
            'expires_at' => now()->addHours(24)
        ]);
        
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $pdfResponse = $this->postJson('/admin/applications/generate-pdf', [
            'session_id' => $incompleteSessionId
        ]);
        $pdfResponse->assertStatus(422); // Should fail validation
        
        // Test WhatsApp webhook with invalid data
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263774444444');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        $webhookResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263774444444',
            'Body' => 'INVALID_REF_CODE',
            'MessageSid' => 'test_invalid_message'
        ]);
        $webhookResponse->assertStatus(200); // Should handle gracefully
        
        // Test synchronization with non-existent sessions
        $syncErrorResponse = $this->postJson('/application/synchronize', [
            'primary_session_id' => 'non_existent_1',
            'secondary_session_id' => 'non_existent_2'
        ]);
        $syncErrorResponse->assertStatus(500);
    }

    /** @test */
    public function complete_multi_platform_application_lifecycle()
    {
        // Phase 1: Start application on web
        $sessionId = $this->stateManager->generateSessionId('web');
        
        // Complete initial steps
        $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'language',
            'data' => ['language' => 'en']
        ])->assertStatus(200);
        
        $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'intent',
            'data' => ['intent' => 'microBiz']
        ])->assertStatus(200);
        
        $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'employer',
            'data' => ['employer' => 'entrepreneur']
        ])->assertStatus(200);
        
        // Get reference code early
        $response = $this->postJson('/application/wizard', [
            'sessionId' => $sessionId,
            'step' => 'product',
            'data' => [
                'category' => 'retail',
                'business' => 'Grocery Store',
                'amount' => 15000
            ]
        ]);
        $response->assertStatus(200);
        
        $applicationState = $this->stateManager->getState($sessionId);
        $referenceCode = $applicationState->reference_code;
        $this->assertNotNull($referenceCode);
        
        // Phase 2: Switch to WhatsApp mid-application
        $phoneNumber = '+263778888888';
        $switchResponse = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber
        ]);
        $switchResponse->assertStatus(200);
        
        // Phase 3: Continue on WhatsApp
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263778888888');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        // Continue application via WhatsApp
        $whatsappResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:' . $phoneNumber,
            'Body' => $referenceCode,
            'MessageSid' => 'continue_app_msg'
        ]);
        $whatsappResponse->assertStatus(200);
        
        // Complete form via WhatsApp simulation
        $whatsappSessionId = 'whatsapp_' . ltrim($phoneNumber, '+');
        $whatsappState = $this->stateManager->getState($whatsappSessionId);
        $whatsappState->update([
            'current_step' => 'completed',
            'form_data' => array_merge($whatsappState->form_data, [
                'formResponses' => [
                    'firstName' => 'Multi',
                    'lastName' => 'Platform',
                    'email' => 'multi.platform@example.com',
                    'phone' => $phoneNumber,
                    'businessName' => 'Multi Platform Store'
                ],
                'applicationComplete' => true
            ])
        ]);
        
        // Phase 4: Check status from web
        $webStatusResponse = $this->get('/application/status?reference_code=' . $referenceCode);
        $webStatusResponse->assertStatus(200);
        
        // Phase 5: Check status via API
        $apiStatusResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $apiStatusResponse->assertStatus(200)
            ->assertJsonPath('applicantName', 'Multi Platform')
            ->assertJsonPath('business', 'Grocery Store');
        
        // Phase 6: Admin processes application
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        // Update status via admin
        $adminUpdateResponse = $this->postJson('/api/application/status/' . $sessionId, [
            'status' => 'approved',
            'approval_details' => [
                'amount' => 15000,
                'disbursement_date' => now()->addDays(5)->format('Y-m-d')
            ]
        ]);
        $adminUpdateResponse->assertStatus(200);
        
        // Generate PDF from admin
        $pdfResponse = $this->postJson('/admin/applications/generate-pdf', [
            'session_id' => $sessionId
        ]);
        $pdfResponse->assertStatus(200);
        
        // Phase 7: Final status check across all platforms
        $finalWebStatus = $this->get('/application/status?reference_code=' . $referenceCode);
        $finalWebStatus->assertStatus(200);
        
        $finalApiStatus = $this->getJson('/api/application/status/' . $referenceCode);
        $finalApiStatus->assertStatus(200)
            ->assertJsonPath('status', 'approved');
        
        // WhatsApp status check
        $whatsappStatusResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:' . $phoneNumber,
            'Body' => 'STATUS ' . $referenceCode,
            'MessageSid' => 'final_status_check'
        ]);
        $whatsappStatusResponse->assertStatus(200);
        
        // Verify data consistency across platforms
        $webState = $this->stateManager->getState($sessionId);
        $whatsappState = $this->stateManager->getState($whatsappSessionId);
        
        $this->assertEquals('approved', $webState->metadata['status'] ?? null);
        $this->assertEquals('Multi Platform', $webState->form_data['formResponses']['firstName']);
        $this->assertEquals('Grocery Store', $webState->form_data['business']);
    }

    /** @test */
    public function admin_bulk_operations_work_across_platforms()
    {
        // Create multiple applications from different platforms
        $applications = [];
        
        // Web application
        $webSessionId = $this->stateManager->generateSessionId('web');
        $webApp = ApplicationState::create([
            'session_id' => $webSessionId,
            'channel' => 'web',
            'user_identifier' => $webSessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'business' => 'Web Application Car',
                'amount' => 20000,
                'formResponses' => [
                    'firstName' => 'Web',
                    'lastName' => 'User',
                    'email' => 'web@example.com'
                ]
            ],
            'reference_code' => $this->referenceCodeService->generateReferenceCode($webSessionId),
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        $applications[] = $webSessionId;
        
        // WhatsApp application
        $whatsappSessionId = 'whatsapp_263779999999';
        $whatsappApp = ApplicationState::create([
            'session_id' => $whatsappSessionId,
            'channel' => 'whatsapp',
            'user_identifier' => '263779999999',
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'sn',
                'intent' => 'microBiz',
                'employer' => 'entrepreneur',
                'business' => 'WhatsApp Business',
                'amount' => 8000,
                'formResponses' => [
                    'firstName' => 'WhatsApp',
                    'lastName' => 'User',
                    'phone' => '+263779999999'
                ]
            ],
            'reference_code' => $this->referenceCodeService->generateReferenceCode($whatsappSessionId),
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addDays(7)
        ]);
        $applications[] = $whatsappSessionId;
        
        // Cross-platform application (started on web, completed on WhatsApp)
        $crossPlatformWebId = $this->stateManager->generateSessionId('web');
        $crossPlatformWhatsAppId = 'whatsapp_263770000000';
        
        $crossWebApp = ApplicationState::create([
            'session_id' => $crossPlatformWebId,
            'channel' => 'web',
            'user_identifier' => $crossPlatformWebId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'business' => 'Cross Platform Item',
                'amount' => 12000,
                'formResponses' => [
                    'firstName' => 'Cross',
                    'lastName' => 'Platform',
                    'email' => 'cross@example.com'
                ]
            ],
            'metadata' => ['linked_whatsapp_session' => $crossPlatformWhatsAppId],
            'reference_code' => $this->referenceCodeService->generateReferenceCode($crossPlatformWebId),
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        
        $crossWhatsAppApp = ApplicationState::create([
            'session_id' => $crossPlatformWhatsAppId,
            'channel' => 'whatsapp',
            'user_identifier' => '263770000000',
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'business' => 'Cross Platform Item',
                'amount' => 12000,
                'formResponses' => [
                    'firstName' => 'Cross',
                    'lastName' => 'Platform',
                    'phone' => '+263770000000'
                ]
            ],
            'metadata' => ['linked_web_session' => $crossPlatformWebId],
            'reference_code' => $crossWebApp->reference_code, // Same reference code
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addDays(7)
        ]);
        $applications[] = $crossPlatformWebId;
        
        // Admin operations
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        // Test bulk PDF generation
        $bulkPdfResponse = $this->postJson('/admin/applications/bulk-generate-pdf', [
            'session_ids' => $applications
        ]);
        $bulkPdfResponse->assertStatus(200)
            ->assertJsonStructure(['zip_path', 'generated_count']);
        
        // Test bulk status update
        $bulkStatusResponse = $this->postJson('/admin/applications/bulk-status-update', [
            'session_ids' => $applications,
            'status' => 'under_review',
            'note' => 'Bulk review initiated'
        ]);
        $bulkStatusResponse->assertStatus(200);
        
        // Verify all applications were updated
        foreach ($applications as $sessionId) {
            $app = ApplicationState::where('session_id', $sessionId)->first();
            $this->assertEquals('under_review', $app->metadata['status'] ?? null);
        }
        
        // Test admin dashboard shows cross-platform statistics
        $dashboardResponse = $this->getJson('/admin/dashboard/stats');
        $dashboardResponse->assertStatus(200)
            ->assertJsonStructure([
                'total_applications',
                'by_channel' => [
                    'web',
                    'whatsapp'
                ],
                'cross_platform_applications',
                'completion_rates'
            ]);
        
        $stats = $dashboardResponse->json();
        $this->assertGreaterThan(0, $stats['by_channel']['web']);
        $this->assertGreaterThan(0, $stats['by_channel']['whatsapp']);
        $this->assertGreaterThan(0, $stats['cross_platform_applications']);
    }

    /** @test */
    public function reference_code_functionality_comprehensive_test()
    {
        // Create application with comprehensive data
        $sessionId = $this->stateManager->generateSessionId('web');
        $referenceCode = $this->referenceCodeService->generateReferenceCode($sessionId);
        
        $application = ApplicationState::create([
            'session_id' => $sessionId,
            'channel' => 'web',
            'user_identifier' => $sessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'electronics',
                'business' => 'Reference Test Item',
                'amount' => 18000,
                'creditTerm' => 18,
                'monthlyPayment' => 1200,
                'hasAccount' => true,
                'accountNumber' => '5566778899',
                'formResponses' => [
                    'firstName' => 'Reference',
                    'lastName' => 'Test',
                    'email' => 'reference.test@example.com',
                    'phone' => '+263771111111',
                    'nationalId' => '55667788990011',
                    'address' => '123 Reference Street, Harare'
                ]
            ],
            'metadata' => [
                'status' => 'submitted',
                'submitted_at' => now()->subHours(2)->toIso8601String(),
                'documents_uploaded' => true,
                'status_history' => [
                    [
                        'status' => 'submitted',
                        'timestamp' => now()->subHours(2)->toIso8601String(),
                        'note' => 'Application submitted'
                    ]
                ]
            ],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);
        
        // Test 1: Web resume with reference code
        $webResumeResponse = $this->get('/application/resume/' . $referenceCode);
        $webResumeResponse->assertStatus(200);
        
        $props = $webResumeResponse->viewData('page')['props'];
        $this->assertEquals($sessionId, $props['sessionId']);
        $this->assertEquals('Reference Test Item', $props['wizardData']['business']);
        
        // Test 2: API status lookup
        $apiLookupResponse = $this->getJson('/api/reference-code/lookup/' . $referenceCode);
        $apiLookupResponse->assertStatus(200)
            ->assertJsonPath('reference_code', $referenceCode)
            ->assertJsonPath('application.applicantName', 'Reference Test')
            ->assertJsonPath('application.business', 'Reference Test Item')
            ->assertJsonPath('application.loanAmount', 18000)
            ->assertJsonPath('application.status', 'submitted');
        
        // Test 3: WhatsApp integration
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263771111111');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        // Resume via WhatsApp
        $whatsappResumeResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263771111111',
            'Body' => $referenceCode,
            'MessageSid' => 'ref_code_resume'
        ]);
        $whatsappResumeResponse->assertStatus(200);
        
        // Status check via WhatsApp
        $whatsappStatusResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263771111111',
            'Body' => 'STATUS ' . $referenceCode,
            'MessageSid' => 'ref_code_status'
        ]);
        $whatsappStatusResponse->assertStatus(200);
        
        // Test 4: Admin can find by reference code
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        
        $adminSearchResponse = $this->getJson('/admin/applications/search?reference_code=' . $referenceCode);
        $adminSearchResponse->assertStatus(200);
        
        $searchResults = $adminSearchResponse->json();
        $this->assertCount(1, $searchResults['data']);
        $this->assertEquals($referenceCode, $searchResults['data'][0]['reference_code']);
        
        // Test 5: Reference code validation edge cases
        $this->assertTrue($this->referenceCodeService->validateReferenceCode($referenceCode));
        $this->assertFalse($this->referenceCodeService->validateReferenceCode('INVALID'));
        $this->assertFalse($this->referenceCodeService->validateReferenceCode(''));
        $this->assertFalse($this->referenceCodeService->validateReferenceCode('12345')); // Too short
        $this->assertFalse($this->referenceCodeService->validateReferenceCode('1234567')); // Too long
        
        // Test 6: Reference code expiry
        $expiredSessionId = 'expired_test';
        $expiredCode = $this->referenceCodeService->generateReferenceCode($expiredSessionId);
        ApplicationState::create([
            'session_id' => 'expired_test',
            'channel' => 'web',
            'user_identifier' => 'expired_test',
            'current_step' => 'completed',
            'form_data' => ['language' => 'en'],
            'reference_code' => $expiredCode,
            'reference_code_expires_at' => now()->subDays(1), // Expired
            'expires_at' => now()->addHours(24)
        ]);
        
        $this->assertFalse($this->referenceCodeService->validateReferenceCode($expiredCode));
        
        $expiredLookupResponse = $this->getJson('/api/reference-code/lookup/' . $expiredCode);
        $expiredLookupResponse->assertStatus(404);
    }

    /** @test */
    public function error_handling_comprehensive_test()
    {
        // Test 1: Invalid reference codes
        $invalidCodes = ['INVALID', '12345', 'TOOLONG123'];
        
        foreach ($invalidCodes as $code) {
            $response = $this->getJson('/api/application/status/' . $code);
            $response->assertStatus(404);
            
            // Web resume should redirect to home with error for invalid codes
            $webResponse = $this->get('/application/resume/' . $code);
            $webResponse->assertStatus(302); // Redirect to home
        }
        
        // Test 2: PDF generation errors (basic validation)
        // Incomplete application
        $incompleteSessionId = $this->stateManager->generateSessionId('web');
        ApplicationState::create([
            'session_id' => $incompleteSessionId,
            'channel' => 'web',
            'user_identifier' => $incompleteSessionId,
            'current_step' => 'language',
            'form_data' => ['language' => 'en'],
            'expires_at' => now()->addHours(24)
        ]);
        
        // Test PDF download for incomplete application
        $pdfDownloadResponse = $this->get('/application/pdf/download/' . $incompleteSessionId);
        $pdfDownloadResponse->assertStatus(404); // Should not exist
        
        // Test 3: WhatsApp webhook error handling
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')->andReturn('263772222222');
        $twilioMock->shouldReceive('sendMessage')->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);
        
        // Invalid reference code via WhatsApp
        $webhookResponse = $this->postJson('/api/whatsapp/webhook', [
            'From' => 'whatsapp:+263772222222',
            'Body' => 'INVALID_REF',
            'MessageSid' => 'error_test_msg'
        ]);
        $webhookResponse->assertStatus(200); // Should handle gracefully
        
        // Test 4: Cross-platform sync errors
        $syncErrorResponse = $this->postJson('/application/synchronize', [
            'primary_session_id' => 'non_existent_1',
            'secondary_session_id' => 'non_existent_2'
        ]);
        $syncErrorResponse->assertStatus(500);
        
        // Test 5: Platform switching errors
        $switchErrorResponse = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => 'non_existent_session',
            'phone_number' => '+263773333333'
        ]);
        $switchErrorResponse->assertStatus(500);
        
        // Invalid phone number
        $validSessionId = $this->stateManager->generateSessionId('web');
        ApplicationState::create([
            'session_id' => $validSessionId,
            'channel' => 'web',
            'user_identifier' => $validSessionId,
            'current_step' => 'employer',
            'form_data' => ['language' => 'en'],
            'expires_at' => now()->addHours(24)
        ]);
        
        $invalidPhoneResponse = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => $validSessionId,
            'phone_number' => 'invalid-phone'
        ]);
        $invalidPhoneResponse->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}