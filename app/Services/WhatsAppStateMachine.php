<?php

namespace App\Services;

use App\Enums\ConversationState;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Conversation State Machine
 * 
 * Manages state transitions and actions for WhatsApp conversations
 * Updated: New "Adala" persona with expanded menu and language selection
 */
class WhatsAppStateMachine
{
    private WhatsAppCloudApiService $whatsAppService;
    private StateManager $stateManager;
    
    // Website base URL for redirects
    private string $websiteUrl = 'https://bancosystem.fly.dev';
    
    /**
     * State transition map: current_state => [input => next_state]
     */
    private array $transitions = [
        // Language selection
        'language_selection' => [
            '1' => 'main_menu',    // English
            '2' => 'main_menu',    // ChiShona -> redirect to English
            '3' => 'main_menu',    // Ndau -> redirect to English
            '4' => 'main_menu',    // Chichewa -> redirect to English
        ],
        
        // Main menu - 11 options
        'main_menu' => [
            '1' => 'redirect_starter_pack_credit',
            '2' => 'redirect_gadgets_credit',
            '3' => 'redirect_chicken_projects',
            '4' => 'redirect_building_materials',
            '5' => 'redirect_driving_school',
            '6' => 'redirect_zimparks',
            '7' => 'redirect_school_fees',
            '8' => 'redirect_company_registration',
            '9' => 'agent_age_check',
            '10' => 'redirect_tracking',
            '11' => 'customer_service_wait',
        ],
        
        // Agent application flow (preserved from original)
        'agent_age_check' => [
            '1' => 'agent_province',  // 18 and above
            '2' => 'agent_underage',  // Under 18
        ],
        'agent_province' => [
            '1' => 'agent_name', '2' => 'agent_name', '3' => 'agent_name',
            '4' => 'agent_name', '5' => 'agent_name', '6' => 'agent_name',
            '7' => 'agent_name', '8' => 'agent_name', '9' => 'agent_name', '10' => 'agent_name',
        ],
        'agent_gender' => [
            '1' => 'agent_age_range', // Male
            '2' => 'agent_age_range', // Female
        ],
        'agent_age_range' => [
            '1' => 'agent_voice_number', '2' => 'agent_voice_number',
            '3' => 'agent_voice_number', '4' => 'agent_voice_number',
            '5' => 'agent_voice_number', '6' => 'agent_voice_number',
        ],
        
        // Idle timeout flow
        'idle_continue' => [
            'yes' => 'main_menu',
            'no' => 'survey_question',
        ],
        'survey_question' => [
            '1' => 'completed', '2' => 'completed', '3' => 'completed',
            '4' => 'completed', '5' => 'completed',
        ],
    ];
    
    /**
     * States that accept free text input (not restricted to specific options)
     */
    private array $freeTextStates = [
        'agent_name',
        'agent_surname',
        'agent_voice_number',
        'agent_whatsapp_number',
        'agent_ecocash_number',
    ];
    
    public function __construct(
        WhatsAppCloudApiService $whatsAppService,
        StateManager $stateManager
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->stateManager = $stateManager;
    }
    
    /**
     * Process incoming message and transition state
     */
    public function process(string $from, string $input, $state): void
    {
        $currentStep = $state->current_step ?? 'language_selection';
        $input = strtolower(trim($input));
        
        Log::info("StateMachine: Processing", [
            'from' => $from,
            'currentStep' => $currentStep,
            'input' => $input
        ]);
        
        // Check if this is a free text state
        if ($this->isFreeTextState($currentStep)) {
            $this->handleFreeTextInput($from, $input, $state, $currentStep);
            return;
        }
        
        // Get valid transitions for current state
        $validTransitions = $this->transitions[$currentStep] ?? null;
        
        if ($validTransitions === null) {
            Log::warning("StateMachine: Unknown state", ['state' => $currentStep]);
            $this->sendErrorMessage($from, "Something went wrong. Please say 'hi' to start over.");
            return;
        }
        
        // Check if input is valid for current state
        if (!isset($validTransitions[$input])) {
            Log::info("StateMachine: Invalid input for state", [
                'state' => $currentStep,
                'input' => $input,
                'validInputs' => array_keys($validTransitions)
            ]);
            $this->sendInvalidInputMessage($from, $currentStep);
            return;
        }
        
        // Execute transition
        $nextState = $validTransitions[$input];
        $this->executeTransition($from, $state, $currentStep, $nextState, $input);
    }
    
    /**
     * Check if state accepts free text input
     */
    private function isFreeTextState(string $state): bool
    {
        return in_array($state, $this->freeTextStates);
    }
    
