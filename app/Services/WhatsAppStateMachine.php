<?php

namespace App\Services;

use App\Enums\ConversationState;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Conversation State Machine
 * 
 * Manages state transitions and actions for WhatsApp conversations
 */
class WhatsAppStateMachine
{
    private TwilioWhatsAppService $whatsAppService;
    private StateManager $stateManager;
    
    /**
     * State transition map: current_state => [input => next_state]
     */
    private array $transitions = [
        'microbiz_main_menu' => [
            '1' => 'agent_age_check',
            '2' => 'redirect_cash',
            '3' => 'employment_check',
            '4' => 'redirect_cash',
            '5' => 'employment_check',
        ],
        'agent_age_check' => [
            '1' => 'agent_province',  // 18 and above
            '2' => 'agent_underage',  // Under 18
        ],
        'agent_province' => [
            '1' => 'agent_name', '2' => 'agent_name', '3' => 'agent_name',
            '4' => 'agent_name', '5' => 'agent_name', '6' => 'agent_name',
            '7' => 'agent_name', '8' => 'agent_name', '9' => 'agent_name', '10' => 'agent_name',
        ],
        // Agent name, surname, etc. accept free text - handled specially
        'agent_gender' => [
            '1' => 'agent_age_range', // Male
            '2' => 'agent_age_range', // Female
        ],
        'agent_age_range' => [
            '1' => 'agent_voice_number', '2' => 'agent_voice_number',
            '3' => 'agent_voice_number', '4' => 'agent_voice_number',
            '5' => 'agent_voice_number', '6' => 'agent_voice_number',
        ],
        // Phone numbers accept free text
        'employment_check' => [
            'yes' => 'formal_employment_check',
            'no' => 'unemployment_category',
        ],
        'formal_employment_check' => [
            'yes' => 'employer_category',
            'no' => 'agent_offer_after_rejection',
        ],
        'unemployment_category' => [
            '1' => 'redirect_credit', // Government Pensioner
            '2' => 'agent_offer_after_rejection', // Self-employed
            '3' => 'agent_offer_after_rejection', // Unemployed
            '4' => 'agent_offer_after_rejection', // School leaver
        ],
        'employer_category' => [
            '1' => 'redirect_credit', // Government
            '2' => 'sme_salary_method', // SME
            '3' => 'redirect_credit', // Parastatal
            '4' => 'agent_offer_after_rejection', // Other
        ],
        'sme_salary_method' => [
            '1' => 'beneficiary_question', // Bank transfer
            '2' => 'agent_offer_after_rejection', // Cash
        ],
        'beneficiary_question' => [
            'yes' => 'redirect_credit',
            'no' => 'monitoring_question',
        ],
        'monitoring_question' => [
            'yes' => 'redirect_credit',
            'no' => 'training_question',
        ],
        'training_question' => [
            'yes' => 'redirect_credit',
            'no' => 'agent_offer_after_rejection',
        ],
        'agent_offer_after_rejection' => [
            'yes' => 'agent_age_check',
            'no' => 'completed',
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
        TwilioWhatsAppService $whatsAppService,
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
        $currentStep = $state->current_step ?? 'microbiz_main_menu';
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
            case 'microbiz_main_menu':
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
            case 'employment_check':
                $formData['is_employed'] = ($input === 'yes');
                break;
            case 'formal_employment_check':
                $formData['is_formally_employed'] = ($input === 'yes');
                break;
            case 'unemployment_category':
                $categories = ['Government Pensioner', 'Self-employed', 'Unemployed', 'School Leaver'];
                $formData['unemployment_category'] = $categories[(int)$input - 1] ?? $input;
                break;
            case 'employer_category':
                $employers = ['Government', 'SME/Private Company', 'Parastatal', 'Other'];
                $formData['employer_category'] = $employers[(int)$input - 1] ?? $input;
                break;
            case 'sme_salary_method':
                $formData['salary_via_bank'] = ($input === '1');
                break;
                
            case 'beneficiary_question':
            case 'monitoring_question':
            case 'training_question':
                $fieldName = str_replace('_question', '', $state);
                $formData[$fieldName] = ($input === 'yes');
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
                       
            case 'redirect_cash':
                $productType = ($formData['main_menu_choice'] ?? '2') === '2' ? 'Starter Pack' : 'Gadgets/Furniture';
                return "✅ Thank you for choosing to purchase *{$productType}* for cash!\n\n" .
                       "Please proceed to our website to complete your purchase:\n\n" .
                       "🌐 *https://bancosystem.fly.dev*\n\n" .
                       "Or tap the link below to get started! 👇";
                       
            case 'redirect_credit':
                return "✅ *Great news!*\n\n" .
                       "Based on your responses, you may be eligible for credit.\n\n" .
                       "Please visit our website to complete your application:\n\n" .
                       "🌐 *https://bancosystem.fly.dev*\n\n" .
                       "Our team will review your application and get back to you.";
                       
            case 'employment_check':
                $productType = ($formData['main_menu_choice'] ?? '3') === '3' ? 'Starter Pack' : 'Gadgets/Furniture';
                return "📋 Let's check your eligibility for credit purchase of *{$productType}*.\n\n" .
                       "Are you currently employed?\n\n" .
                       "Reply with:\n" .
                       "• *YES* if you are employed\n" .
                       "• *NO* if you are not employed";
                       
            case 'formal_employment_check':
                return "Great! Are you *formally employed*?\n\n" .
                       "Reply with:\n" .
                       "• *YES* if formally employed (receiving salary into a bank account)\n" .
                       "• *NO* if informally employed";
                       
            case 'unemployment_category':
                return "Thank you. Which category best describes your situation?\n\n" .
                       "1. Government Pensioner\n" .
                       "2. Self-employed individual\n" .
                       "3. Unemployed\n" .
                       "4. School leaver\n\n" .
                       "Reply with the number (1-4).";
                       
            case 'employer_category':
                return "What type of employer do you work for?\n\n" .
                       "1. Government\n" .
                       "2. SME/Private Company\n" .
                       "3. Parastatal\n" .
                       "4. Other\n\n" .
                       "Reply with the number (1-4).";
                       
            case 'sme_salary_method':
                return "How do you receive your salary?\n\n" .
                       "1. Bank transfer\n" .
                       "2. Cash\n\n" .
                       "Reply with 1 or 2.";
                       
            case 'beneficiary_question':
                return "Are you a ZB Bank account holder?\n\n" .
                       "Reply with *YES* or *NO*.";
                       
            case 'monitoring_question':
                return "Would you like us to help you open a ZB Bank account?\n\n" .
                       "Reply with *YES* or *NO*.";
                       
            case 'training_question':
                return "Would you be interested in business training?\n\n" .
                       "Reply with *YES* or *NO*.";
                       
            case 'agent_offer_after_rejection':
                return "Thank you for your responses.\n\n" .
                       "Unfortunately, you don't qualify for credit at this time.\n\n" .
                       "However, you can still earn income by becoming an online agent!\n\n" .
                       "Would you like to apply to become an agent?\n\n" .
                       "Reply with *YES* or *NO*.";
                       
            case 'completed':
                return "Thank you for using Microbiz Zimbabwe! 🙏\n\n" .
                       "Have a great day! 👋\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            default:
                return null;
        }
    }
    
