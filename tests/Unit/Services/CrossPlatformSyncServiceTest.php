<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CrossPlatformSyncService;
use App\Services\StateManager;
use App\Services\ReferenceCodeService;
use App\Models\ApplicationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CrossPlatformSyncServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private CrossPlatformSyncService $syncService;
    private StateManager $stateManager;
    private ReferenceCodeService $referenceCodeService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->referenceCodeService = $this->app->make(ReferenceCodeService::class);
        $this->stateManager = $this->app->make(StateManager::class);
        $this->syncService = $this->app->make(CrossPlatformSyncService::class);
    }
    
    public function test_synchronize_application_data_merges_states_correctly()
    {
        // Create web state
        $webState = ApplicationState::create([
            'session_id' => 'web_session_123',
            'channel' => 'web',
            'user_identifier' => 'web_session_123',
            'current_step' => 'product',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'category' => 'electronics'
            ],
            'metadata' => ['created_at_web' => now()->toISOString()],
            'expires_at' => now()->addHours(24)
        ]);
        
        // Create WhatsApp state with additional data
        $whatsappState = ApplicationState::create([
            'session_id' => 'whatsapp_1234567890',
            'channel' => 'whatsapp',
            'user_identifier' => '1234567890',
            'current_step' => 'business',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb',
                'selectedCategory' => ['id' => 'electronics', 'name' => 'Electronics'],
                'selectedBusiness' => ['id' => 'laptop', 'name' => 'Laptop']
            ],
            'metadata' => ['phone_number' => '1234567890'],
            'expires_at' => now()->addDays(7)
        ]);
        
        // Synchronize the data
        $result = $this->syncService->synchronizeApplicationData(
            $webState->session_id,
            $whatsappState->session_id
        );
        
        // Verify synchronization result
        $this->assertArrayHasKey('synchronized_states', $result);
        $this->assertArrayHasKey('current_step', $result);
        $this->assertArrayHasKey('sync_timestamp', $result);
        
        // Verify both states have merged data
        $webState->refresh();
        $whatsappState->refresh();
        
        $this->assertEquals('business', $webState->current_step);
        $this->assertEquals('business', $whatsappState->current_step);
        
        // Both should have the merged form data
        $this->assertArrayHasKey('selectedCategory', $webState->form_data);
        $this->assertArrayHasKey('selectedBusiness', $webState->form_data);
        $this->assertArrayHasKey('selectedCategory', $whatsappState->form_data);
        $this->assertArrayHasKey('selectedBusiness', $whatsappState->form_data);
    }
    
    public function test_switch_to_whatsapp_creates_whatsapp_state()
    {
        // Create web state
        $webState = ApplicationState::create([
            'session_id' => 'web_session_456',
            'channel' => 'web',
            'user_identifier' => 'web_session_456',
            'current_step' => 'employer',
            'form_data' => [
                'language' => 'en',
                'intent' => 'microBiz'
            ],
            'metadata' => [],
            'expires_at' => now()->addHours(24)
        ]);
        
        $phoneNumber = '9876543210';
        
        // Switch to WhatsApp
        $result = $this->syncService->switchToWhatsApp($webState->session_id, $phoneNumber);
        
        // Verify result structure
        $this->assertArrayHasKey('whatsapp_state', $result);
        $this->assertArrayHasKey('current_step', $result);
        $this->assertArrayHasKey('sync_timestamp', $result);
        
        // Verify WhatsApp state was created
        $whatsappState = ApplicationState::where('session_id', 'whatsapp_' . $phoneNumber)->first();
        $this->assertNotNull($whatsappState);
        $this->assertEquals('whatsapp', $whatsappState->channel);
        $this->assertEquals($phoneNumber, $whatsappState->user_identifier);
        $this->assertEquals('employer', $whatsappState->current_step);
        
        // Verify form data was normalized for WhatsApp
        $this->assertEquals('en', $whatsappState->form_data['language']);
        $this->assertEquals('microBiz', $whatsappState->form_data['intent']);
        
        // Verify metadata includes platform switch information
        $this->assertArrayHasKey('created_from_web', $whatsappState->metadata);
        $this->assertArrayHasKey('platform_switch_time', $whatsappState->metadata);
        $this->assertEquals($webState->session_id, $whatsappState->metadata['created_from_web']);
    }
    
    public function test_switch_to_web_creates_web_state()
    {
        // Create WhatsApp state
        $whatsappState = ApplicationState::create([
            'session_id' => 'whatsapp_5555555555',
            'channel' => 'whatsapp',
            'user_identifier' => '5555555555',
            'current_step' => 'form',
            'form_data' => [
                'language' => 'sn',
                'intent' => 'hirePurchase',
                'employer' => 'entrepreneur',
                'selectedCategory' => ['id' => 'vehicles', 'name' => 'Vehicles'],
                'formResponses' => ['firstName' => 'John', 'lastName' => 'Doe']
            ],
            'metadata' => ['phone_number' => '5555555555'],
            'expires_at' => now()->addDays(7)
        ]);
        
        // Switch to web
        $result = $this->syncService->switchToWeb($whatsappState->session_id);
        
        // Verify result structure
        $this->assertArrayHasKey('web_state', $result);
        $this->assertArrayHasKey('current_step', $result);
        $this->assertArrayHasKey('sync_timestamp', $result);
        
        // Verify web state was created
        $webState = $result['web_state'];
        $this->assertEquals('web', $webState->channel);
        $this->assertEquals('form', $webState->current_step);
        
        // Verify form data was normalized for web
        $this->assertEquals('sn', $webState->form_data['language']);
        $this->assertEquals('hirePurchase', $webState->form_data['intent']);
        $this->assertEquals('entrepreneur', $webState->form_data['employer']);
        
        // Verify metadata includes platform switch information
        $this->assertArrayHasKey('created_from_whatsapp', $webState->metadata);
        $this->assertArrayHasKey('platform_switch_time', $webState->metadata);
        $this->assertEquals($whatsappState->session_id, $webState->metadata['created_from_whatsapp']);
    }
    
    public function test_validate_data_consistency_detects_inconsistencies()
    {
        // Create two states with different data
        $state1 = ApplicationState::create([
            'session_id' => 'session_1',
            'channel' => 'web',
            'user_identifier' => 'session_1',
            'current_step' => 'product',
            'form_data' => [
                'language' => 'en',
                'intent' => 'hirePurchase',
                'employer' => 'goz-ssb'
            ],
            'expires_at' => now()->addHours(24)
        ]);
        
        $state2 = ApplicationState::create([
            'session_id' => 'session_2',
            'channel' => 'whatsapp',
            'user_identifier' => '1234567890',
            'current_step' => 'product',
            'form_data' => [
                'language' => 'sn', // Different language
                'intent' => 'microBiz', // Different intent
                'employer' => 'goz-ssb'
            ],
            'expires_at' => now()->addDays(7)
        ]);
        
        // Validate consistency
        $inconsistencies = $this->syncService->validateDataConsistency($state1, $state2);
        
        // Should detect language and intent inconsistencies
        $this->assertCount(2, $inconsistencies);
        
        $languageInconsistency = collect($inconsistencies)->firstWhere('field', 'language');
        $this->assertNotNull($languageInconsistency);
        $this->assertEquals('en', $languageInconsistency['state1_value']);
        $this->assertEquals('sn', $languageInconsistency['state2_value']);
        
        $intentInconsistency = collect($inconsistencies)->firstWhere('field', 'intent');
        $this->assertNotNull($intentInconsistency);
        $this->assertEquals('hirePurchase', $intentInconsistency['state1_value']);
        $this->assertEquals('microBiz', $intentInconsistency['state2_value']);
    }
    
    public function test_get_sync_status_returns_correct_status()
    {
        // Create synchronized states
        $state1 = ApplicationState::create([
            'session_id' => 'sync_session_1',
            'channel' => 'web',
            'user_identifier' => 'sync_session_1',
            'current_step' => 'product',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['last_sync' => now()->toISOString()],
            'expires_at' => now()->addHours(24)
        ]);
        
        $state2 = ApplicationState::create([
            'session_id' => 'sync_session_2',
            'channel' => 'whatsapp',
            'user_identifier' => '1234567890',
            'current_step' => 'product',
            'form_data' => ['language' => 'en', 'intent' => 'hirePurchase'],
            'metadata' => ['last_sync' => now()->toISOString()],
            'expires_at' => now()->addDays(7)
        ]);
        
        // Get sync status
        $status = $this->syncService->getSyncStatus($state1->session_id, $state2->session_id);
        
        // Should be synchronized
        $this->assertEquals('synchronized', $status['status']);
        $this->assertEquals(0, $status['inconsistencies_count']);
        $this->assertEmpty($status['inconsistencies']);
        $this->assertNotNull($status['last_sync']);
    }
    
    public function test_normalize_data_for_platform_converts_correctly()
    {
        $whatsappData = [
            'language' => 'en',
            'selectedCategory' => ['id' => 'electronics', 'name' => 'Electronics'],
            'selectedBusiness' => ['id' => 'laptop', 'name' => 'Laptop'],
            'formResponses' => ['firstName' => 'John']
        ];
        
        // Normalize for web
        $webData = $this->syncService->normalizeDataForPlatform($whatsappData, 'web');
        
        // Should convert selectedCategory to category
        $this->assertEquals('electronics', $webData['category']);
        $this->assertEquals('laptop', $webData['business']);
        $this->assertEquals('en', $webData['language']);
        
        // Normalize for WhatsApp
        $webDataInput = [
            'language' => 'sn',
            'category' => 'vehicles',
            'business' => 'car',
            'formResponses' => ['lastName' => 'Doe']
        ];
        
        $normalizedWhatsappData = $this->syncService->normalizeDataForPlatform($webDataInput, 'whatsapp');
        
        // Should maintain the data structure
        $this->assertEquals('sn', $normalizedWhatsappData['language']);
        $this->assertEquals('vehicles', $normalizedWhatsappData['category']);
        $this->assertEquals('car', $normalizedWhatsappData['business']);
    }
    
    public function test_synchronization_handles_missing_states_gracefully()
    {
        // Test with non-existent session IDs
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No application states found for synchronization');
        
        $this->syncService->synchronizeApplicationData('non_existent_1', 'non_existent_2');
    }
    
    public function test_switch_to_whatsapp_handles_non_existent_web_state()
    {
        // Test with non-existent web session
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Web session not found');
        
        $this->syncService->switchToWhatsApp('non_existent_web_session', '1234567890');
    }
    
    public function test_switch_to_web_handles_non_existent_whatsapp_state()
    {
        // Test with non-existent WhatsApp session
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('WhatsApp session not found');
        
        $this->syncService->switchToWeb('non_existent_whatsapp_session');
    }
}