    /**
     * Handle free text input for states that accept any text
     */
    private function handleFreeTextInput(string $from, string $input, $state, string $currentStep): void
    {
        $formData = $state->form_data ?? [];
        
        // Map current state to form field and next state
        $stateConfig = [
            'agent_name' => ['field' => 'first_name', 'next' => 'agent_surname'],
            'agent_surname' => ['field' => 'surname', 'next' => 'agent_gender'],
            'agent_voice_number' => ['field' => 'voice_number', 'next' => 'agent_whatsapp_number'],
            'agent_whatsapp_number' => ['field' => 'whatsapp_contact', 'next' => 'agent_ecocash_number'],
            'agent_ecocash_number' => ['field' => 'ecocash_number', 'next' => 'agent_id_upload'],
        ];
        
        $config = $stateConfig[$currentStep] ?? null;
        
        if (!$config) {
            Log::error("StateMachine: No config for free text state", ['state' => $currentStep]);
            return;
        }
        
        // Save input to form data
        $formData[$config['field']] = $input;
        
        // Transition to next state
        $this->executeTransition($from, $state, $currentStep, $config['next'], $input, $formData);
    }
    
    /**
     * Execute state transition
     */
    private function executeTransition(
        string $from, 
        $state, 
        string $fromState, 
        string $toState, 
        string $input,
        array $formData = null
    ): void {
        Log::info("StateMachine: Transitioning", [
            'from' => $fromState,
            'to' => $toState,
            'input' => $input
        ]);
        
        // Merge form data
        $formData = $formData ?? ($state->form_data ?? []);
        
        // Add input-specific data based on state
        $formData = $this->enrichFormData($fromState, $input, $formData);
        
        // Handle language selection - show "not available" for non-English
        if ($fromState === 'language_selection' && $input !== '1') {
            $this->whatsAppService->sendMessage($from, 
                "🌐 This language is not available at the moment. Please proceed with English.\n\n" .
                "_Rurimi urwu harusi rwuripo panguva ino. Ndapota enderera neChirungu._"
            );
        }
        
        // Save new state
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            $toState,
            $formData,
            $state->metadata ?? []
        );
        
