<?php

namespace App\Services;

use App\Services\StateManager;
use App\Services\TwilioWhatsAppService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsAppConversationService
{
    private $twilioService;
    private $stateManager;
    private $syncService;

    public function __construct(
        TwilioWhatsAppService $twilioService, 
        StateManager $stateManager,
        CrossPlatformSyncService $syncService
    ) {
        $this->twilioService = $twilioService;
        $this->stateManager = $stateManager;
        $this->syncService = $syncService;
    }

    /**
     * Process incoming WhatsApp message
     */
    public function processIncomingMessage(string $from, string $message): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
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
            $this->twilioService->sendMessage($from, "Sorry, something went wrong. Please try again or type 'start' to begin.");
        }
    }

    /**
     * Handle new conversation
     */
    private function handleNewConversation(string $from, string $message): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);

        // Check for specific commands
        if (in_array($message, ['start', 'begin', 'hello', 'hi'])) {
            $this->startApplication($from);
        } elseif (preg_match('/^resume\s+([a-z0-9]{6})$/i', $message, $matches)) {
            $this->resumeApplication($from, $matches[1]);
        } else {
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
            default:
                $this->sendInvalidInput($from);
        }
    }

    /**
     * Start new application
     */
    private function startApplication(string $from): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
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

        $this->twilioService->sendMessage($from, $message);
    }

    /**
     * Resume application from web
     */
    public function resumeApplication(string $from, string $resumeCode): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
        
        // Find session by resume code
        $linkedState = $this->stateManager->getStateByResumeCode($resumeCode);
        
        if (!$linkedState) {
            $this->twilioService->sendMessage($from, "❌ Invalid resume code. Please check and try again or type 'start' to begin a new application.");
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

            $this->twilioService->sendMessage($from, $message);
            
            Log::info('Application resumed via WhatsApp', [
                'phone_number' => $phoneNumber,
                'resume_code' => $resumeCode,
                'web_session' => $linkedState->session_id,
                'current_step' => $syncResult['current_step']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to resume application via WhatsApp: ' . $e->getMessage());
            $this->twilioService->sendMessage($from, "❌ Sorry, there was an issue resuming your application. Please try again or contact support.");
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

        $this->twilioService->sendMessage($from, $msg);
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

        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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

            $this->twilioService->sendMessage($from, $msg);
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

        $this->twilioService->sendMessage($from, $msg);
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

        $this->twilioService->sendMessage($from, $message);
    }

    /**
     * Send invalid input message
     */
    private function sendInvalidInput(string $from, string $customMessage = null): void
    {
        $message = $customMessage ?? "❌ Invalid input. Please try again.";
        $this->twilioService->sendMessage($from, $message);
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
            
            $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
        
        $this->twilioService->sendMessage($from, $msg);
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
    
    
    

}