    /**
     * Send invalid input message based on current state
     */
    private function sendInvalidInputMessage(string $to, string $state): void
    {
        $message = match($state) {
            'microbiz_main_menu' => "Please select a number from 1-5.",
            'agent_age_check' => "Please reply with 1 or 2.",
            'agent_province' => "Please reply with a number from 1-10.",
            'agent_gender' => "Please reply with 1 or 2.",
            'agent_age_range' => "Please reply with a number from 1-6.",
            'employment_check', 'formal_employment_check', 'beneficiary_question',
            'monitoring_question', 'training_question', 'agent_offer_after_rejection' => 
                "Please reply with YES or NO.",
            'unemployment_category', 'employer_category' => "Please reply with a number from 1-4.",
            'sme_salary_method' => "Please reply with 1 or 2.",
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
     * Start a new conversation with main menu
     */
    public function startConversation(string $from): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
        $sessionId = 'whatsapp_' . $phoneNumber;
        
        Log::info("StateMachine: Starting new conversation", ['from' => $from, 'sessionId' => $sessionId]);
        
        // Initialize state
        $this->stateManager->saveState(
            $sessionId,
            'whatsapp',
            $phoneNumber,
            'microbiz_main_menu',
            [],
            ['phone_number' => $phoneNumber, 'started_at' => now(), 'flow_type' => 'microbiz']
        );
        
        // Send welcome menu
        $message = "Hello *there*, welcome to *Microbiz Zimbabwe*, the home of innovation and where entrepreneurs are born.\n\n";
        $message .= "How can I help you today?\n\n";
        $message .= "1. Apply to become an online agent and earn a passive income through referral commissions\n";
        $message .= "2. Purchase microbiz starter pack for cash\n";
        $message .= "3. Purchase microbiz starter pack for credit\n";
        $message .= "4. Purchase gadgets, furniture, solar systems, laptops, cellphones, kitchenware for cash\n";
        $message .= "5. Purchase gadgets, furniture, solar systems, laptops, cellphones, kitchenware for credit\n\n";
        $message .= "Reply with the number of your choice (1-5).";
        
        $result = $this->whatsAppService->sendMessage($from, $message);
        Log::info("StateMachine: Welcome message sent", ['success' => $result]);
    }
}