        // Send response for new state
        $this->sendStateMessage($from, $toState, $formData);
    }
    
    /**
     * Enrich form data based on state transition
     */
    private function enrichFormData(string $state, string $input, array $formData): array
    {
        switch ($state) {
            case 'language_selection':
                $languages = ['English', 'ChiShona', 'Ndau', 'Chichewa'];
                $formData['selected_language'] = $languages[(int)$input - 1] ?? 'English';
                $formData['language'] = 'English'; // Always use English for now
                break;
            case 'main_menu':
                $formData['main_menu_choice'] = $input;
                break;
            case 'agent_age_check':
                $formData['is_adult'] = ($input === '1');
                break;
            case 'agent_province':
                $provinces = ['Harare', 'Bulawayo', 'Manicaland', 'Mashonaland Central', 
                              'Mashonaland East', 'Mashonaland West', 'Masvingo', 
                              'Matabeleland North', 'Matabeleland South', 'Midlands'];
                $formData['province'] = $provinces[(int)$input - 1] ?? $input;
                break;
            case 'agent_gender':
                $formData['gender'] = ($input === '1') ? 'Male' : 'Female';
                break;
            case 'agent_age_range':
                $ranges = ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
                $formData['age_range'] = $ranges[(int)$input - 1] ?? $input;
                break;
            case 'survey_question':
                $ratings = ['Very Poor', 'Poor', 'Average', 'Good', 'Excellent'];
                $formData['survey_rating'] = $ratings[(int)$input - 1] ?? $input;
                break;
        }
        
        return $formData;
    }
    
    /**
     * Send message for a given state
     */
    private function sendStateMessage(string $to, string $state, array $formData = []): void
    {
        $message = $this->getStateMessage($state, $formData);
        
        if ($message) {
            Log::info("StateMachine: Sending message for state", ['state' => $state, 'to' => $to]);
            $result = $this->whatsAppService->sendMessage($to, $message);
            Log::info("StateMachine: Message send result", ['success' => $result]);
        }
    }
    
    /**
     * Get message template for a state
     */
    private function getStateMessage(string $state, array $formData = []): ?string
    {
        switch ($state) {
            case 'main_menu':
                return $this->getMainMenuMessage();
                
            // Website redirects (options 1-8 and 10)
            case 'redirect_starter_pack_credit':
                return "📦 *Small Business Starter Pack on Credit*\n\n" .
                       "Please click here to view our catalogue and complete your application:\n\n" .
                       "🔗 {$this->websiteUrl}/starter-pack\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_gadgets_credit':
                return "📱 *Gadgets, Furniture & Solar Products on Credit*\n\n" .
                       "Please click here to view our catalogue and complete your application:\n\n" .
                       "🔗 {$this->websiteUrl}/products\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_chicken_projects':
                return "🐔 *Chicken Projects (Broilers, Hatchery)*\n\n" .
                       "Please click here to learn more about our poultry packages:\n\n" .
                       "🔗 {$this->websiteUrl}/poultry\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_building_materials':
                return "🧱 *Building Materials*\n\n" .
                       "Please click here to view our building materials catalogue:\n\n" .
                       "🔗 {$this->websiteUrl}/building-materials\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_driving_school':
                return "🚗 *Driving School Fees Assistance*\n\n" .
                       "We can help you from provisional to license! Please click here:\n\n" .
                       "🔗 {$this->websiteUrl}/driving-school\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_zimparks':
                return "🦁 *Zimparks Package Booking*\n\n" .
                       "Please click here to explore our Zimparks holiday packages:\n\n" .
                       "🔗 {$this->websiteUrl}/zimparks\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_school_fees':
                return "🎓 *School Fees Assistance*\n\n" .
                       "Available for ZB institutions only. Please click here:\n\n" .
                       "🔗 {$this->websiteUrl}/school-fees\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_company_registration':
                return "🏢 *Company Registration Assistance*\n\n" .
                       "We can help with registration fees and paperwork! Please click here:\n\n" .
                       "🔗 {$this->websiteUrl}/company-registration\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_tracking':
                return "📍 *Track Your Delivery or Application*\n\n" .
                       "Please click here to login and track your status:\n\n" .
                       "🔗 {$this->websiteUrl}/track\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            // Customer service flow
            case 'customer_service_wait':
                return "👨‍💼 *Connecting to Customer Service*\n\n" .
                       "Please wait a few minutes while we connect you to a representative...\n\n" .
                       "🔄 A team member will be with you shortly.";
                       
            // Agent application flow (option 9 - preserved)
            case 'agent_age_check':
                return "🌟 *Agent Application*\n\n" .
                       "Great choice! Let's get you set up as an online agent.\n\n" .
                       "What best describes your age?\n\n" .
                       "1. 18 and above\n" .
                       "2. 17 and under\n\n" .
                       "Reply with 1 or 2.";
                       
            case 'agent_underage':
                return "❌ *Sorry!*\n\n" .
                       "You must be 18 or older to become an agent.\n\n" .
                       "Thank you for your interest! Feel free to reach out when you're 18. 👋";
                       
            case 'agent_province':
                return "📍 *Province Selection*\n\n" .
                       "Which province are you based in?\n\n" .
                       "1. Harare\n2. Bulawayo\n3. Manicaland\n" .
                       "4. Mashonaland Central\n5. Mashonaland East\n" .
                       "6. Mashonaland West\n7. Masvingo\n" .
                       "8. Matabeleland North\n9. Matabeleland South\n10. Midlands\n\n" .
                       "Reply with a number (1-10).";
                       
            case 'agent_name':
                return "👤 *Personal Details*\n\n" .
                       "Please enter your *first name*:";
                       
            case 'agent_surname':
                return "Please enter your *surname*:";
                       
            case 'agent_gender':
                return "What is your gender?\n\n" .
                       "1. Male\n2. Female\n\n" .
                       "Reply with 1 or 2.";
                       
            case 'agent_age_range':
                return "What is your age range?\n\n" .
                       "1. 18-24\n2. 25-34\n3. 35-44\n" .
                       "4. 45-54\n5. 55-64\n6. 65+\n\n" .
                       "Reply with a number (1-6).";
                       
            case 'agent_voice_number':
                return "📱 *Contact Details*\n\n" .
                       "Please enter your *voice/call number* (e.g. 0771234567):";
                       
            case 'agent_whatsapp_number':
                return "Please enter your *WhatsApp number* (e.g. 0771234567):";
                       
            case 'agent_ecocash_number':
                return "Please enter your *EcoCash number* for commission payments (e.g. 0771234567):";
                       
            case 'agent_id_upload':
                return "📸 *ID Verification*\n\n" .
                       "Almost done! Please send a clear photo of the *front* of your ID card.";
                       
            // Idle timeout flow
            case 'idle_continue':
                return "⏰ *Session Idle*\n\n" .
                       "This session has been idle for some time.\n\n" .
                       "Would you like to continue with another service?\n\n" .
                       "Reply with *YES* or *NO*.";
                       
            case 'survey_question':
                return "📝 *Quick Survey*\n\n" .
                       "How was your experience today?\n\n" .
                       "1. ⭐ Very Poor\n" .
                       "2. ⭐⭐ Poor\n" .
                       "3. ⭐⭐⭐ Average\n" .
                       "4. ⭐⭐⭐⭐ Good\n" .
                       "5. ⭐⭐⭐⭐⭐ Excellent\n\n" .
                       "Reply with a number (1-5).";
                       
            case 'completed':
                $rating = $formData['survey_rating'] ?? null;
                $thankYou = $rating ? "Thank you for rating us *{$rating}*! " : "";
                return "🙏 {$thankYou}Thank you for your interest in *Bancosystem*!\n\n" .
                       "Come again soon! 👋\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            default:
                return null;
        }
    }
    
    /**
     * Get main menu message
     */
    private function getMainMenuMessage(): string
    {
        return "🛒 *WHAT PRODUCT OR SERVICE DO YOU WANT TO ACQUIRE?*\n\n" .
               "1. A small business starter pack on credit\n" .
               "2. Gadgets, furniture, solar products etc on credit\n" .
               "3. Chicken Projects (broilers, hatchery)\n" .
               "4. Building Materials\n" .
               "5. Driving school fees assistance (provisional to license)\n" .
               "6. Zimparks Package booking\n" .
               "7. School Fees Assistance (for ZB institutions only)\n" .
               "8. Assistance to register a company (fees and paperwork)\n\n" .
               "—————— or ——————\n\n" .
               "9. Apply to become an online agent\n" .
               "10. Login to track your delivery or application status\n" .
               "11. Talk to a customer services representative\n\n" .
               "Reply with a number (1-11).";
    }
    
    /**
     * Send invalid input message based on current state
     */
    private function sendInvalidInputMessage(string $to, string $state): void
    {
        $message = match($state) {
            'language_selection' => "Please select a number from 1-4.",
            'main_menu' => "Please select a number from 1-11.",
            'agent_age_check' => "Please reply with 1 or 2.",
            'agent_province' => "Please reply with a number from 1-10.",
            'agent_gender' => "Please reply with 1 or 2.",
            'agent_age_range' => "Please reply with a number from 1-6.",
            'idle_continue' => "Please reply with YES or NO.",
            'survey_question' => "Please reply with a number from 1-5.",
            default => "Invalid input. Please try again.",
        };
        
        $this->whatsAppService->sendMessage($to, "❌ {$message}");
    }
    
    /**
     * Send error message
     */
    private function sendErrorMessage(string $to, string $message): void
    {
        $this->whatsAppService->sendMessage($to, "❌ {$message}");
    }
    
    /**
     * Start a new conversation with greeting and language selection
     */
    public function startConversation(string $from, ?string $userName = null): void
    {
        $phoneNumber = WhatsAppCloudApiService::extractPhoneNumber($from);
        $sessionId = 'whatsapp_' . $phoneNumber;
        
        Log::info("StateMachine: Starting new conversation", ['from' => $from, 'sessionId' => $sessionId]);
        
        // Initialize state with language selection
        $this->stateManager->saveState(
            $sessionId,
            'whatsapp',
            $phoneNumber,
            'language_selection',
            ['phone_number' => $phoneNumber],
            ['phone_number' => $phoneNumber, 'started_at' => now(), 'flow_type' => 'adala']
        );
        
        // Get user display name
        $displayName = $userName ?: 'there';
        
        // Send welcome message with Adala persona
        $message = "Hello *{$displayName}* 👋\n\n";
        $message .= "I am *Adala*, consider me your digital uncle, here to assist you to get the best digital experience, because we are family.\n\n";
        $message .= "Welcome to *Bancosystem*, the home of innovation.\n\n";
        $message .= "🌐 *Select your preferred language:*\n\n";
        $message .= "1. English\n";
        $message .= "2. ChiShona\n";
        $message .= "3. Ndau\n";
        $message .= "4. Chichewa\n\n";
        $message .= "Reply with a number (1-4).";
        
        $result = $this->whatsAppService->sendMessage($from, $message);
        Log::info("StateMachine: Welcome message sent", ['success' => $result]);
    }
    
    /**
     * Send idle timeout message (called by scheduler after 3 minutes of inactivity)
     */
    public function sendIdleTimeoutMessage(string $from, $state): void
    {
        // Update state to idle_continue
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'idle_continue',
            $state->form_data ?? [],
            $state->metadata ?? []
        );
        
        $this->sendStateMessage($from, 'idle_continue', $state->form_data ?? []);
    }
}
