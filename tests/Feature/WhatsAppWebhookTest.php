<?php

namespace Tests\Feature;

use App\Http\Controllers\WhatsAppWebhookController;
use App\Models\ApplicationState;
use App\Services\ReferenceCodeService;
use App\Services\TwilioWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Twilio configuration
        Config::set('services.twilio.auth_token', 'test_auth_token');
        Config::set('services.twilio.account_sid', 'test_account_sid');
        Config::set('services.twilio.whatsapp_number', 'whatsapp:+1234567890');
    }

    public function test_reference_code_service_validates_codes_correctly()
    {
        // Create an application state with reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'test_session',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'form',
            'form_data' => ['language' => 'en'],
            'reference_code' => 'ABC123',
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24),
        ]);

        $referenceCodeService = app(ReferenceCodeService::class);

        // Test valid reference code
        $this->assertTrue($referenceCodeService->validateReferenceCode('ABC123'));

        // Test invalid reference code
        $this->assertFalse($referenceCodeService->validateReferenceCode('INVALID'));

        // Test getting state by reference code
        $state = $referenceCodeService->getStateByReferenceCode('ABC123');
        $this->assertNotNull($state);
        $this->assertEquals('test_session', $state->session_id);

        // Test getting status by reference code
        $status = $referenceCodeService->getApplicationStatusByReferenceCode('ABC123');
        $this->assertNotNull($status);
        $this->assertEquals('form', $status['current_step']);
    }

    public function test_webhook_controller_methods_exist()
    {
        // Test that the controller has the required methods
        $controller = app(WhatsAppWebhookController::class);

        $this->assertTrue(method_exists($controller, 'resumeApplication'));
        $this->assertTrue(method_exists($controller, 'checkApplicationStatus'));
        $this->assertTrue(method_exists($controller, 'handleWebhook'));
    }

    public function test_resume_application_with_valid_reference_code()
    {
        // Create an application state with reference code
        $applicationState = ApplicationState::create([
            'session_id' => 'resume_test_session',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'form',
            'form_data' => [
                'language' => 'en',
                'selectedBusiness' => ['name' => 'Test Business'],
                'finalPrice' => 5000,
            ],
            'reference_code' => 'RESUME',
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24),
        ]);

        // Mock the Twilio service
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')
            ->with('whatsapp:+1234567890')
            ->andReturn('1234567890');
        $twilioMock->shouldReceive('sendMessage')
            ->once();

        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);

        $controller = app(WhatsAppWebhookController::class);

        // This should not throw an exception
        $controller->resumeApplication('whatsapp:+1234567890', 'RESUME');

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    public function test_check_application_status_with_valid_reference_code()
    {
        // Create a completed application state
        $applicationState = ApplicationState::create([
            'session_id' => 'status_test_session',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'selectedBusiness' => ['name' => 'Status Test Business'],
                'finalPrice' => 3000,
                'applicationComplete' => true,
            ],
            'metadata' => ['status' => 'under_review'],
            'reference_code' => 'STATUS',
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24),
        ]);

        // Mock the Twilio service
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')
            ->with('whatsapp:+1234567890')
            ->andReturn('1234567890');
        $twilioMock->shouldReceive('sendMessage')
            ->once()
            ->with('whatsapp:+1234567890', Mockery::on(function ($message) {
                return str_contains($message, 'Application Status') &&
                       str_contains($message, 'STATUS');
            }));

        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);

        $controller = app(WhatsAppWebhookController::class);

        // This should not throw an exception
        $controller->checkApplicationStatus('whatsapp:+1234567890', 'STATUS');

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    public function test_invalid_reference_code_handling()
    {
        // Mock the Twilio service
        $twilioMock = Mockery::mock(TwilioWhatsAppService::class);
        $twilioMock->shouldReceive('extractPhoneNumber')
            ->with('whatsapp:+1234567890')
            ->andReturn('1234567890');
        $twilioMock->shouldReceive('sendMessage')
            ->once()
            ->with('whatsapp:+1234567890', Mockery::on(function ($message) {
                return str_contains($message, 'Invalid Reference Code') &&
                       str_contains($message, 'INVALID');
            }));

        $this->app->instance(TwilioWhatsAppService::class, $twilioMock);

        $controller = app(WhatsAppWebhookController::class);

        // This should handle invalid reference code gracefully
        $controller->resumeApplication('whatsapp:+1234567890', 'INVALID');

        $this->assertTrue(true); // If we get here, the method executed successfully
    }
}
