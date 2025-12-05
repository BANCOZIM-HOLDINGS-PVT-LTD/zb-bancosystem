<?php

namespace App\Services;

use App\Services\StateManager;
use App\Services\RapiWhaService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsAppConversationService
{
    private $rapiWhaService;
    private $stateManager;
    private $syncService;

    public function __construct(
        RapiWhaService $rapiWhaService, 
        StateManager $stateManager,
        CrossPlatformSyncService $syncService
    ) {
        $this->rapiWhaService = $rapiWhaService;
        $this->stateManager = $stateManager;
        $this->syncService = $syncService;
    }

    /**
     * Process incoming WhatsApp message
     */
    public function processIncomingMessage(string $from, string $message): void
    {
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        $message = trim(strtolower($message));

        Log::info("WhatsApp message received from {$phoneNumber}: {$message}");

        try {
            // Try to get existing conversation state
            $state = $this->stateManager->retrieveState('whatsapp_' . $phoneNumber, 'whatsapp');
            
            if (!$state) {
                // New conversation
                $this->handleNewConversation($from, $message);
            } else {
                // Continue existing conversation
                $this->handleExistingConversation($from, $message, $state);
            }
        } catch (\Exception $e) {
            Log::error("Error processing WhatsApp message: " . $e->getMessage());
            $this->rapiWhaService->sendMessage($from, "Sorry, something went wrong. Please try again or type 'start' to begin.");
        }
    }

    /**
     * Handle new conversation
     */
    private function handleNewConversation(string $from, string $message): void
    {
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);

        // New Microbiz greetings
        $microbizGreetings = ['hi', 'hie', 'hallo', 'hello', 'hey', 'hesi'];
        
        if (in_array($message, $microbizGreetings)) {
            $this->showMicrobizMainMenu($from);
        }
        // Legacy loan application flow
        elseif (in_array($message, ['start', 'begin'])) {
            $this->startApplication($from);
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
        $currentStep = $state->current_step ?? 'language';
        $formData = $state->form_data ?? [];

        switch ($currentStep) {
            // === Legacy Loan Application Flow ===
            case 'language':
                $this->handleLanguageSelection($from, $message, $state);
                break;
            case 'intent':
                $this->handleIntentSelection($from, $message, $state);
                break;
            case 'employer':
                $this->handleEmployerSelection($from, $message, $state);
                break;
            case 'product':
                $this->handleProductCategorySelection($from, $message, $state);
                break;
            case 'subcategory':
                $this->handleSubcategorySelection($from, $message, $state);
                break;
            case 'business':
                $this->handleBusinessSelection($from, $message, $state);
                break;
            case 'scale':
                $this->handleScaleSelection($from, $message, $state);
                break;
            case 'account':
                $this->handleAccountVerification($from, $message, $state);
                break;
            case 'form':
                $this->handleFormFilling($from, $message, $state);
                break;
                
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
     * Start new application
     */
    private function startApplication(string $from): void
    {
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        $sessionId = 'whatsapp_' . $phoneNumber;

        // Initialize application state
        $this->stateManager->saveState(
            $sessionId,
            'whatsapp',
            $phoneNumber,
            'language',
            [],
            ['phone_number' => $phoneNumber, 'started_at' => now()]
        );

        $message = "🏦 *Welcome to ZB Bank Application*\n\n";
        $message .= "Let's start your loan application. Please select your preferred language:\n\n";
        $message .= "1. English\n";
        $message .= "2. Shona\n";
        $message .= "3. Ndebele\n\n";
        $message .= "Reply with the number of your choice.";

        $this->rapiWhaService->sendMessage($from, $message);
    }

    /**
     * Resume application from web
     */
    public function resumeApplication(string $from, string $resumeCode): void
    {
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        
        // Find session by resume code
        $linkedState = $this->stateManager->getStateByResumeCode($resumeCode);
        
        if (!$linkedState) {
            $this->rapiWhaService->sendMessage($from, "❌ Invalid resume code. Please check and try again or type 'start' to begin a new application.");
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

            $this->rapiWhaService->sendMessage($from, $message);
            
            Log::info('Application resumed via WhatsApp', [
                'phone_number' => $phoneNumber,
                'resume_code' => $resumeCode,
                'web_session' => $linkedState->session_id,
                'current_step' => $syncResult['current_step']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to resume application via WhatsApp: ' . $e->getMessage());
            $this->rapiWhaService->sendMessage($from, "❌ Sorry, there was an issue resuming your application. Please try again or contact support.");
        }
    }

    /**
     * Handle language selection
     */
    private function handleLanguageSelection(string $from, string $message, $state): void
    {
        $languages = [
            '1' => ['code' => 'en', 'name' => 'English'],
            '2' => ['code' => 'sn', 'name' => 'Shona'],
            '3' => ['code' => 'nd', 'name' => 'Ndebele']
        ];

        if (!isset($languages[$message])) {
            $this->sendInvalidInput($from, "Please select 1, 2, or 3 for your language preference.");
            return;
        }

        $selectedLang = $languages[$message];
        $formData = array_merge($state->form_data ?? [], ['language' => $selectedLang['code']]);

        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'intent',
            $formData,
            $state->metadata ?? []
        );

        $msg = "✅ Language selected: {$selectedLang['name']}\n\n";
        $msg .= "What type of application would you like to make?\n\n";
        $msg .= "1. 💳 Hire Purchase Credit\n";
        $msg .= "2. 💼 Micro Biz Loan\n\n";
        $msg .= "Reply with 1 or 2.";

        $this->rapiWhaService->sendMessage($from, $msg);
    }

    /**
     * Handle intent selection
     */
    private function handleIntentSelection(string $from, string $message, $state): void
    {
        $intents = [
            '1' => 'hirePurchase',
            '2' => 'microBiz'
        ];

        if (!isset($intents[$message])) {
            $this->sendInvalidInput($from, "Please select 1 for Hire Purchase Credit or 2 for Micro Biz Loan.");
            return;
        }

        $formData = array_merge($state->form_data ?? [], ['intent' => $intents[$message]]);

        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'employer',
            $formData,
            $state->metadata ?? []
        );

        $msg = "✅ Application type selected\n\n";
        $msg .= "Who is your employer?\n\n";
        $msg .= "1. 🏛️ GOZ (Government) - SSB\n";
        $msg .= "2. 🏛️ GOZ - ZAPPA\n";
        $msg .= "3. 🏛️ GOZ - Pension\n";
        $msg .= "4. 🏢 Town Council\n";
        $msg .= "5. 🏢 Parastatal\n";
        $msg .= "6. 🏫 Mission and Private Schools\n";
        $msg .= "7. 💼 I am an Entrepreneur\n";
        $msg .= "8. 🏢 Large Corporate\n";
        $msg .= "9. 📝 Other\n\n";
        $msg .= "Reply with the number (1-9).";

        $this->rapiWhaService->sendMessage($from, $msg);
    }

    /**
     * Handle employer selection
     */
    private function handleEmployerSelection(string $from, string $message, $state): void
    {
        $employers = [
            '1' => 'goz-ssb',
            '2' => 'goz-zappa',
            '3' => 'goz-pension',
            '4' => 'town-council',
            '5' => 'parastatal',
            '6' => 'mission-private-schools',
            '7' => 'entrepreneur',
            '8' => 'large-corporate',
            '9' => 'other'
        ];

        if (!isset($employers[$message])) {
            $this->sendInvalidInput($from, "Please select a number from 1-9 for your employer type.");
            return;
        }

        $formData = array_merge($state->form_data ?? [], ['employer' => $employers[$message]]);

        // Check if SSB (skip account verification)
        if ($employers[$message] === 'goz-ssb') {
            $formData['hasAccount'] = true;
            $formData['accountType'] = 'SSB';
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'product',
                $formData,
                $state->metadata ?? []
            );

            $this->sendProductCategorySelection($from);
        } else {
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'account',
                $formData,
                $state->metadata ?? []
            );

            $this->sendAccountVerification($from);
        }
    }

    /**
     * Handle product category selection
     */
    private function handleProductCategorySelection(string $from, string $message, $state): void
    {
        $categories = $this->getProductCategories();
        $currentPage = $state->form_data['categoryPage'] ?? 1;
        
        if ($message === 'back' && isset($state->form_data['previousStep'])) {
            $this->goBackToPreviousStep($from, $state);
            return;
        }
        
        if ($message === 'next') {
            $totalPages = ceil(count($categories) / 10);
            if ($currentPage < $totalPages) {
                $newPage = $currentPage + 1;
                $formData = array_merge($state->form_data ?? [], ['categoryPage' => $newPage]);
                $this->stateManager->saveState(
                    $state->session_id,
                    'whatsapp',
                    $state->user_identifier,
                    'product',
                    $formData,
                    $state->metadata ?? []
                );
                $this->sendProductCategorySelection($from, $newPage);
            }
            return;
        }
        
        if ($message === 'prev') {
            if ($currentPage > 1) {
                $newPage = $currentPage - 1;
                $formData = array_merge($state->form_data ?? [], ['categoryPage' => $newPage]);
                $this->stateManager->saveState(
                    $state->session_id,
                    'whatsapp',
                    $state->user_identifier,
                    'product',
                    $formData,
                    $state->metadata ?? []
                );
                $this->sendProductCategorySelection($from, $newPage);
            }
            return;
        }
        
        if (!ctype_digit($message) || !isset($categories[(int)$message - 1])) {
            $this->sendInvalidInput($from, "Please select a number from 1-" . count($categories) . ", type 'next'/'prev' for pagination, or 'back'.");
            return;
        }
        
        $selectedCategory = $categories[(int)$message - 1];
        
        // Get businesses for this category directly (since each category has only one subcategory)
        $businesses = $selectedCategory['subcategories'][0]['businesses'];
        
        if (empty($businesses)) {
            $this->sendInvalidInput($from, "This category is not yet available. Please choose another category.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], [
            'selectedCategory' => $selectedCategory,
            'selectedBusinesses' => $businesses,
            'previousStep' => 'product'
        ]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'business',
            $formData,
            $state->metadata ?? []
        );
        
        $this->sendBusinessSelection($from, $selectedCategory['name'], $businesses);
    }
    
    /**
     * Handle subcategory selection
     */
    private function handleSubcategorySelection(string $from, string $message, $state): void
    {
        $category = $state->form_data['selectedCategory'] ?? null;
        
        if (!$category) {
            $this->sendProductCategorySelection($from);
            return;
        }
        
        if ($message === 'back') {
            $this->sendProductCategorySelection($from);
            return;
        }
        
        if (!ctype_digit($message) || !isset($category['subcategories'][(int)$message - 1])) {
            $this->sendInvalidInput($from, "Please select a number from 1-" . count($category['subcategories']) . " or type 'back'.");
            return;
        }
        
        $selectedSubcategory = $category['subcategories'][(int)$message - 1];
        $formData = array_merge($state->form_data ?? [], [
            'selectedSubcategory' => $selectedSubcategory,
            'previousStep' => 'subcategory'
        ]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'business',
            $formData,
            $state->metadata ?? []
        );
        
        $this->sendBusinessSelection($from, $selectedSubcategory);
    }
    
    /**
     * Handle business selection
     */
    private function handleBusinessSelection(string $from, string $message, $state): void
    {
        $businesses = $state->form_data['selectedBusinesses'] ?? null;
        
        if (!$businesses) {
            $this->sendProductCategorySelection($from);
            return;
        }
        
        if ($message === 'back') {
            $this->sendProductCategorySelection($from);
            return;
        }
        
        if (!ctype_digit($message) || !isset($businesses[(int)$message - 1])) {
            $this->sendInvalidInput($from, "Please select a number from 1-" . count($businesses) . " or type 'back'.");
            return;
        }
        
        $selectedBusiness = $businesses[(int)$message - 1];
        $formData = array_merge($state->form_data ?? [], [
            'selectedBusiness' => $selectedBusiness,
            'previousStep' => 'business'
        ]);
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'scale',
            $formData,
            $state->metadata ?? []
        );
        
        $this->sendScaleSelection($from, $selectedBusiness);
    }
    
    /**
     * Handle scale selection
     */
    private function handleScaleSelection(string $from, string $message, $state): void
    {
        $business = $state->form_data['selectedBusiness'] ?? null;
        
        if (!$business) {
            $this->sendProductCategorySelection($from);
            return;
        }
        
        if ($message === 'back') {
            $category = $state->form_data['selectedCategory'];
            $businesses = $state->form_data['selectedBusinesses'];
            $this->sendBusinessSelection($from, $category['name'], $businesses);
            return;
        }
        
        if (!ctype_digit($message) || !isset($business['scales'][(int)$message - 1])) {
            $this->sendInvalidInput($from, "Please select a number from 1-" . count($business['scales']) . " or type 'back'.");
            return;
        }
        
        $selectedScale = $business['scales'][(int)$message - 1];
        $finalPrice = $selectedScale['custom_price'] ?? $business['basePrice'] * $selectedScale['multiplier'];
        
        $formData = array_merge($state->form_data ?? [], [
            'selectedScale' => $selectedScale,
            'finalPrice' => $finalPrice,
            'productSelectionComplete' => true
        ]);
        
        // Move to form filling or completion
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'form',
            $formData,
            $state->metadata ?? []
        );
        
        $this->sendProductSelectionSummary($from, $formData);
    }
    
    /**
     * Send product category selection with pagination
     */
    private function sendProductCategorySelection(string $from, int $page = 1): void
    {
        $categories = $this->getProductCategories();
        $perPage = 10;
        $totalPages = ceil(count($categories) / $perPage);
        $offset = ($page - 1) * $perPage;
        $pageCategories = array_slice($categories, $offset, $perPage);
        
        $msg = "🛍️ *Product Categories (Page {$page}/{$totalPages})*\n\n";
        $msg .= "Choose a category:\n\n";
        
        foreach ($pageCategories as $index => $category) {
            $globalIndex = $offset + $index + 1;
            $msg .= "{$globalIndex}. " . $category['emoji'] . " " . $category['name'] . "\n";
        }
        
        $msg .= "\n";
        
        if ($page > 1) {
            $msg .= "Type 'prev' for previous page\n";
        }
        if ($page < $totalPages) {
            $msg .= "Type 'next' for next page\n";
        }
        
        $msg .= "Type the number of your choice (1-" . count($categories) . ").";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Send subcategory selection
     */
    private function sendSubcategorySelection(string $from, array $category): void
    {
        $msg = "📋 *" . $category['name'] . " Subcategories*\n\n";
        
        foreach ($category['subcategories'] as $index => $subcategory) {
            $msg .= ($index + 1) . ". " . $subcategory['name'] . "\n";
        }
        
        $msg .= "\nType the number of your choice or 'back' to go back.";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Send business selection
     */
    private function sendBusinessSelection(string $from, string $categoryName, array $businesses): void
    {
        $msg = "🏢 *" . $categoryName . " Options*\n\n";
        
        foreach ($businesses as $index => $business) {
            $msg .= ($index + 1) . ". " . $business['name'] . " - $" . number_format($business['basePrice']) . "\n";
        }
        
        $msg .= "\nType the number of your choice or 'back' to go back.";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Send scale selection
     */
    private function sendScaleSelection(string $from, array $business): void
    {
        $msg = "📏 *" . $business['name'] . " Scale Options*\n\n";
        
        foreach ($business['scales'] as $index => $scale) {
            $finalPrice = $business['basePrice'] * $scale['multiplier'];
            $msg .= ($index + 1) . ". " . $scale['name'] . " - $" . number_format($finalPrice) . "\n";
        }
        
        $msg .= "\nType the number of your choice or 'back' to go back.";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Send product selection summary
     */
    private function sendProductSelectionSummary(string $from, array $formData): void
    {
        $business = $formData['selectedBusiness'];
        $scale = $formData['selectedScale'];
        $price = $formData['finalPrice'];
        
        // Calculate credit facility details
        $prePopulated = $this->getPrePopulatedValues($formData);
        
        $msg = "✅ *Product Selection Complete*\n\n";
        $msg .= "🏢 Business: " . $business['name'] . "\n";
        $msg .= "📏 Scale: " . $scale['name'] . "\n";
        $msg .= "💰 Loan Amount: $" . number_format($price) . "\n";
        $msg .= "📅 Tenure: " . $prePopulated['loanTenure'] . " months\n";
        $msg .= "💳 Monthly Payment: $" . $prePopulated['monthlyPayment'] . "\n";
        $msg .= "📊 Interest Rate: " . $prePopulated['interestRate'] . "%\n\n";
        $msg .= "Now let's complete your application form.\n\n";
        
        // Determine form type based on employer and account status
        $employer = $formData['employer'] ?? '';
        $hasAccount = $formData['hasAccount'] ?? false;
        
        if ($employer === 'goz-ssb') {
            $msg .= "📋 We'll collect your SSB loan application details.\n";
            $msg .= "The credit facility details above will be pre-filled.\n\n";
            $msg .= "Type 'continue' to start the form.";
        } elseif ($employer === 'entrepreneur') {
            $msg .= "📋 We'll collect your SME business application details.\n";
            $msg .= "The credit facility details above will be pre-filled.\n\n";
            $msg .= "Type 'continue' to start the form.";
        } elseif ($hasAccount) {
            $msg .= "📋 We'll collect your account holder loan application details.\n";
            $msg .= "The credit facility details above will be pre-filled.\n\n";
            $msg .= "Type 'continue' to start the form.";
        } else {
            $msg .= "📋 We'll collect your account opening details.\n";
            $msg .= "The credit facility details above will be pre-filled.\n\n";
            $msg .= "Type 'continue' to start the form.";
        }
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }

    /**
     * Handle account verification
     */
    private function handleAccountVerification(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2'])) {
            $this->sendInvalidInput($from, "Please select 1 if you have an account or 2 if you don't.");
            return;
        }

        $hasAccount = $message === '1';
        $formData = array_merge($state->form_data ?? [], ['hasAccount' => $hasAccount]);

        if ($hasAccount) {
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'product',
                $formData,
                $state->metadata ?? []
            );

            $this->sendProductCategorySelection($from);
        } else {
            // Need to open account first
            $msg = "🏦 *Account Required*\n\n";
            $msg .= "An account is required to proceed with the loan application.\n\n";
            $msg .= "📱 Continue on web to open an account: " . config('app.url') . "\n\n";
            $msg .= "Or type 'account' to get account opening information.";

            $this->rapiWhaService->sendMessage($from, $msg);
        }
    }

    /**
     * Send account verification
     */
    private function sendAccountVerification(string $from): void
    {
        $msg = "✅ Employer selected\n\n";
        $msg .= "🏦 *Account Verification*\n\n";
        $msg .= "Do you have a ZB Bank account?\n\n";
        $msg .= "1. ✅ Yes, I have an account\n";
        $msg .= "2. ❌ No, I don't have an account\n\n";
        $msg .= "Reply with 1 or 2.";

        $this->rapiWhaService->sendMessage($from, $msg);
    }

    /**
     * Generate web resume link
     */
    public function generateWebResumeLink(string $sessionId): string
    {
        $resumeCode = $this->stateManager->generateResumeCode($sessionId);
        return config('app.url') . "/application/resume/{$resumeCode}";
    }
    
    /**
     * Get current step message for user
     */
    private function getCurrentStepMessage(string $currentStep, array $formData): string
    {
        switch ($currentStep) {
            case 'language':
                return "Please select your preferred language:\n1. English\n2. Shona\n3. Ndebele";
            case 'intent':
                return "What type of application would you like to make?\n1. 💳 Hire Purchase Credit\n2. 💼 Micro Biz Loan";
            case 'employer':
                return "Please select your employer type from the options provided.";
            case 'account':
                return "Do you have a ZB Bank account?\n1. ✅ Yes\n2. ❌ No";
            case 'product':
                return "Please select a product category from the available options.";
            case 'business':
                return "Please select a business option from the available choices.";
            case 'scale':
                return "Please select the scale/size for your selected business.";
            case 'form':
                $completedFields = count($formData['formResponses'] ?? []);
                $totalFields = count($formData['formFields'] ?? []);
                return "Continue filling your application form.\nProgress: {$completedFields}/{$totalFields} fields completed.\nType 'continue' to proceed.";
            case 'completed':
                return "🎉 Your application has been completed! You can check its status using your reference code.";
            default:
                return "Continue with your application. Type 'help' if you need assistance.";
        }
    }

    /**
     * Send welcome message
     */
    private function sendWelcomeMessage(string $from): void
    {
        $message = "👋 *Welcome to ZB Bank*\n\n";
        $message .= "I can help you apply for loans and open accounts.\n\n";
        $message .= "*Commands:*\n";
        $message .= "• Type *'start'* to begin a new application\n";
        $message .= "• Type *'resume XXXXXX'* to continue from web\n\n";
        $message .= "How can I help you today?";

        $this->rapiWhaService->sendMessage($from, $message);
    }

    /**
     * Send invalid input message
     */
    private function sendInvalidInput(string $from, string $customMessage = null): void
    {
        $message = $customMessage ?? "❌ Invalid input. Please try again.";
        $this->rapiWhaService->sendMessage($from, $message);
    }

    /**
     * Handle form filling
     */
    private function handleFormFilling(string $from, string $message, $state): void
    {
        $formData = $state->form_data ?? [];
        
        if ($message === 'continue' && !isset($formData['formFields'])) {
            $this->startFormFilling($from, $state);
            return;
        }
        
        if ($message === 'back') {
            $this->sendProductSelectionSummary($from, $formData);
            return;
        }
        
        if ($message === 'skip' && isset($formData['currentField']['optional'])) {
            $this->processFormField($from, '', $state, true);
            return;
        }
        
        if ($message === 'save') {
            $this->saveAndResume($from, $state);
            return;
        }
        
        $this->processFormField($from, $message, $state);
    }
    
    /**
     * Start form filling process
     */
    private function startFormFilling(string $from, $state): void
    {
        $formData = $state->form_data ?? [];
        $employer = $formData['employer'] ?? '';
        $hasAccount = $formData['hasAccount'] ?? false;
        
        // Get appropriate form fields based on employer and account status
        $formFields = $this->getFormFields($employer, $hasAccount);
        
        // Pre-populate Credit Facility Application Details from product selections
        $prePopulatedResponses = $this->getPrePopulatedValues($formData);
        
        $formData['formFields'] = $formFields;
        $formData['currentFieldIndex'] = 0;
        $formData['formResponses'] = $prePopulatedResponses;
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'form',
            $formData,
            $state->metadata ?? []
        );
        
        $this->askNextFormField($from, $formData);
    }
    
    /**
     * Get pre-populated values from product selections
     */
    private function getPrePopulatedValues(array $formData): array
    {
        $business = $formData['selectedBusiness'] ?? null;
        $scale = $formData['selectedScale'] ?? null;
        $category = $formData['selectedCategory'] ?? null;
        $intent = $formData['intent'] ?? '';
        $finalPrice = $formData['finalPrice'] ?? 0;
        
        // Determine credit facility type based on intent and business
        $facilityType = '';
        if ($intent === 'hirePurchase') {
            $facilityType = 'Hire Purchase Credit - ' . ($business['name'] ?? 'Unknown');
        } elseif ($intent === 'microBiz') {
            $facilityType = 'Micro Biz Loan - ' . ($business['name'] ?? 'Unknown');
        }
        
        // Calculate tenure based on loan amount (simple logic)
        $tenure = $this->calculateTenure($finalPrice);
        
        // Calculate monthly payment (10% interest rate)
        $interestRate = 10.0;
        $monthlyPayment = $this->calculateMonthlyPayment($finalPrice, $tenure, $interestRate);
        
        return [
            'creditFacilityType' => $facilityType,
            'loanAmount' => number_format($finalPrice, 2),
            'loanTenure' => $tenure,
            'monthlyPayment' => number_format($monthlyPayment, 2),
            'interestRate' => number_format($interestRate, 1),
        ];
    }
    
    /**
     * Calculate tenure based on loan amount
     */
    private function calculateTenure(float $amount): int
    {
        if ($amount <= 1000) {
            return 6;  // 6 months for smaller loans
        } elseif ($amount <= 5000) {
            return 12; // 12 months for medium loans
        } elseif ($amount <= 15000) {
            return 18; // 18 months for larger loans
        } else {
            return 24; // 24 months for very large loans
        }
    }
    
    /**
     * Calculate monthly payment with interest
     */
    private function calculateMonthlyPayment(float $principal, int $months, float $annualRate): float
    {
        if ($principal <= 0 || $months <= 0) {
            return 0;
        }
        
        // Convert annual rate to monthly rate
        $monthlyRate = ($annualRate / 100) / 12;
        
        // Calculate monthly payment using loan formula
        if ($monthlyRate > 0) {
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
        } else {
            // No interest case
            $monthlyPayment = $principal / $months;
        }
        
        return $monthlyPayment;
    }
    
    /**
     * Process form field response
     */
    private function processFormField(string $from, string $response, $state, bool $skipped = false): void
    {
        $formData = $state->form_data ?? [];
        $currentIndex = $formData['currentFieldIndex'] ?? 0;
        $formFields = $formData['formFields'] ?? [];
        $responses = $formData['formResponses'] ?? [];
        
        if ($currentIndex >= count($formFields)) {
            $this->completeFormFilling($from, $state);
            return;
        }
        
        $currentField = $formFields[$currentIndex];
        
        // Handle readonly fields (just advance to next field)
        if (isset($currentField['readonly']) && $currentField['readonly']) {
            // Readonly field, just move to next field (value already set in pre-population)
            $formData['currentFieldIndex'] = $currentIndex + 1;
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'form',
                $formData,
                $state->metadata ?? []
            );
            
            $this->askNextFormField($from, $formData);
            return;
        }
        
        if (!$skipped && !$this->validateFormField($currentField, $response)) {
            $this->sendInvalidInput($from, "Please provide a valid " . strtolower($currentField['label']) . ".");
            return;
        }
        
        // Store response
        $responses[$currentField['name']] = $skipped ? null : $response;
        
        // Move to next field
        $formData['currentFieldIndex'] = $currentIndex + 1;
        $formData['formResponses'] = $responses;
        
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'form',
            $formData,
            $state->metadata ?? []
        );
        
        $this->askNextFormField($from, $formData);
    }
    
    /**
     * Ask next form field
     */
    private function askNextFormField(string $from, array $formData): void
    {
        $currentIndex = $formData['currentFieldIndex'] ?? 0;
        $formFields = $formData['formFields'] ?? [];
        $responses = $formData['formResponses'] ?? [];
        $totalFields = count($formFields);
        
        if ($currentIndex >= $totalFields) {
            $this->completeFormFilling($from, $formData);
            return;
        }
        
        $field = $formFields[$currentIndex];
        $progress = $currentIndex + 1;
        
        // Check if field is readonly (pre-populated)
        if (isset($field['readonly']) && $field['readonly']) {
            $preFilledValue = $responses[$field['name']] ?? 'N/A';
            
            $msg = "📋 *Field {$progress} of {$totalFields}* (Pre-filled)\n\n";
            $msg .= "🔸 " . $field['label'] . ": *{$preFilledValue}*\n\n";
            $msg .= "✅ This field is pre-filled from your product selection.\n";
            $msg .= "Press any key to continue...";
            
            $this->rapiWhaService->sendMessage($from, $msg);
            return;
        }
        
        $msg = "📋 *Question {$progress} of {$totalFields}*\n\n";
        $msg .= "🔸 " . $field['label'] . "\n";
        
        if (isset($field['description'])) {
            $msg .= "ℹ️ " . $field['description'] . "\n";
        }
        
        if (isset($field['options'])) {
            $msg .= "\nOptions:\n";
            foreach ($field['options'] as $index => $option) {
                $msg .= ($index + 1) . ". " . $option . "\n";
            }
        }
        
        $msg .= "\n";
        
        if (isset($field['optional']) && $field['optional']) {
            $msg .= "Type your answer or 'skip' to skip this field.\n";
        } else {
            $msg .= "Type your answer:\n";
        }
        
        $msg .= "\n💾 Type 'save' to save progress and get resume code.";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Complete form filling
     */
    private function completeFormFilling(string $from, $state): void
    {
        $formData = $state->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];
        
        // Mark as complete
        $formData['applicationComplete'] = true;
        $formData['completedAt'] = now()->toISOString();
        
        // Save the completed state
        $completedState = $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'completed',
            $formData,
            $state->metadata ?? []
        );
        
        // PDF will be generated on demand when downloaded from admin panel
        
        $business = $formData['selectedBusiness']['name'] ?? 'N/A';
        $amount = $formData['finalPrice'] ?? 0;
        $applicationNumber = 'ZB' . date('Y') . str_pad($completedState->id, 6, '0', STR_PAD_LEFT);
        
        $msg = "🎉 *Application Complete!*\n\n";
        $msg .= "✅ Your loan application has been submitted successfully.\n\n";
        $msg .= "📋 *Application Summary:*\n";
        $msg .= "• Application #: " . $applicationNumber . "\n";
        $msg .= "• Business: " . $business . "\n";
        $msg .= "• Amount: $" . number_format($amount) . "\n";
        $msg .= "• Submitted: " . now()->format('Y-m-d H:i') . "\n\n";
        $msg .= "📄 Download your application:\n";
        $msg .= config('app.url') . "/application/download/" . $state->session_id . "\n\n";
        $msg .= "📱 Check application status at:\n";
        $msg .= config('app.url') . "/application/status/" . $applicationNumber . "\n\n";
        $msg .= "Thank you for choosing ZB Bank! 🏦";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Save and resume functionality
     */
    private function saveAndResume(string $from, $state): void
    {
        $resumeCode = $this->stateManager->generateResumeCode($state->session_id);
        
        // Get expiration time for user-friendly display
        $expirationText = $this->getExpirationText($state);
        
        $msg = "💾 *Progress Saved*\n\n";
        $msg .= "Your application has been saved. You can:\n\n";
        $msg .= "1. 📱 Continue on web: " . config('app.url') . "/application/resume/{$resumeCode}\n\n";
        $msg .= "2. 💬 Continue here by typing 'continue'\n\n";
        $msg .= "⏰ Resume code expires in {$expirationText}.\n";
        $msg .= "🔐 Resume code: *{$resumeCode}*";
        
        $this->rapiWhaService->sendMessage($from, $msg);
    }
    
    /**
     * Get user-friendly expiration text
     */
    private function getExpirationText($state): string
    {
        $currentStep = $state->current_step;
        $formData = $state->form_data ?? [];
        
        switch ($currentStep) {
            case 'form':
                $completedFields = count($formData['formResponses'] ?? []);
                if ($completedFields > 0) {
                    return "2 hours";
                } else {
                    return "4 hours";
                }
                
            case 'product':
            case 'business':
            case 'scale':
                return "1 hour";
                
            case 'completed':
                return "15 minutes";
                
            default:
                return "30 minutes";
        }
    }
    
    /**
     * Get form fields based on employer and account status
     */
    private function getFormFields(string $employer, bool $hasAccount): array
    {
        $commonFields = [
            // Credit Facility Application Details (Pre-populated from selections)
            ['name' => 'creditFacilityType', 'label' => 'Credit Facility Type', 'type' => 'text', 'required' => true, 'readonly' => true],
            ['name' => 'loanAmount', 'label' => 'Loan Amount (USD)', 'type' => 'text', 'required' => true, 'readonly' => true],
            ['name' => 'loanTenure', 'label' => 'Loan Tenure (Months)', 'type' => 'text', 'required' => true, 'readonly' => true],
            ['name' => 'monthlyPayment', 'label' => 'Monthly Payment (USD)', 'type' => 'text', 'required' => true, 'readonly' => true],
            ['name' => 'interestRate', 'label' => 'Interest Rate (%)', 'type' => 'text', 'required' => true, 'readonly' => true],
            
            // Personal Details
            ['name' => 'firstName', 'label' => 'First Name', 'type' => 'text', 'required' => true],
            ['name' => 'lastName', 'label' => 'Last Name', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'Phone Number', 'type' => 'tel', 'required' => true],
            ['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'required' => true],
            ['name' => 'address', 'label' => 'Physical Address', 'type' => 'text', 'required' => true],
        ];
        
        if ($employer === 'goz-ssb') {
            return array_merge($commonFields, [
                ['name' => 'forceNumber', 'label' => 'Force Number', 'type' => 'text', 'required' => true],
                ['name' => 'rank', 'label' => 'Rank', 'type' => 'text', 'required' => true],
                ['name' => 'station', 'label' => 'Station', 'type' => 'text', 'required' => true],
            ]);
        }
        
        if ($employer === 'entrepreneur') {
            return array_merge($commonFields, [
                ['name' => 'businessName', 'label' => 'Business Name', 'type' => 'text', 'required' => true],
                ['name' => 'businessType', 'label' => 'Type of Business', 'type' => 'text', 'required' => true],
                ['name' => 'yearsInBusiness', 'label' => 'Years in Business', 'type' => 'number', 'required' => true],
                ['name' => 'monthlyIncome', 'label' => 'Monthly Income', 'type' => 'number', 'required' => true],
            ]);
        }
        
        if (!$hasAccount) {
            return array_merge($commonFields, [
                ['name' => 'idNumber', 'label' => 'ID Number', 'type' => 'text', 'required' => true],
                ['name' => 'dateOfBirth', 'label' => 'Date of Birth (YYYY-MM-DD)', 'type' => 'date', 'required' => true],
                ['name' => 'monthlyIncome', 'label' => 'Monthly Income', 'type' => 'number', 'required' => true],
                ['name' => 'employer', 'label' => 'Employer Name', 'type' => 'text', 'required' => true],
            ]);
        }
        
        return array_merge($commonFields, [
            ['name' => 'accountNumber', 'label' => 'Account Number', 'type' => 'text', 'required' => true],
            ['name' => 'monthlyIncome', 'label' => 'Monthly Income', 'type' => 'number', 'required' => true],
        ]);
    }
    
    /**
     * Validate form field
     */
    private function validateFormField(array $field, string $value): bool
    {
        if (empty($value) && isset($field['required']) && $field['required']) {
            return false;
        }
        
        if (empty($value)) {
            return true; // Optional field
        }
        
        switch ($field['type']) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'number':
                return is_numeric($value) && $value > 0;
            case 'tel':
                return preg_match('/^[+]?[0-9\s\-()]{7,}$/', $value);
            case 'date':
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
            default:
                return strlen($value) >= 2;
        }
    }
    
    /**
     * Get complete product categories - all 20 categories
     */
    private function getProductCategories(): array
    {
        $response = Http::get(config('app.url') . '/api/products');
        return $response->json();
    }
    
    // =====================================================
    // MICROBIZ ZIMBABWE CONVERSATION FLOW
    // =====================================================
    
    /**
     * Show Microbiz main menu with 5 options
     */
    private function showMicrobizMainMenu(string $from): void
    {
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        $sessionId = 'whatsapp_' . $phoneNumber;
        
        // Initialize Microbiz state
        $this->stateManager->saveState(
            $sessionId,
            'whatsapp',
            $phoneNumber,
            'microbiz_main_menu',
            [],
            ['phone_number' => $phoneNumber, 'started_at' => now(), 'flow_type' => 'microbiz']
        );
        
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
        
        $this->rapiWhaService->sendMessage($from, $message);
    }
    
    /**
     * Handle Microbiz main menu selection
     */
    private function handleMicrobizMainMenu(string $from, string $message, $state): void
    {
        if (!in_array($message, ['1', '2', '3', '4', '5'])) {
            $this->sendInvalidInput($from, "Please select a number from 1-5.");
            return;
        }
        
        $formData = array_merge($state->form_data ?? [], ['main_menu_choice' => $message]);
        
        switch ($message) {
            case '1':
                // Agent application flow
                $this->startAgentApplication($from, $state);
                break;
                
            case '2':
            case '4':
                // Cash purchase - redirect to website
                $this->redirectToCashPurchase($from, $state, $message);
                break;
                
            case '3':
            case '5':
                // Credit purchase - start eligibility check
                $this->startCreditEligibilityCheck($from, $state, $message);
                break;
        }
    }
    
    /**
     * Redirect to website for cash purchases
     */
    private function redirectToCashPurchase(string $from, $state, string $choice): void
    {
        $productType = ($choice == '2') ? 'Starter Pack' : 'Gadgets/Furniture';
        
        $message = "✅ Thank you for choosing to purchase *{$productType}* for cash!\n\n";
        $message .= "Please proceed to our website to complete your purchase:\n\n";
        $message .= "🌐 *" . config('app.url', 'https://bancosystem.fly.dev') . "*\n\n";
        $message .= "Or tap the link below to get started! 👇";
        
        $this->rapiWhaService->sendMessage($from, $message);
        
        // End session
        $this->stateManager->saveState(
            $state->session_id,
            'whatsapp',
            $state->user_identifier,
            'completed',
            array_merge($state->form_data ?? [], ['outcome' => 'redirected_to_website']),
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
        
        $this->rapiWhaService->sendMessage($from, $message);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
            
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
            
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
            
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
            
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
            $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
        
        $this->rapiWhaService->sendMessage($from, $msg);
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
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
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        
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
            
            $this->rapiWhaService->sendMessage($from, $msg);
            
            Log::info('Agent application submitted', [
                'application_id' => $application->id,
                'whatsapp_number' => $phoneNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save agent application: ' . $e->getMessage());
            
            $msg = "❌ Sorry, there was an error saving your application. Please try again later or contact support.";
            $this->rapiWhaService->sendMessage($from, $msg);
        }
    }
    

}