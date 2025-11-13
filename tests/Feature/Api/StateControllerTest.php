<?php

namespace Tests\Feature\Api;

use App\Models\ApplicationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::flush();
    }

    public function test_can_save_application_state()
    {
        $data = [
            'session_id' => 'test-session-123',
            'channel' => 'web',
            'user_identifier' => 'test-user@example.com',
            'current_step' => 'form',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ]
            ],
            'metadata' => [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Browser',
            ]
        ];

        $response = $this->postJson('/api/states/save', $data);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'state_id',
                    'expires_at',
                ]);

        $this->assertDatabaseHas('application_states', [
            'session_id' => 'test-session-123',
            'channel' => 'web',
            'current_step' => 'form',
        ]);
    }

    public function test_can_retrieve_application_state()
    {
        $applicationState = ApplicationState::factory()->create([
            'user_identifier' => 'test-user@example.com',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ]
            ]
        ]);

        $response = $this->postJson('/api/states/retrieve', [
            'user' => 'test-user@example.com',
            'channel' => 'web',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'session_id' => $applicationState->session_id,
                    'current_step' => 'form',
                    'can_resume' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'session_id',
                    'current_step',
                    'form_data',
                    'can_resume',
                    'expires_in',
                ]);
    }

    public function test_retrieve_returns_404_when_no_state_found()
    {
        $response = $this->postJson('/api/states/retrieve', [
            'user' => 'nonexistent-user@example.com',
            'channel' => 'web',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No active state found',
                ]);
    }

    public function test_save_state_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/states/save', [
            'session_id' => '', // Empty session ID
            'channel' => 'invalid-channel',
            'current_step' => 'invalid-step',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'session_id',
                    'channel',
                    'user_identifier',
                    'current_step',
                ]);
    }

    public function test_retrieve_state_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/states/retrieve', [
            'user' => '', // Empty user
            'channel' => 'invalid-channel',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'user',
                    'channel',
                ]);
    }

    public function test_can_create_final_application()
    {
        $applicationState = ApplicationState::factory()->create([
            'current_step' => 'completed',
            'form_data' => [
                'formId' => 'individual_account_opening.json',
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'emailAddress' => 'john.doe@example.com',
                    'mobile' => '+263771234567',
                    'nationalIdNumber' => '12-345678-A-12',
                ]
            ]
        ]);

        $response = $this->postJson('/api/states/create-application', [
            'session_id' => $applicationState->session_id,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'application_id',
                    'reference_code',
                    'created_at',
                ]);
    }

    public function test_create_application_fails_for_incomplete_state()
    {
        $applicationState = ApplicationState::factory()->create([
            'current_step' => 'form', // Not completed
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                ]
            ]
        ]);

        $response = $this->postJson('/api/states/create-application', [
            'session_id' => $applicationState->session_id,
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                ]);
    }

    public function test_state_caching_works_correctly()
    {
        $data = [
            'session_id' => 'cached-session-123',
            'channel' => 'web',
            'user_identifier' => 'cached-user@example.com',
            'current_step' => 'form',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ]
            ]
        ];

        // Save state (should cache it)
        $this->postJson('/api/states/save', $data);

        // Retrieve state (should use cache)
        $response = $this->postJson('/api/states/retrieve', [
            'user' => 'cached-user@example.com',
            'channel' => 'web',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'session_id' => 'cached-session-123',
                ]);

        // Verify cache was used by checking cache directly
        $cacheKey = "application_state:cached-user@example.com:web";
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function test_can_update_existing_state()
    {
        $applicationState = ApplicationState::factory()->create([
            'session_id' => 'update-session-123',
            'current_step' => 'form',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                ]
            ]
        ]);

        $updateData = [
            'session_id' => 'update-session-123',
            'channel' => $applicationState->channel,
            'user_identifier' => $applicationState->user_identifier,
            'current_step' => 'documents',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'emailAddress' => 'john.doe@example.com',
                ]
            ]
        ];

        $response = $this->postJson('/api/states/save', $updateData);

        $response->assertStatus(200);

        // Verify state was updated
        $applicationState->refresh();
        $this->assertEquals('documents', $applicationState->current_step);
        $this->assertEquals('Doe', $applicationState->form_data['formResponses']['lastName']);
    }

    public function test_handles_concurrent_state_updates()
    {
        $applicationState = ApplicationState::factory()->create([
            'session_id' => 'concurrent-session-123',
            'current_step' => 'form',
        ]);

        // Simulate concurrent updates
        $updateData1 = [
            'session_id' => 'concurrent-session-123',
            'channel' => $applicationState->channel,
            'user_identifier' => $applicationState->user_identifier,
            'current_step' => 'documents',
            'form_data' => ['field1' => 'value1']
        ];

        $updateData2 = [
            'session_id' => 'concurrent-session-123',
            'channel' => $applicationState->channel,
            'user_identifier' => $applicationState->user_identifier,
            'current_step' => 'summary',
            'form_data' => ['field2' => 'value2']
        ];

        // Both requests should succeed
        $response1 = $this->postJson('/api/states/save', $updateData1);
        $response2 = $this->postJson('/api/states/save', $updateData2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Last update should win
        $applicationState->refresh();
        $this->assertEquals('summary', $applicationState->current_step);
    }

    public function test_state_expiration_handling()
    {
        $expiredState = ApplicationState::factory()->create([
            'user_identifier' => 'expired-user@example.com',
            'channel' => 'web',
            'expires_at' => now()->subHour(), // Expired 1 hour ago
        ]);

        $response = $this->postJson('/api/states/retrieve', [
            'user' => 'expired-user@example.com',
            'channel' => 'web',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No active state found',
                ]);
    }

    public function test_rate_limiting_works()
    {
        $data = [
            'session_id' => 'rate-limit-test',
            'channel' => 'web',
            'user_identifier' => 'rate-limit-user@example.com',
            'current_step' => 'form',
            'form_data' => []
        ];

        // Make requests up to the rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/states/save', $data);
            if ($response->status() === 429) {
                break; // Hit rate limit
            }
        }

        // Next request should be rate limited
        $response = $this->postJson('/api/states/save', $data);
        $this->assertEquals(429, $response->status());
    }

    public function test_handles_malformed_json_gracefully()
    {
        $response = $this->postJson('/api/states/save', [
            'session_id' => 'malformed-test',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'form',
            'form_data' => 'invalid-json-string', // Should be array
        ]);

        $response->assertStatus(422);
    }

    public function test_cross_channel_state_isolation()
    {
        // Create states for same user on different channels
        $webState = ApplicationState::factory()->create([
            'user_identifier' => 'multi-channel@example.com',
            'channel' => 'web',
            'current_step' => 'form',
        ]);

        $whatsappState = ApplicationState::factory()->create([
            'user_identifier' => 'multi-channel@example.com',
            'channel' => 'whatsapp',
            'current_step' => 'documents',
        ]);

        // Retrieve web state
        $webResponse = $this->postJson('/api/states/retrieve', [
            'user' => 'multi-channel@example.com',
            'channel' => 'web',
        ]);

        // Retrieve WhatsApp state
        $whatsappResponse = $this->postJson('/api/states/retrieve', [
            'user' => 'multi-channel@example.com',
            'channel' => 'whatsapp',
        ]);

        $webResponse->assertStatus(200)
                   ->assertJson(['current_step' => 'form']);

        $whatsappResponse->assertStatus(200)
                        ->assertJson(['current_step' => 'documents']);
    }
}
