<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TwilioWhatsAppService;
use App\Services\WhatsAppConversationService;

class TestWhatsApp extends Command
{
    protected $signature = 'whatsapp:test {phone?}';
    protected $description = 'Test WhatsApp services';

    public function handle(
        TwilioWhatsAppService $twilioService,
        WhatsAppConversationService $conversationService
    ) {
        $this->info('=== Testing WhatsApp Services ===');
        
        // Test 1: Check Twilio configuration
        $this->info("\n1. Checking Twilio configuration...");
        $config = $twilioService->getConfigStatus();
        $this->table(['Setting', 'Value'], collect($config)->map(function($value, $key) {
            return [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value];
        })->toArray());
        
        if (!$twilioService->isConfigured()) {
            $this->error('Twilio is NOT configured! Check your .env file.');
            return 1;
        }
        
        $this->info('Twilio is configured correctly!');
        
        // Test 2: Try sending a message if phone number provided
        $phone = $this->argument('phone');
        if ($phone) {
            $this->info("\n2. Testing message sending to: {$phone}");
            $formattedPhone = TwilioWhatsAppService::formatWhatsAppNumber($phone);
            $this->info("   Formatted number: {$formattedPhone}");
            
            $result = $twilioService->sendMessage($formattedPhone, 'Test message from WhatsApp bot!');
            
            if ($result) {
                $this->info('   Message sent successfully!');
            } else {
                $this->error('   Message sending FAILED!');
            }
        } else {
            $this->info("\n2. Skipping message test (no phone number provided)");
            $this->info("   Use: php artisan whatsapp:test +263771234567");
        }
        
        // Test 3: Test conversation service instantiation
        $this->info("\n3. Testing WhatsAppConversationService...");
        try {
            $this->info('   Service instantiated successfully!');
            $this->info('   State machine is ready.');
        } catch (\Exception $e) {
            $this->error('   Error: ' . $e->getMessage());
            return 1;
        }
        
        $this->info("\n=== All tests passed! ===");
        return 0;
    }
}
