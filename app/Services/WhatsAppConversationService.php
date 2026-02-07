<?php

namespace App\Services;

use App\Services\StateManager;
use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppStateMachine;
// use App\Services\TwilioWhatsAppService; // DEPRECATED: Switched to Cloud API 2026-01-14
// use App\Services\RapiWhaService; // DEPRECATED: Switched to Twilio 2025-12-06
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsAppConversationService
{
    private WhatsAppCloudApiService $whatsAppService;
    private WhatsAppStateMachine $stateMachine;
    private $stateManager;
    private $syncService;

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
    public function processIncomingMessage(string $from, string $message): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        $originalMessage = trim($message);
        $lowerMessage = trim(strtolower($message));
        
        // Greetings that should always restart the conversation
        $greetings = ['hi', 'hie', 'hallo', 'hello', 'hey', 'hesi', 'makadii', 'start', 'begin', 'restart', 'menu'];

        Log::info("WhatsApp message received from {$phoneNumber}: {$originalMessage}");

        try {
            // If user sends a greeting, ALWAYS start fresh conversation
            if (in_array($lowerMessage, $greetings)) {
                Log::info("Greeting received, starting fresh conversation", ['message' => $lowerMessage]);
                $this->stateMachine->startConversation($from);
                return;
            }
            
            // Try to get existing conversation state
            $state = $this->stateManager->retrieveState($phoneNumber, 'whatsapp');
            
            if (!$state) {
                // New conversation without greeting
                $this->sendWelcomeMessage($from);
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
            $this->whatsAppService->sendMessage($from, "Sorry, something went wrong. Please try again or say 'hi' to start.");
        }
    }

    /**
     * Handle new conversation
     */
    private function handleNewConversation(string $from, string $message): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);

        // Microbiz greetings - main entry point
        $microbizGreetings = ['hi', 'hie', 'hallo', 'hello', 'hey', 'hesi', 'makadii', 'start', 'begin'];
        
        if (in_array($message, $microbizGreetings)) {
            // Use state machine to start conversation
            $this->stateMachine->startConversation($from);
        }
        elseif (preg_match('/^resume\s+([a-z0-9]{6})$/i', $message, $matches)) {
            $this->resumeApplication($from, $matches[1]);
        } 
        else {
            $this->sendWelcomeMessage($from);
        }
    }

    /**
     * Handle existing conversation
     */
    private function handleExistingConversation(string $from, string $message, $state): void
    {
        $currentStep = $state->current_step ?? 'microbiz_main_menu';
        $formData = $state->form_data ?? [];

        switch ($currentStep) {
            // === Microbiz Flow ===
            case 'microbiz_main_menu':
                $this->handleMicrobizMainMenu($from, $message, $state);
                break;
            case 'employment_check':
                $this->handleEmploymentCheck($from, $message, $state);
                break;
            case 'unemployment_category':
                $this->handleUnemploymentCategory($from, $message, $state);
                break;
            case 'formal_employment_check':
                $this->handleFormalEmploymentCheck($from, $message, $state);
                break;
            case 'employer_category':
                $this->handleEmployerCategory($from, $message, $state);
                break;
            case 'sme_salary_method':
                $this->handleSmeSalaryMethod($from, $message, $state);
                break;
            case 'beneficiary_question':
                $this->handleBeneficiaryQuestion($from, $message, $state);
                break;
            case 'monitoring_question':
                $this->handleMonitoringQuestion($from, $message, $state);
                break;
            case 'training_question':
                $this->handleTrainingQuestion($from, $message, $state);
                break;
            case 'agent_offer_after_rejection':
                $this->handleAgentOfferResponse($from, $message, $state);
                break;
                
            // === Agent Application Flow ===
            case 'agent_age_check':
                $this->handleAgentAgeCheck($from, $message, $state);
                break;
            case 'agent_province':
                $this->handleAgentProvince($from, $message, $state);
                break;
            case 'agent_name':
                $this->handleAgentName($from, $message, $state);
                break;
            case 'agent_surname':
                $this->handleAgentSurname($from, $message, $state);
                break;
            case 'agent_gender':
                $this->handleAgentGender($from, $message, $state);
                break;
            case 'agent_age_range':
                $this->handleAgentAgeRange($from, $message, $state);
                break;
            case 'agent_voice_number':
                $this->handleAgentVoiceNumber($from, $message, $state);
                break;
            case 'agent_whatsapp_number':
                $this->handleAgentWhatsAppNumber($from, $message, $state);
                break;
            case 'agent_ecocash_number':
                $this->handleAgentEcocashNumber($from, $message, $state);
                break;
            // Note: ID upload handled via media messages in webhook controller
                
            default:
                $this->sendInvalidInput($from);
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
            $message .= "📊 *Sync Status:* Data synchronized at " . now()->format('H:i') . "\n";
            $message .= "📍 *Current Step:* " . ucfirst($syncResult['current_step']) . "\n\n";
            $message .= $this->getCurrentStepMessage($syncResult['current_step'], $linkedState->form_data);

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

    /**
     * Send welcome message
     */
    private function sendWelcomeMessage(string $from): void
    {
        // Use the state machine to start a conversation with Adala greeting
        $this->stateMachine->startConversation($from);
    }

    /**
     * Send invalid input message
     */
    private function sendInvalidInput(string $from, string $customMessage = null): void
    {
        $message = $customMessage ?? "❌ Invalid input. Please try again.";
        $this->whatsAppService->sendMessage($from, $message);
    }

    // =====================================================
    // MICROBIZ ZIMBABWE CONVERSATION FLOW
    // =====================================================
    
    /**
     * Show Microbiz main menu with 5 options
     */
    private function showMicrobizMainMenu(string $from): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        $sessionId = 'whatsapp_' . $phoneNumber;
        
        Log::info("showMicrobizMainMenu called", ['from' => $from, 'phoneNumber' => $phoneNumber]);
        
        // Initialize Microbiz state
        $this->stateManager->saveState(
            $sessionId,
            'whatsapp',
            $phoneNumber,
            'microbiz_main_menu',
            [],
            ['phone_number' => $phoneNumber, 'started_at' => now(), 'flow_type' => 'microbiz']
        );
        
        Log::info("State saved for Microbiz menu", ['sessionId' => $sessionId]);
        
        // TODO: Get user's name from WhatsApp API if possible
        $userName = "there"; // Default fallback
        
        $message = "Hello *{$userName}*, welcome to *Microbiz Zimbabwe*, the home of innovation and where entrepreneurs are born.\n\n";
        $message .= "How can I help you today?\n\n";
        $message .= "1. Apply to become an online agent and earn a passive income through referral commissions\n";
        $message .= "2. Purchase microbiz starter pack for cash\n";
        $message .= "3. Purchase microbiz starter pack for credit\n";
        $message .= "4. Purchase gadgets, furniture, solar systems, laptops, cellphones, kitchenware for cash\n";
        $message .= "5. Purchase gadgets, furniture, solar systems, laptops, cellphones, kitchenware for credit\n\n";
        $message .= "Reply with the number of your choice (1-5).";
        
        Log::info("Attempting to send WhatsApp message", ['to' => $from, 'messageLength' => strlen($message)]);
        
        $result = $this->whatsAppService->sendMessage($from, $message);
        
        Log::info("WhatsApp sendMessage result", ['to' => $from, 'success' => $result]);
    }
    
    /**
     * Handle Microbiz main menu selection
     */
    private function handleMicrobizMainMenu(string $from, string $message, $state): void
    {
        Log::info("handleMicrobizMainMenu called", ['from' => $from, 'message' => $message, 'currentStep' => $state->current_step]);
        
        if (!in_array($message, ['1', '2', '3', '4', '5'])) {
            Log::info("handleMicrobizMainMenu: invalid input", ['message' => $message]);
            $this->sendInvalidInput($from, "Please select a number from 1-5.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['main_menu_choice' => $message]);
        
        Log::info("handleMicrobizMainMenu: processing choice", ['choice' => $message]);
        
        switch ($message) {
            case '1':
                // Agent application flow
                Log::info("handleMicrobizMainMenu: calling startAgentApplication");
                $this->startAgentApplication($from, $state);
                break;
                
            case '2':
            case '4':
                // Cash purchase - redirect to external website
                $this->redirectToWebsiteForCash($from, $state, $message);
                break;
                
            case '3':
            case '5':
                // Credit purchase - start eligibility check
                $this->startCreditEligibilityCheck($from, $state, $message);
                break;
        }
    }
    
    /**
     * Redirect to external website for cash purchases
     */
    private function redirectToWebsiteForCash(string $from, $state, string $choice): void
    {
        $productType = ($choice == '2') ? 'Starter Pack' : 'Gadgets/Furniture';
        
        $message = "✅ Thank you for choosing to purchase *{$productType}* for cash!\n\n";
        $message .= "Please proceed to our website to complete your purchase:\n\n";
        $message .= "🌐 *https://microbizimbabwe.co.zw*\n\n";
        $message .= "Or tap the link below to get started! 👇";
        
        $this->whatsAppService->sendMessage($from, $message);
        
        // End session
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'completed',
            array_merge($state->form_data ?? [], ['outcome' => 'redirected_to_website_for_cash']),
            $state->metadata ?? []
        );
    }
    
    /**
     * Start credit eligibility check
     */
    private function startCreditEligibilityCheck(string $from, $state, string $choice): void
    {
        $productType = ($choice == '3') ? 'Starter Pack' : 'Gadgets/Furniture';
        
        $formData = array_merge($state->form_data ?? [], [
            'credit_product_type' => $productType,
            'credit_product_choice' => $choice
        ]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'employment_check',
            $formData,
            $state->metadata ?? []
        );
        
        $message = "📋 Let's check your eligibility for credit purchase of *{$productType}*.\n\n";
        $message .= "Are you currently employed?\n\n";
        $message .= "Reply with:\n";
        $message .= "• *YES* if you are employed\n";
        $message .= "• *NO* if you are not employed";
        
        $this->whatsAppService->sendMessage($from, $message);
    }
    
    /**
     * Handle employment check response
     */
    private function handleEmploymentCheck(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['yes', 'no'])) {
            $this->sendInvalidInput($from, "Please reply with YES or NO.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['is_employed' => $message === 'yes']);
        
        if ($message === 'yes') {
            // Check if formally employed
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'formal_employment_check',
                $formData,
                $state->metadata ?? []
            );
            
           $msg = "Great! Are you *formally employed*?\n\n";
            $msg .= "Reply with:\n";
            $msg .= "• *YES* if formally employed (receiving salary into a bank account)\n";
            $msg .= "• *NO* if informally employed";
            
            $this->whatsAppService->sendMessage($from, $msg);
        } else {
            // Not employed - check category
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'unemployment_category',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "Thank you. Which category best describes your situation?\n\n";
            $msg .= "1. Government Pensioner\n";
            $msg .= "2. Self-employed individual\n";
            $msg .= "3. Unemployed\n";
            $msg .= "4. School leaver\n\n";
            $msg .= "Reply with the number (1-4).";
            
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    
    /**
     * Handle unemployment category selection
     */
    private function handleUnemploymentCategory(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2', '3', '4'])) {
            $this->sendInvalidInput($from, "Please select a number from 1-4.");
            return;
        }
        
        $categories = [
            '1' => 'pensioner',
            '2' => 'self_employed',
            '3' => 'unemployed',
            '4' => 'school_leaver'
        ];
        
        $category = $categories[$message];
        $formData = array_merge($state->form_data ?? [], ['unemployment_category' => $category]);
        
        if (in_array($category, ['pensioner', 'unemployed', 'school_leaver'])) {
            // Recommend agent program
            $this->recommendAgentProgram($from, $state, $category);
        } else {
            // Self-employed - currently not eligible
            $msg = "Thank you for your interest in us.\n\n";
            $msg .= "Currently the program is aimed at formally employed individuals who are receiving their salary into a bank account.\n\n";
            $msg .= "Kindly come back to us at a later date to check if the scope of the program has expanded.\n\n";
            $msg .= "💡 However, you might be interested in becoming an online agent! Would you like to proceed with an agent application? Reply with YES or NO.";
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'agent_offer_after_rejection',
                $formData,
                $state->metadata ?? []
            );
            
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    
    /**
     * Handle formal employment check
     */
    private function handleFormalEmploymentCheck(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['yes', 'no'])) {
            $this->sendInvalidInput($from, "Please reply with YES or NO.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['is_formally_employed' => $message === 'yes']);
        
        if ($message === 'no') {
            // Not formally employed
            $msg = "Currently the program is aimed at formally employed individuals who are receiving their salary into a bank account.\n\n";
            $msg .= "Kindly come back to us at a later date to check if the scope of the program has expanded.\n\n";
            $msg .= "Thank you for your interest in our program. Please revisit us in 6 months time.";
            
            $this->whatsAppService->sendMessage($from, $msg);
            
            // Mark as completed
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                array_merge($formData, ['outcome' => 'not_eligible_informal_employment']),
                $state->metadata ?? []
            );
        } else {
            // Formally employed - proceed to employer category
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'employer_category',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "Excellent! Which category best describes your employer?\n\n";
            $msg .= "1. Gov/Parastatal/Quasi Parastatal\n";
            $msg .= "2. Public Company\n";
            $msg .= "3. Private Company/Organisation\n";
            $msg .= "4. Church-run educational institution\n";
            $msg .= "5. N.G.O\n";
            $msg .= "6. SME\n\n";
            $msg .= "Reply with the number (1-6).";
            
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    
    /**
     * Handle employer category selection
     */
    private function handleEmployerCategory(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2', '3', '4', '5', '6'])) {
            $this->sendInvalidInput($from, "Please select a number from 1-6.");
            return;
        }
        
        $categories = [
            '1' => 'government_parastatal',
            '2' => 'public_company',
            '3' => 'private_company',
            '4' => 'church_educational',
            '5' => 'ngo',
            '6' => 'sme'
        ];
        
        $category = $categories[$message];
        $formData = array_merge($state->form_data ?? [], ['employer_category' => $category]);
        
        if ($category === 'sme') {
            // Ask how they receive salary
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'sme_salary_method',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "How do you receive your salary?\n\n";
            $msg .= "1. In cash\n";
            $msg .= "2. Deposited in a bank\n\n";
            $msg .= "Reply with 1 or 2.";
            
            $this->whatsAppService->sendMessage($from, $msg);
        } else {
            // Proceed to beneficiary question
            $this->askBeneficiaryQuestion($from, $state, $formData);
        }
    }
    
    /**
     * Handle SME salary method
     */
    private function handleSmeSalaryMethod(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2'])) {
            $this->sendInvalidInput($from, "Please reply with 1 or 2.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['salary_method' => $message === '2' ? 'bank' : 'cash']);
        
        if ($message === '1') {
            // Cash - not eligible
            $msg = "Currently the program is aimed at formally employed individuals who are receiving their salary into a bank account.\n\n";
            $msg .= "Kindly come back to us at a later date to check if the scope of the program has expanded.\n\n";
            $msg .= "Thank you for your interest in our program. Please revisit us in 6 months time.";
            
            $this->whatsAppService->sendMessage($from, $msg);
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                array_merge($formData, ['outcome' => 'not_eligible_cash_salary']),
                $state->metadata ?? []
            );
        } else {
            // Bank deposit - proceed
            $this->askBeneficiaryQuestion($from, $state, $formData);
        }
    }
    
    /**
     * Ask beneficiary question
     */
    private function askBeneficiaryQuestion(string $from, $state, array $formData): void
    {
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'beneficiary_question',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "Who do you want to open the microbusiness for?\n\n";
        $msg .= "Reply with:\n";
        $msg .= "• *SELF* - for yourself\n";
        $msg .= "• *OTHER* - for spouse, child, or relative";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle beneficiary question
     */
    private function handleBeneficiaryQuestion(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['self', 'other'])) {
            $this->sendInvalidInput($from, "Please reply with SELF or OTHER.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['beneficiary' => $message]);
        
        if ($message === 'other') {
            // Ask about monitoring
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'monitoring_question',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "Do you want us to monitor the business on your behalf?\n\n";
            $msg .= "Reply with YES or NO.";
            
            $this->whatsAppService->sendMessage($from, $msg);
        } else {
            // Skip monitoring, go to training
            $this->askTrainingQuestion($from, $state, $formData);
        }
    }
    
    /**
     * Handle monitoring question
     */
    private function handleMonitoringQuestion(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['yes', 'no'])) {
            $this->sendInvalidInput($from, "Please reply with YES or NO.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['wants_monitoring' => $message === 'yes']);
        $this->askTrainingQuestion($from, $state, $formData);
    }
    
    /**
     * Ask training question
     */
    private function askTrainingQuestion(string $from, $state, array $formData): void
    {
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'training_question',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "Do you want to receive practical and business enterprise training in the chosen micro business?\n\n";
        $msg .= "Reply with YES or NO.";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle training question
     */
    private function handleTrainingQuestion(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['yes', 'no'])) {
            $this->sendInvalidInput($from, "Please reply with YES or NO.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['wants_training' => $message === 'yes']);
        
        // Ready to proceed - send application link
        $this->sendCreditApplicationLink($from, $state, $formData);
    }
    
    /**
     * Send credit application link
     */
    private function sendCreditApplicationLink(string $from, $state, array $formData): void
    {
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'completed',
            array_merge($formData, ['outcome' => 'credit_application_ready']),
            $state->metadata ?? []
        );
        
        $msg = "✅ Perfect! You're eligible to proceed with your credit application.\n\n";
        $msg .= "Please click the link below to commence your application:\n\n";
        $msg .= "🌐 *" . config('app.url', 'https://bancosystem.fly.dev') . "/application*\n\n";
        $msg .= "Our team will guide you through the process. Good luck! 🎉";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Recommend agent program
     */
    private function recommendAgentProgram(string $from, $state, string $reason): void
    {
        $msg = "Thank you for your interest!\n\n";
        $msg .= "While you're not currently eligible for the credit program, ";
        $msg .= "we have an exciting opportunity for you! 💡\n\n";
        $msg .= "🌟 *Become an Online Agent* and earn passive income through referral commissions!\n\n";
        $msg .= "Would you like to apply? Reply with:\n";
        $msg .= "• *YES* to start your agent application\n";
        $msg .= "• *NO* to end this conversation";
        
        $formData = array_merge($state->form_data ?? [], ['recommended_reason' => $reason]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_offer_after_rejection',
            $formData,
            $state->metadata ?? []
        );
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent offer response
     */
    private function handleAgentOfferResponse(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['yes', 'no'])) {
            $this->sendInvalidInput($from, "Please reply with YES or NO.");
            return;
        }
        
        if ($message === 'yes') {
            $this->startAgentApplication($from, $state);
        } else {
            $msg = "Thank you for your time. Feel free to reach out if you change your mind!\n\n";
            $msg .= "Have a great day! 👋";
            
            $this->whatsAppService->sendMessage($from, $msg);
            
            $formData = array_merge($state->form_data ?? [], ['outcome' => 'declined_agent_offer']);
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                $formData,
                $state->metadata ?? []
            );
        }
    }
    
    // =====================================================
    // AGENT APPLICATION FLOW
    // =====================================================
    
    /**
     * Start agent application
     */
    private function startAgentApplication(string $from, $state): void
    {
        $formData = array_merge($state->form_data ?? [], ['agent_application_started' => true]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_age_check',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "🌟 *Agent Application*\n\n";
        $msg .= "Great choice! Let's get you set up as an online agent.\n\n";
        $msg .= "What best describes your age?\n\n";
        $msg .= "1. 18 and above\n";
        $msg .= "2. 17 and under\n\n";
        $msg .= "Reply with 1 or 2.";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent age check
     */
    private function handleAgentAgeCheck(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2'])) {
            $this->sendInvalidInput($from, "Please reply with 1 or 2.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['age_category' => $message === '1' ? '18+' : '17-']);
        
        if ($message === '2') {
            // Under 18 - not eligible
            $msg = "Thank you for your interest! ☺️\n\n";
            $msg .= "Unfortunately, you must be 18 years or older to become an agent.\n\n";
            $msg .= "Please come back when you turn 18. We'd love to have you on board then! 🎉";
            
            $this->whatsAppService->sendMessage($from, $msg);
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                array_merge($formData, ['outcome' => 'agent_too_young']),
                $state->metadata ?? []
            );
        } else {
            // 18+ - proceed with application
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'agent_province',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "🎉 Congratulations on taking steps to achieve your financial freedom!\n\n";
            $msg .= "All you need is a smartphone. Let's get started!\n\n";
            $msg .= "📍 Which province do you reside in?\n\n";
            $msg .= "1. Harare\n";
            $msg .= "2. Bulawayo\n";
            $msg .= "3. Mash East\n";
            $msg .= "4. Mash West\n";
            $msg .= "5. Mash Central\n";
            $msg .= "6. Mash North\n";
            $msg .= "7. Mash South\n";
            $msg .= "8. Manicaland\n";
            $msg .= "9. Masvingo\n";
            $msg .= "10. Midlands\n\n";
            $msg .= "Reply with the number (1-10).";
            
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    
    /**
     * Handle agent province selection
     */
    private function handleAgentProvince(string $from, string $message, $state): void
    {
        $provinces = [
            '1' => 'Harare',
            '2' => 'Bulawayo',
            '3' => 'Mash East',
            '4' => 'Mash West',
            '5' => 'Mash Central',
            '6' => 'Mash North',
            '7' => 'Mash South',
            '8' => 'Manicaland',
            '9' => 'Masvingo',
            '10' => 'Midlands'
        ];
        
        if (!isset($provinces[$message])) {
            $this->sendInvalidInput($from, "Please select a number from 1-10.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['province' => $provinces[$message]]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_name',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "Great! Now let's get your personal details.\n\n";
        $msg .= "Please provide your *First Name*:";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
   /**
     * Handle agent name input
     */
    private function handleAgentName(string $from, string $message, $state): void
    {
        $formData = $state->form_data ?? [];
        
        if (!isset($formData['first_name'])) {
            // Collecting first name
            $formData['first_name'] = trim($message);
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'agent_surname',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "Thank you! Now please provide your *Surname*:";
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    
    /**
     * Handle agent surname input
     */
    private function handleAgentSurname(string $from, string $message, $state): void
    {
        $formData = array_merge($state->form_data ?? [], ['surname' => trim($message)]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_gender',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "What is your gender?\n\n";
        $msg .= "Reply with:\n";
        $msg .= "• *MALE*\n";
        $msg .= "• *FEMALE*\n";
        $msg .= "• *OTHER*";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent gender input
     */
    private function handleAgentGender(string $from, string $message, $state): void
    {
        $message = strtolower(trim($message));
        
        if (!in_array($message, ['male', 'female', 'other'])) {
            $this->sendInvalidInput($from, "Please reply with MALE, FEMALE, or OTHER.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['gender' => ucfirst($message)]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_age_range',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "What is your age range?\n\n";
        $msg .= "1. 18-22\n";
        $msg .= "2. 23-30\n";
        $msg .= "3. 31-40\n";
        $msg .= "4. 41-50\n";
        $msg .= "5. 51-60\n";
        $msg .= "6. 60+\n\n";
        $msg .= "Reply with the number (1-6).";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent age range
     */
    private function handleAgentAgeRange(string $from, string $message, $state): void
    {
        $ageRanges = [
            '1' => '18-22',
            '2' => '23-30',
            '3' => '31-40',
            '4' => '41-50',
            '5' => '51-60',
            '6' => '60+'
        ];
        
        if (!isset($ageRanges[$message])) {
            $this->sendInvalidInput($from, "Please select a number from 1-6.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['age_range' => $ageRanges[$message]]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_voice_number',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "Now let's collect your contact details.\n\n";
        $msg .= "📞 Please provide your *voice number* (to receive SMS):\n\n";
        $msg .= "Example: 0771234567";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent voice number
     */
    private function handleAgentVoiceNumber(string $from, string $message, $state): void
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', trim($message));
        
        if (strlen($phoneNumber) < 9) {
            $this->sendInvalidInput($from, "Please provide a valid phone number.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['voice_number' => $phoneNumber]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_whatsapp_number',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "📱 Please provide your *WhatsApp number* (to receive links):\n\n";
        $msg .= "Example: 0771234567";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent WhatsApp number
     */
    private function handleAgentWhatsAppNumber(string $from, string $message, $state): void
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', trim($message));
        
        if (strlen($phoneNumber) < 9) {
            $this->sendInvalidInput($from, "Please provide a valid WhatsApp number.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['whatsapp_contact' => $phoneNumber]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_ecocash_number',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "💳 Finally, please provide your *EcoCash number* (to receive your commission):\n\n";
        $msg .= "Example: 0771234567";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent EcoCash number
     */
    private function handleAgentEcocashNumber(string $from, string $message, $state): void
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', trim($message));
        
        if (strlen($phoneNumber) < 9) {
            $this->sendInvalidInput($from, "Please provide a valid EcoCash number.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['ecocash_number' => $phoneNumber]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'agent_id_upload',
            $formData,
            $state->metadata ?? []
        );
        
        $msg = "📄 Almost done! Please upload your ID document.\n\n";
        $msg .= "Send a clear photo of the *front* of your ID card.";
        
        $this->whatsAppService->sendMessage($from, $msg);
    }
    
    /**
     * Handle agent ID upload
     */
    private function handleAgentIDUpload(string $from, string $mediaUrl, $state, string $side = 'front'): void
    {
        $formData = $state->form_data ?? [];
        
        if ($side === 'front') {
            $formData['id_front_url'] = $mediaUrl;
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'agent_id_back_upload',
                $formData,
                $state->metadata ?? []
            );
            
            $msg = "✅ Front of ID received!\n\n";
            $msg .= "Now please send a clear photo of the *back* of your ID card.";
            
            $this->whatsAppService->sendMessage($from, $msg);
        } else {
            // Back of ID received - complete application
            $formData['id_back_url'] = $mediaUrl;
            
            // Save to database
            $this->saveAgentApplication($from, $state, $formData);
        }
    }
    
    /**
     * Save agent application to database
     */
    private function saveAgentApplication(string $from, $state, array $formData): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        
        try {
            $application = \App\Models\AgentApplication::create([
                'whatsapp_number' => $phoneNumber,
                'session_id' => $state->session_id,
                'province' => $formData['province'] ?? '',
                'first_name' => $formData['first_name'] ?? '',
                'surname' => $formData['surname'] ?? '',
                'gender' => $formData['gender'] ?? '',
                'age_range' => $formData['age_range'] ?? '',
                'voice_number' => $formData['voice_number'] ?? '',
                'whatsapp_contact' => $formData['whatsapp_contact'] ?? '',
                'ecocash_number' => $formData['ecocash_number'] ?? '',
                'id_front_url' => $formData['id_front_url'] ?? null,
                'id_back_url' => $formData['id_back_url'] ?? null,
                'status' => 'pending',
            ]);
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                array_merge($formData, [
                    'outcome' => 'agent_application_submitted',
                    'application_id' => $application->id
                ]),
                $state->metadata ?? []
            );
            
            $msg = "🎉 *Application Submitted Successfully!*\n\n";
            $msg .= "Thank you, *{$formData['first_name']} {$formData['surname']}*!\n\n";
            $msg .= "Your agent application has been received. We will review your application and get back to you soon with your agent login details and referral link.\n\n";
            $msg .= "📧 You will be contacted via:\n";
            $msg .= "• SMS: {$formData['voice_number']}\n";
            $msg .= "• WhatsApp: {$formData['whatsapp_contact']}\n\n";
            $msg .= "Thank you for joining Microbiz Zimbabwe! 🚀";
            
            $this->whatsAppService->sendMessage($from, $msg);
            
            Log::info('Agent application submitted', [
                'application_id' => $application->id,
                'whatsapp_number' => $phoneNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save agent application: ' . $e->getMessage());
            
            $msg = "❌ Sorry, there was an error saving your application. Please try again later or contact support.";
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }
    

}