<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Models\User;
use App\Services\ReferenceCodeService;
use App\Services\TwilioWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CrossPlatformReferenceCodeTest extends TestCase
{
    use RefreshDatabase;

    protected $referenceCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referenceCodeService = app(ReferenceCodeService::class);
    }

    /** @test */
    public function reference_code_enables_seamless_platform_switching()
    {
        // Create application on web
        $webSessionId = 'web_platform_test';
        $referenceCode = $this->referenceCodeService->generateReferenceCode();
        
        $webApplication = ApplicationState::create([
            'session_id' => $webSessionId,
            'channel' => 'web',
            'user_identifier' => $webSessionId,
            'current_step' => 'product',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'vehicles',
                'business' => 'Honda Civic',
                'amount' => 22000
            ],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);

        // Test 1: Resume on web using reference code
        $webResumeResponse = $this->get('/application/resume/' . $referenceCode);
        $webResumeResponse->assertStatus(200);
        
        $props = $webResumeResponse->viewData('page')['props'];
        $this->assertEquals($webSessionId, $props['sessionId']);
        $this->assertEquals('product', $props['currentStep']);
        $this->assertEquals('Honda Civic', $props['wizardData']['business']);

        // Test 2: Switch to WhatsApp
        $phoneNumber = '+263775555555';
        $switchResponse = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => $webSessionId,
            'phone_number' => $phoneNumber
        ]);
        $switchResponse->assertStatus(200);

        // Test 3: Continue on WhatsApp using reference code
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')
                   ->with('whatsapp:' . $phoneNumber)
                   ->andReturn(ltrim($phoneNumber, '+'));
        $twilioMock->shouldReceive('sendMessage')
                   ->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);

        $whatsappResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:' . $phoneNumber,
            'Body' => $referenceCode,
            'MessageSid' => 'test_platform_switch'
        ]);
        $whatsappResponse->assertStatus(200);

        // Test 4: Check status via API using reference code
        $statusResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $statusResponse->assertStatus(200)
            ->assertJsonStructure([
                'sessionId',
                'status',
                'applicantName',
                'business',
                'loanAmount',
                'timeline',
                'progressPercentage'
            ]);

        $statusData = $statusResponse->json();
        $this->assertEquals('Honda Civic', $statusData['business']);
        $this->assertEquals(22000, $statusData['loanAmount']);

        // Test 5: Admin can find application using reference code
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Search for application in admin interface
        $adminSearchResponse = $this->getJson('/admin/applications/search?reference_code=' . $referenceCode);
        $adminSearchResponse->assertStatus(200);
        
        $searchResults = $adminSearchResponse->json();
        $this->assertCount(1, $searchResults['data']);
        $this->assertEquals($referenceCode, $searchResults['data'][0]['reference_code']);
    }

    /** @test */
    public function reference_code_validation_works_consistently()
    {
        // Create multiple applications with reference codes
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $code = $this->referenceCodeService->generateReferenceCode();
            $codes[] = $code;
            
            ApplicationState::create([
                'session_id' => 'test_session_' . $i,
                'channel' => 'web',
                'user_identifier' => 'test_session_' . $i,
                'current_step' => 'completed',
                'form_data' => [
                    'language' => 'en',
                    'formResponses' => [
                        'firstName' => 'Test' . $i,
                        'lastName' => 'User' . $i
                    ]
                ],
                'reference_code' => $code,
                'reference_code_expires_at' => now()->addDays(30),
                'expires_at' => now()->addHours(24)
            ]);
        }

        // Test validation for all valid codes
        foreach ($codes as $code) {
            $this->assertTrue($this->referenceCodeService->validateReferenceCode($code));
            
            $state = $this->referenceCodeService->getStateByReferenceCode($code);
            $this->assertNotNull($state);
            $this->assertEquals($code, $state->reference_code);
        }

        // Test validation for invalid codes
        $invalidCodes = ['INVALID', '123456', 'ABCDEF', 'SHORT', 'TOOLONG123'];
        foreach ($invalidCodes as $invalidCode) {
            $this->assertFalse($this->referenceCodeService->validateReferenceCode($invalidCode));
            
            $state = $this->referenceCodeService->getStateByReferenceCode($invalidCode);
            $this->assertNull($state);
        }

        // Test expired reference codes
        $expiredCode = $this->referenceCodeService->generateReferenceCode();
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
    }

    /** @test */
    public function reference_code_enables_status_tracking_across_platforms()
    {
        // Create application with various status updates
        $referenceCode = $this->referenceCodeService->generateReferenceCode();
        $sessionId = 'status_tracking_test';
        
        $application = ApplicationState::create([
            'session_id' => $sessionId,
            'channel' => 'web',
            'user_identifier' => $sessionId,
            'current_step' => 'completed',
            'form_data' => [
                'language' => 'en',
                'intent' => 'microBiz',
                'formResponses' => [
                    'firstName' => 'Status',
                    'lastName' => 'Tracker',
                    'email' => 'status@example.com'
                ],
                'selectedBusiness' => ['name' => 'Status Business'],
                'finalPrice' => 8000
            ],
            'metadata' => [
                'status' => 'under_review',
                'documents_verified' => true,
                'documents_verified_at' => now()->subDays(2)->toIso8601String(),
                'credit_check_completed' => true,
                'credit_check_completed_at' => now()->subDays(1)->toIso8601String(),
                'status_history' => [
                    [
                        'status' => 'submitted',
                        'timestamp' => now()->subDays(3)->toIso8601String(),
                        'note' => 'Application submitted'
                    ],
                    [
                        'status' => 'under_review',
                        'timestamp' => now()->subDays(1)->toIso8601String(),
                        'note' => 'Application under review'
                    ]
                ]
            ],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);

        // Test 1: Web status page
        $webStatusResponse = $this->get('/application/status?reference_code=' . $referenceCode);
        $webStatusResponse->assertStatus(200);
        
        $props = $webStatusResponse->viewData('page')['props'];
        $this->assertEquals('under_review', $props['application']['status']);
        $this->assertNotEmpty($props['application']['timeline']);

        // Test 2: API status endpoint
        $apiStatusResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $apiStatusResponse->assertStatus(200)
            ->assertJsonPath('status', 'under_review')
            ->assertJsonPath('applicantName', 'Status Tracker')
            ->assertJsonStructure([
                'timeline' => [
                    '*' => [
                        'title',
                        'description',
                        'timestamp',
                        'status',
                        'icon'
                    ]
                ]
            ]);

        // Test 3: WhatsApp status check
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')
                   ->andReturn('263776666666');
        $twilioMock->shouldReceive('sendMessage')
                   ->with('whatsapp:+263776666666', Mockery::on(function ($message) use ($referenceCode) {
                       return str_contains($message, 'Application Status') &&
                              str_contains($message, $referenceCode) &&
                              str_contains($message, 'under_review');
                   }))
                   ->andReturn(true);
        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);

        $whatsappStatusResponse = $this->postJson('/whatsapp/webhook', [
            'From' => 'whatsapp:+263776666666',
            'Body' => 'STATUS ' . $referenceCode,
            'MessageSid' => 'test_status_check'
        ]);
        $whatsappStatusResponse->assertStatus(200);

        // Test 4: Admin status update
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $adminUpdateResponse = $this->postJson('/api/application/status/' . $sessionId, [
            'status' => 'approved',
            'approval_details' => [
                'amount' => 8000,
                'disbursement_date' => now()->addDays(3)->format('Y-m-d')
            ]
        ]);
        $adminUpdateResponse->assertStatus(200);

        // Verify status was updated
        $updatedStatusResponse = $this->getJson('/api/application/status/' . $referenceCode);
        $updatedStatusResponse->assertStatus(200)
            ->assertJsonPath('status', 'approved');
    }

    /** @test */
    public function reference_code_lookup_provides_comprehensive_information()
    {
        // Create comprehensive application
        $referenceCode = $this->referenceCodeService->generateReferenceCode();
        $sessionId = 'comprehensive_lookup_test';
        
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
                'business' => 'MacBook Pro',
                'amount' => 45000,
                'creditTerm' => 24,
                'monthlyPayment' => 2100,
                'hasAccount' => true,
                'accountNumber' => '1122334455',
                'formResponses' => [
                    'firstName' => 'Comprehensive',
                    'lastName' => 'Test',
                    'email' => 'comprehensive@example.com',
                    'phone' => '+263777777777',
                    'nationalId' => '11223344556677',
                    'address' => '789 Lookup Street, Harare'
                ],
                'documents' => [
                    'uploadedDocuments' => [
                        'nationalId' => ['path' => 'documents/id.jpg'],
                        'proofOfIncome' => ['path' => 'documents/income.pdf']
                    ],
                    'selfie' => 'documents/selfie.jpg',
                    'signature' => 'documents/signature.png'
                ]
            ],
            'metadata' => [
                'status' => 'approved',
                'approval_details' => [
                    'amount' => 45000,
                    'approved_at' => now()->subDays(1)->toIso8601String(),
                    'disbursement_date' => now()->addDays(2)->format('Y-m-d')
                ],
                'documents_verified' => true,
                'credit_check_completed' => true,
                'committee_review_completed' => true
            ],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24)
        ]);

        // Test comprehensive lookup via API
        $lookupResponse = $this->getJson('/api/reference-code/lookup/' . $referenceCode);
        $lookupResponse->assertStatus(200)
            ->assertJsonStructure([
                'reference_code',
                'application' => [
                    'sessionId',
                    'status',
                    'applicantName',
                    'business',
                    'loanAmount',
                    'monthlyPayment',
                    'creditTerm',
                    'hasAccount',
                    'accountNumber',
                    'submittedAt',
                    'lastUpdated',
                    'timeline',
                    'progressPercentage',
                    'estimatedCompletionDate',
                    'nextAction',
                    'documents' => [
                        'uploaded',
                        'verified',
                        'pending'
                    ],
                    'approval' => [
                        'status',
                        'amount',
                        'disbursementDate'
                    ]
                ]
            ]);

        $lookupData = $lookupResponse->json();
        
        // Verify comprehensive data
        $this->assertEquals($referenceCode, $lookupData['reference_code']);
        $this->assertEquals('approved', $lookupData['application']['status']);
        $this->assertEquals('Comprehensive Test', $lookupData['application']['applicantName']);
        $this->assertEquals('MacBook Pro', $lookupData['application']['business']);
        $this->assertEquals(45000, $lookupData['application']['loanAmount']);
        $this->assertEquals(2100, $lookupData['application']['monthlyPayment']);
        $this->assertEquals(24, $lookupData['application']['creditTerm']);
        $this->assertTrue($lookupData['application']['hasAccount']);
        $this->assertEquals('1122334455', $lookupData['application']['accountNumber']);
        $this->assertEquals(100, $lookupData['application']['progressPercentage']);
        $this->assertNotEmpty($lookupData['application']['timeline']);
        $this->assertNotEmpty($lookupData['application']['documents']['uploaded']);
        $this->assertEquals('approved', $lookupData['application']['approval']['status']);
        $this->assertEquals(45000, $lookupData['application']['approval']['amount']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}