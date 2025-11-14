<?php

namespace Tests\Feature;

use App\Models\ApplicationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossPlatformSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_can_be_switched_to_whatsapp()
    {
        // Create a web application state
        $webState = ApplicationState::create([
            'session_id' => 'web_test_session',
            'channel' => 'web',
            'user_identifier' => 'web_test_session',
            'current_step' => 'employer',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
            ],
            'metadata' => [],
            'expires_at' => now()->addHours(24),
        ]);

        // Make API call to switch to WhatsApp
        $response = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => 'web_test_session',
            'phone_number' => '1234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Application successfully linked to WhatsApp',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'reference_code',
                'whatsapp_instructions' => [
                    'message',
                    'steps',
                    'reference_code',
                ],
            ]);

        // Verify WhatsApp state was created
        $whatsappState = ApplicationState::where('session_id', 'whatsapp_1234567890')->first();
        $this->assertNotNull($whatsappState);
        $this->assertEquals('whatsapp', $whatsappState->channel);
        $this->assertEquals('1234567890', $whatsappState->user_identifier);
        $this->assertEquals('employer', $whatsappState->current_step);

        // Verify data was synchronized
        $this->assertEquals('en', $whatsappState->form_data['language']);
        $this->assertEquals('hirePurchase', $whatsappState->form_data['intent']);
    }

    public function test_application_can_be_switched_to_web()
    {
        // Create a WhatsApp application state
        $whatsappState = ApplicationState::create([
            'session_id' => 'whatsapp_9876543210',
            'channel' => 'whatsapp',
            'user_identifier' => '9876543210',
            'current_step' => 'product',
            'form_data' => [
                'language' => 'sn',
                'intent' => 'microBiz',
                'employer' => 'entrepreneur',
                'selectedCategory' => ['id' => 'electronics', 'name' => 'Electronics'],
            ],
            'metadata' => ['phone_number' => '9876543210'],
            'expires_at' => now()->addDays(7),
        ]);

        // Make API call to switch to web
        $response = $this->postJson('/application/switch-to-web', [
            'whatsapp_session_id' => 'whatsapp_9876543210',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Application successfully switched to web',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'web_session_id',
                'resume_url',
            ]);

        $responseData = $response->json();

        // Verify web state was created
        $webState = ApplicationState::where('session_id', $responseData['web_session_id'])->first();
        $this->assertNotNull($webState);
        $this->assertEquals('web', $webState->channel);
        $this->assertEquals('product', $webState->current_step);

        // Verify data was synchronized and normalized for web
        $this->assertEquals('sn', $webState->form_data['language']);
        $this->assertEquals('microBiz', $webState->form_data['intent']);
        $this->assertEquals('entrepreneur', $webState->form_data['employer']);
        $this->assertEquals('electronics', $webState->form_data['category']); // Normalized from selectedCategory
    }

    public function test_sync_status_can_be_retrieved()
    {
        // Create two synchronized states
        $webState = ApplicationState::create([
            'session_id' => 'sync_web_session',
            'channel' => 'web',
            'user_identifier' => 'sync_web_session',
            'current_step' => 'form',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['last_sync' => now()->toISOString()],
            'expires_at' => now()->addHours(24),
        ]);

        $whatsappState = ApplicationState::create([
            'session_id' => 'sync_whatsapp_session',
            'channel' => 'whatsapp',
            'user_identifier' => '1111111111',
            'current_step' => 'form',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['last_sync' => now()->toISOString()],
            'expires_at' => now()->addDays(7),
        ]);

        // Get sync status
        $response = $this->getJson('/application/sync-status?'.http_build_query([
            'session_id_1' => 'sync_web_session',
            'session_id_2' => 'sync_whatsapp_session',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'sync_status' => [
                    'status',
                    'inconsistencies_count',
                    'inconsistencies',
                    'last_sync',
                    'state1_updated',
                    'state2_updated',
                ],
            ]);

        $syncStatus = $response->json('sync_status');
        $this->assertEquals('synchronized', $syncStatus['status']);
        $this->assertEquals(0, $syncStatus['inconsistencies_count']);
    }

    public function test_manual_synchronization_works()
    {
        // Create two states with different data
        $webState = ApplicationState::create([
            'session_id' => 'manual_sync_web',
            'channel' => 'web',
            'user_identifier' => 'manual_sync_web',
            'current_step' => 'employer',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'expires_at' => now()->addHours(24),
        ]);

        $whatsappState = ApplicationState::create([
            'session_id' => 'manual_sync_whatsapp',
            'channel' => 'whatsapp',
            'user_identifier' => '2222222222',
            'current_step' => 'product',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'selectedCategory' => ['id' => 'vehicles', 'name' => 'Vehicles'],
            ],
            'expires_at' => now()->addDays(7),
        ]);

        // Manually synchronize
        $response = $this->postJson('/application/synchronize', [
            'primary_session_id' => 'manual_sync_whatsapp', // WhatsApp has more data
            'secondary_session_id' => 'manual_sync_web',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Application data synchronized successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'sync_result' => [
                    'synchronized_states',
                    'current_step',
                    'sync_timestamp',
                ],
            ]);

        // Verify both states now have the merged data
        $webState->refresh();
        $whatsappState->refresh();

        $this->assertEquals('product', $webState->current_step);
        $this->assertEquals('product', $whatsappState->current_step);
        $this->assertEquals('goz-ssb', $webState->form_data['employer']);
        $this->assertArrayHasKey('selectedCategory', $webState->form_data);
    }

    public function test_resume_application_shows_sync_status()
    {
        // Create a web state with reference code
        $referenceCode = 'ABC123';
        $webState = ApplicationState::create([
            'session_id' => 'resume_test_web',
            'channel' => 'web',
            'user_identifier' => 'resume_test_web',
            'current_step' => 'form',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['phone_number' => '3333333333'],
            'reference_code' => $referenceCode,
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addHours(24),
        ]);

        // Create corresponding WhatsApp state
        $whatsappState = ApplicationState::create([
            'session_id' => 'whatsapp_3333333333',
            'channel' => 'whatsapp',
            'user_identifier' => '3333333333',
            'current_step' => 'form',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['linked_to' => 'resume_test_web'],
            'reference_code' => $referenceCode.'2', // Different reference code to avoid constraint violation
            'reference_code_expires_at' => now()->addDays(30),
            'expires_at' => now()->addDays(7),
        ]);

        // Resume application
        $response = $this->get('/application/resume/'.$referenceCode);

        $response->assertStatus(200);

        // Check that Inertia props include sync status
        $props = $response->viewData('page')['props'];
        $this->assertArrayHasKey('syncStatus', $props);
        $this->assertArrayHasKey('platformSwitchAvailable', $props);
        $this->assertTrue($props['platformSwitchAvailable']);
    }

    public function test_invalid_phone_number_returns_validation_error()
    {
        $webState = ApplicationState::create([
            'session_id' => 'invalid_phone_test',
            'channel' => 'web',
            'user_identifier' => 'invalid_phone_test',
            'current_step' => 'employer',
            'form_data' => ['language' => 'en'],
            'expires_at' => now()->addHours(24),
        ]);

        // Try to switch with invalid phone number
        $response = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => 'invalid_phone_test',
            'phone_number' => 'invalid-phone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_non_existent_session_returns_error()
    {
        // Try to switch non-existent session
        $response = $this->postJson('/application/switch-to-whatsapp', [
            'session_id' => 'non_existent_session',
            'phone_number' => '+1234567890',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to link application to WhatsApp. Please try again.',
            ]);
    }
}
