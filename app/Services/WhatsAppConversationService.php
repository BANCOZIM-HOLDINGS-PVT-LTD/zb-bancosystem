<?php

namespace App\Services;

use App\Services\StateManager;
use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppStateMachine;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WhatsAppConversationService
{
    private WhatsAppCloudApiService $whatsAppService;
    private WhatsAppStateMachine $stateMachine;
    private StateManager $stateManager;
    private CrossPlatformSyncService $syncService;

    public function __construct(
        WhatsAppCloudApiService $whatsAppService, 
        StateManager $stateManager,
        CrossPlatformSyncService $syncService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->stateManager = $stateManager;
        $this->syncService = $syncService;
        // Create state machine internally to avoid DI issues
        $this->stateMachine = new WhatsAppStateMachine($whatsAppService, $stateManager);
    }

    /**
     * Process incoming WhatsApp message using State Machine
     */
    public function processIncomingMessage(string $from, string $message, ?string $senderName = null): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        $originalMessage = trim($message);
        $lowerMessage = trim(strtolower($message));
        
        // Greetings that should always restart the conversation
        $greetings = ['hi', 'hie', 'hallo', 'hello', 'hey', 'hesi', 'makadii', 'start', 'begin', 'restart', 'menu', 'reset'];

        Log::info("WhatsApp message received from {$phoneNumber}: {$originalMessage} (Name: " . ($senderName ?? 'N/A') . ")");

        try {
            // Update user name in metadata if provided
            if ($senderName) {
                $this->updateUserName($phoneNumber, $senderName);
            }

            // If user sends a greeting, ALWAYS start fresh conversation
            if (in_array($lowerMessage, $greetings)) {
                Log::info("Greeting received, starting fresh conversation", ['message' => $lowerMessage]);
                $this->stateMachine->startConversation($from, $senderName);
                return;
            }
            
            // Try to get existing conversation state
            $state = $this->stateManager->retrieveState($phoneNumber, 'whatsapp');
            
            if (!$state) {
                // New conversation without greeting
                Log::info("No existing state found, starting fresh conversation");
                $this->stateMachine->startConversation($from, $senderName);
            } else {
                // Continue existing conversation using state machine
                Log::info("Processing existing conversation", [
                    'currentStep' => $state->current_step,
                    'sessionId' => $state->session_id
                ]);
                // Pass ORIGINAL message casing to state machine for proper name matching
                $this->stateMachine->process($from, $originalMessage, $state);
            }
        } catch (\Exception $e) {
            Log::error("Error processing WhatsApp message: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->whatsAppService->sendMessage($from, "Sorry, something went wrong. Please try again or say 'hi' to start over.");
        }
    }

    /**
     * Update user name in state metadata
     */
    private function updateUserName(string $phoneNumber, string $name): void
    {
        try {
            $state = $this->stateManager->retrieveState($phoneNumber, 'whatsapp');
            if ($state) {
                $metadata = $state->metadata ?? [];
                // Only update if not set or different
                if (!isset($metadata['user_name']) || $metadata['user_name'] !== $name) {
                    $metadata['user_name'] = $name;
                    $state->update(['metadata' => $metadata]);
                    Log::info("Updated WhatsApp user name", ['phone' => $phoneNumber, 'name' => $name]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update user name: " . $e->getMessage());
        }
    }

    /**
     * Resume application from web
     */
    public function resumeApplication(string $from, string $resumeCode): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        
        // Find session by resume code
        $linkedState = $this->stateManager->getStateByResumeCode($resumeCode);
        
        if (!$linkedState) {
            $this->whatsAppService->sendMessage($from, "❌ Invalid resume code. Please check and try again or type 'start' to begin a new application.");
            return;
        }

        try {
            // Use sync service to properly switch from web to WhatsApp
            $syncResult = $this->syncService->switchToWhatsApp($linkedState->session_id, $phoneNumber);
            
            $message = "✅ *Application Resumed*\n\n";
            $message .= "Your application has been successfully synchronized with WhatsApp.\n\n";
            $message .= "📊 *Status:* Data synchronized at " . now()->format('H:i') . "\n";
            $message .= "📍 *Current Step:* " . ucfirst(str_replace('_', ' ', $syncResult['current_step'])) . "\n\n";
            $message .= "Please type 'hi' to continue or follow the instructions above.";

            $this->whatsAppService->sendMessage($from, $message);
            
            Log::info('Application resumed via WhatsApp', [
                'phone_number' => $phoneNumber,
                'resume_code' => $resumeCode,
                'web_session' => $linkedState->session_id,
                'current_step' => $syncResult['current_step']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to resume application via WhatsApp: ' . $e->getMessage());
            $this->whatsAppService->sendMessage($from, "❌ Sorry, there was an issue resuming your application. Please try again or contact support.");
        }
    }
}
