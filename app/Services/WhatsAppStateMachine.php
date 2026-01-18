<?php

namespace App\Services;

use App\Enums\ConversationState;
use Illuminate\Support\Facades\Log;

use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\ProductSeries;
use App\Models\ProductPackageSize; // Added
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * WhatsApp Conversation State Machine
 * 
 * Manages state transitions and actions for WhatsApp conversations
 * Updated: Adala persona with dynamic product catalog browsing
 */
class WhatsAppStateMachine
{
    private WhatsAppCloudApiService $whatsAppService;
    private StateManager $stateManager;
    
    // Website base URL for redirects
    private string $websiteUrl = 'https://bancosystem.fly.dev';
    
    /**
     * State transition map: current_state => [input => next_state]
     * Updated: Language → Intent Selection (18 options)
     *   Options 1-13: Browse Categories → Products → Link
     *   Options 14-18: Direct handling
     */
    private array $transitions = [
        // Language selection (5 languages) → goes to intent_selection
        'language_selection' => [
            '1' => 'intent_selection',    // English
            '2' => 'intent_selection',    // ChiShona -> redirect to English
            '3' => 'intent_selection',    // Ndau -> redirect to English
            '4' => 'intent_selection',    // isiNdebele -> redirect to English
            '5' => 'intent_selection',    // Chichewa -> redirect to English
        ],
        
        // Intent selection - 10 options (Single Page)
        // Options 1-4 → browse catalog
        // Options 5-10 → direct handling
        'intent_selection' => [
            '1' => 'browse_categories',             // SME Starter Pack
            '2' => 'browse_categories',             // Personal & Homeware
            '3' => 'browse_categories',             // Personal Development
            '4' => 'browse_categories',             // House Construction
            '5' => 'redirect_zb_account',           // Apply for ZB Account
            '6' => 'agent_age_check',               // Become Agent
            '7' => 'redirect_application_status',   // Track Application
            '8' => 'redirect_delivery_tracking',    // Track Delivery
            '9' => 'show_faqs',                     // FAQs
            '10' => 'customer_service_wait',        // Customer Services
        ],
        
        // Cash or Credit selection -> currency
        'payment_method' => [
            '1' => 'currency_selection',      // Credit (Assuming credit goes straight to currency as before?)
            '2' => 'cash_payment_selection',  // Cash -> New Step
        ],
        
        // Cash Payment Selection -> currency
        'cash_payment_selection' => [
             '1' => 'currency_selection', // SmileCash
             '2' => 'currency_selection', // Ecocash
             '3' => 'currency_selection', // Zimswitch
             '4' => 'currency_selection', // Mastercard/Visa
             '5' => 'currency_selection', // Sahwira Money
        ],
        
        // Currency selection -> product redirect
        'currency_selection' => [
            '1' => 'product_link_sent',  // USD
            '2' => 'product_link_sent',  // ZiG
        ],
        
        // Dynamic Catalog
        'browse_categories' => [], 
        'browse_subcategories' => [], 
        'browse_series' => [], 
        'browse_products' => [],
        'browse_packages' => [],
        
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
        
        // FAQs
        'show_faqs' => [
            '1' => 'intent_selection', // Back to menu
        ],
        
        // Idle timeout flow
        'idle_continue' => [
            'yes' => 'intent_selection',
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
    
    /**
     * Intent mapping for URL generation (options 1-4)
     */
    private array $intentMap = [
        '1' => 'microBiz',           // SME Starter Pack
        '2' => 'personal',           // Personal & Homeware
        '3' => 'personalServices',   // Personal Development
        '4' => 'construction',       // House Construction
    ];
    
    /**
     * Product names for display (options 1-4)
     */
    private array $productNames = [
        '1' => 'Small to Medium Business Starter Pack',
        '2' => 'Personal and Homeware Products',
        '3' => 'Invest in Personal Development',
        '4' => 'House Construction and Improvements',
    ];
    
    /**
     * Hardcoded Categories by Intent (FROM SEEDERS - First 10 due to WhatsApp limit)
     */
    private array $hardcodedCategories = [
        // Option 1: Microbiz (from ProductCatalogSeeder - 22 total, showing first 10)
        'microBiz' => [
            ['id' => 'mb_agric', 'name' => 'Agricultural machinery', 'desc' => '🚜 Farming equipment'],
            ['id' => 'mb_inputs', 'name' => 'Agricultural Inputs', 'desc' => '🌾 Seeds & Fertilizer'],
            ['id' => 'mb_chicken', 'name' => 'Chicken Projects', 'desc' => '🐔 Broilers & Incubators'],
            ['id' => 'mb_cleaning', 'name' => 'Cleaning Services', 'desc' => '🧹 Laundry & Car wash'],
            ['id' => 'mb_beauty', 'name' => 'Beauty, Hair & Cosmetics', 'desc' => '💇 Salon & Hair Products'],
            ['id' => 'mb_food', 'name' => 'Food Production', 'desc' => '🍞 Baking & Catering'],
            ['id' => 'mb_butchery', 'name' => 'Butchery Equipment', 'desc' => '🥩 small scale'],
            ['id' => 'mb_events', 'name' => 'Events Management', 'desc' => '🎉 PA & Tents'],
            ['id' => 'mb_snack', 'name' => 'Snack Production', 'desc' => '🍿 Maputi & Popcorn'],
        ],
        // Option 2: Personal & Homeware (from HirePurchaseSeeder - 15 categories, showing first 9)
        'personal' => [
            ['id' => 'hp_cell', 'name' => 'Cellphones', 'desc' => '📱 Smartphones'],
            ['id' => 'hp_laptop', 'name' => 'Laptops & Printers', 'desc' => '💻 Computers'],
            ['id' => 'hp_ict', 'name' => 'ICT Accessories', 'desc' => '🖥️ Tech accessories'],
            ['id' => 'hp_kitchen', 'name' => 'Kitchen ware', 'desc' => '🍳 Appliances'],
            ['id' => 'hp_tv', 'name' => 'Television & Decoders', 'desc' => '📺 Entertainment'],
            ['id' => 'hp_lounge', 'name' => 'Lounge Furniture', 'desc' => '🛋️ Sofas'],
            ['id' => 'hp_bedroom', 'name' => 'Bedroom ware', 'desc' => '🛏️ Beds'],
            ['id' => 'hp_solar', 'name' => 'Solar systems', 'desc' => '☀️ Solar panels'],
            ['id' => 'hp_motor', 'name' => 'Motor Sundries', 'desc' => '🚗 Vehicle parts'],
        ],
        // Option 3: Personal Services (from PersonalProductsSeeder)
        'personalServices' => [
            ['id' => 'ps_school', 'name' => 'School Fees Support', 'desc' => '📚 Education financing'],
            ['id' => 'ps_license', 'name' => 'Drivers License', 'desc' => '🚗 Provisional & Full'],
            ['id' => 'ps_nurse', 'name' => 'Nurse Aid Course', 'desc' => '⚕️ Healthcare training'],
            ['id' => 'ps_zimparks', 'name' => 'Zimparks Holiday', 'desc' => '🏕️ Vacation packages'],
        ],
        // Option 4: Construction (from PersonalProductsSeeder + HirePurchaseSeeder)
        'construction' => [
            ['id' => 'cn_core', 'name' => 'Core House', 'desc' => '🏠 Full house build'],
            ['id' => 'cn_materials', 'name' => 'Building Materials', 'desc' => '🧱 Cement, Doors, etc.'],
        ],
    ];
    
    /**
     * Hardcoded Subcategories/Businesses by Category (FROM SEEDERS)
     */
    private array $hardcodedSubcategories = [
        // Microbiz Subcategories (from ProductCatalogSeeder)
        'mb_agric' => [
            ['id' => 'sub_maize', 'name' => 'Maize sheller'],
            ['id' => 'sub_water', 'name' => 'Water storage tanks'],
            ['id' => 'sub_tractors', 'name' => 'Mini Tractors'],
            ['id' => 'sub_irrigation', 'name' => 'Drip Irrigation kits'],            
        ],
        'mb_inputs' => [
            ['id' => 'sub_fertilizer', 'name' => 'Fertilizer'],
            ['id' => 'sub_seed', 'name' => 'Seed + Chemicals'],
            ['id' => 'sub_combo', 'name' => 'Fertilizer + Seed + Chemicals'],
        ],
        'mb_chicken' => [
            ['id' => 'sub_broiler', 'name' => 'Broiler Production'],
            ['id' => 'sub_hatchery', 'name' => 'Egg Hatchery'],
        ],
        'mb_cleaning' => [
            ['id' => 'sub_laundry', 'name' => 'Laundry'],
            ['id' => 'sub_carwash', 'name' => 'Car wash'],
            ['id' => 'sub_carpet', 'name' => 'Carpet and fabric'],
        ],
        'mb_beauty' => [
            ['id' => 'sub_barber', 'name' => 'Barber & Rasta'],
            ['id' => 'sub_braiding', 'name' => 'Braiding and weaving'],
            ['id' => 'sub_nails', 'name' => 'Nails and makeup'],
            ['id' => 'sub_saloon', 'name' => 'Saloon equipment'],
        ],
        'mb_food' => [
            ['id' => 'sub_baking', 'name' => 'Baking'],
            ['id' => 'sub_foodcart', 'name' => 'Mobile food cart'],
            ['id' => 'sub_takeaway', 'name' => 'Takeaway Canteen'],
            ['id' => 'sub_fryer', 'name' => 'Chip and burger fryer'],
        ],
        'mb_butchery' => [
            ['id' => 'sub_fridge', 'name' => 'Commercial Fridges'],
            ['id' => 'sub_bonecutter', 'name' => 'Bone cutter'],
            ['id' => 'sub_sausage', 'name' => 'Sausage maker'],
        ],
        'mb_events' => [
            ['id' => 'sub_pa', 'name' => 'PA system'],
            ['id' => 'sub_chairs', 'name' => 'Chairs, tables & décor'],
            ['id' => 'sub_tents', 'name' => 'Tents'],
        ],
        'mb_snack' => [
            ['id' => 'sub_freezit', 'name' => 'Freezit making'],
            ['id' => 'sub_maputi', 'name' => 'Maputi making'],
            ['id' => 'sub_popcorn', 'name' => 'Popcorn making'],
        ],
        // Personal & Homeware Brands (from HirePurchaseSeeder)
        'hp_cell' => [
            ['id' => 'br_samsung', 'name' => 'Samsung'],
            ['id' => 'br_apple', 'name' => 'Apple iPhone'],
            ['id' => 'br_zte', 'name' => 'ZTE'],
            ['id' => 'br_redmi', 'name' => 'Redmi'],
            ['id' => 'br_tecno', 'name' => 'Tecno'],
        ],
        'hp_laptop' => [
            ['id' => 'br_laptops', 'name' => 'Laptops'],
            ['id' => 'br_printers', 'name' => 'Printers'],
        ],
        'hp_ict' => [
            ['id' => 'br_accessories', 'name' => 'ICT Accessories'],
        ],
        'hp_kitchen' => [
            ['id' => 'br_stove', 'name' => 'Stoves'],
            ['id' => 'br_fridge', 'name' => 'Fridges'],
        ],
        'hp_tv' => [
            ['id' => 'br_tv', 'name' => 'Smart TVs'],
            ['id' => 'br_decoder', 'name' => 'Decoders'],
        ],
        'hp_lounge' => [
            ['id' => 'br_sofa', 'name' => 'Sofas'],
        ],
        'hp_bedroom' => [
            ['id' => 'br_beds', 'name' => 'Beds & Mattresses'],
        ],
        'hp_solar' => [
            ['id' => 'br_solar', 'name' => 'Solar Systems'],
        ],
        'hp_motor' => [
            ['id' => 'br_motor', 'name' => 'Motor Sundries'],
        ],
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
        
        Log::info("StateMachine: Transition Check", [
            'step' => $currentStep, 
            'input' => $input,
            'is_cat_start' => str_starts_with($input, 'cat_')
        ]);
        
        // Dynamic Catalog Flow (HARDCODED)
        // 1. Browse Categories -> Select Category (cat_ID) -> Browse Subcategories or Payment
        if ($currentStep === 'browse_categories') {
            if ($input === 'back') {
                $this->executeTransition($from, $state, $currentStep, 'intent_selection', $input);
                return;
            }
            
            // Accept any cat_ prefixed input (hardcoded IDs)
            if (str_starts_with($input, 'cat_')) {
                Log::info("StateMachine: Category selected", ['input' => $input]);
                
                // Get intent to determine next step
                $formData = $state->form_data ?? [];
                $intent = $formData['intent'] ?? 'personal';
                
                // Options 3 (personalServices) and 4 (construction) go directly to payment
                if (in_array($intent, ['personalServices', 'construction'])) {
                    $this->executeTransition($from, $state, $currentStep, 'payment_method', $input);
                    return;
                }
                
                // Options 1 (microBiz) and 2 (personal) browse subcategories
                $this->executeTransition($from, $state, $currentStep, 'browse_subcategories', $input);
                return;
            } else {
                 Log::warning("StateMachine: Invalid category input", ['input' => $input]);
                 $this->whatsAppService->sendMessage($from, "⚠️ Please select a category from the list.");
                 return;
            }
        }

        // 2. Browse Subcategories -> Select Subcategory (sub_ID) -> Payment
        if ($currentStep === 'browse_subcategories') {
             if ($input === 'back') {
                $this->executeTransition($from, $state, $currentStep, 'browse_categories', $input);
                return;
            }
            
            // Accept any sub_ prefixed input (hardcoded IDs)
            if (str_starts_with($input, 'sub_')) {
                Log::info("StateMachine: Subcategory selected", ['input' => $input]);
                
                // Both Microbiz and Personal go to payment after subcategory selection
                $this->executeTransition($from, $state, $currentStep, 'payment_method', $input);
                return;
            } else {
                 Log::warning("StateMachine: Invalid subcategory input", ['input' => $input]);
                 $this->whatsAppService->sendMessage($from, "⚠️ Please select an option from the list.");
                 return;
            }
        }

        // 3. Browse Series -> Select Series (ser_ID) -> Browse Products
        // 3. Browse Series -> Select Series (ser_ID or Name) -> Browse Products
        if ($currentStep === 'browse_series') {
            if ($input === 'back') {
                $this->executeTransition($from, $state, $currentStep, 'browse_subcategories', $input);
                return;
            }
            if (str_starts_with($input, 'ser_') || ProductSeries::where('name', 'like', $input)->exists()) {
                $this->executeTransition($from, $state, $currentStep, 'browse_products', $input);
                return;
            }
            $this->whatsAppService->sendMessage($from, "⚠️ Series/Brand not found. Please select from the list.");
            return;
        }

        // 4. Browse Products -> Select Product (prod_ID) -> Get Link

        // 3. Browse Products -> Select Product (prod_ID) -> Get Link
        // 4. Browse Products -> Select Product (prod_ID or Name) -> Get Link
        if ($currentStep === 'browse_products') {
            if ($input === 'back') {
                 // Determining back state is tricky (series vs subcategory). 
                 // We can check formData in state, or just default to one.
                 // For now, let's default to browse_subcategories as it's safer, 
                 // or ideally we should know where we came from.
                 // Let's go to browse_subcategories to be safe, users can navigate forward.
                 // OR check if we have series_id in the previous state's data? 
                 // $state->form_data is available.
                 $fd = $state->form_data ?? [];
                 $hasSeriesId = !empty($fd['selected_series_id']);
                 $backState = $hasSeriesId ? 'browse_series' : 'browse_subcategories';
                 
                 $this->executeTransition($from, $state, $currentStep, $backState, $input);
                 return;
            }
            
            if (str_starts_with($input, 'prod_') || ($product = Product::where('name', 'like', $input)->first())) {
                $prodId = str_starts_with($input, 'prod_') ? substr($input, 5) : $product->id;
                
                // Check for Packages
                $hasPackages = ProductPackageSize::where('product_id', $prodId)->exists();
                $nextState = $hasPackages ? 'browse_packages' : 'payment_method';
                
                $this->executeTransition($from, $state, $currentStep, $nextState, $input);
                return;
            }
            $this->whatsAppService->sendMessage($from, "⚠️ Product not found. Please select from the list.");
            return;
        }

        // 5. Browse Packages -> Select Package (pkg_ID or Name) -> Payment Method
        if ($currentStep === 'browse_packages') {
             if ($input === 'back') {
                $this->executeTransition($from, $state, $currentStep, 'browse_products', $input);
                return;
            }
            if (str_starts_with($input, 'pkg_') || ProductPackageSize::where('name', 'like', $input)->exists()) {
                $this->executeTransition($from, $state, $currentStep, 'payment_method', $input);
                return;
            }
            $this->whatsAppService->sendMessage($from, "⚠️ Package not found. Please select from the list.");
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
        Log::info("StateMachine: handleFreeTextInput called", [
            'from' => $from,
            'currentStep' => $currentStep,
            'input' => $input
        ]);
        
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
        
        Log::info("StateMachine: Free text config lookup", [
            'currentStep' => $currentStep,
            'config' => $config
        ]);
        
        if (!$config) {
            Log::error("StateMachine: No config for free text state", ['state' => $currentStep]);
            return;
        }
        
        // Save input to form data
        $formData[$config['field']] = $input;
        
        // If transitioning to agent_id_upload, save the agent application now
        // (all text data has been collected at this point)
        if ($config['next'] === 'agent_id_upload') {
            $this->saveAgentApplication($from, $state, $formData);
        }
        
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
                "🌐 This language is not available at the moment. Proceeding with English."
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
                $languages = ['English', 'ChiShona', 'Ndau', 'isiNdebele', 'Chichewa'];
                $formData['selected_language'] = $languages[(int)$input - 1] ?? 'English';
                $formData['language'] = 'en';
                break;
            case 'payment_method':
                // 1=Credit, 2=Cash
                $formData['payment_method'] = ($input === '1') ? 'credit' : 'cash';
                break;
                
            case 'cash_payment_selection':
                $cashOptions = [
                    '1' => 'SmileCash (ZB wallet)',
                    '2' => 'Ecocash',
                    '3' => 'Zimswitch',
                    '4' => 'Mastercard or Visa',
                    '5' => 'Sahwira Money (ZB diaspora)'
                ];
                $formData['payment_details'] = $cashOptions[$input] ?? 'Cash';
                break;
            case 'intent_selection':
                // No more next_page/prev_page check needed
                $formData['intent_choice'] = $input;
                $formData['intent'] = $this->intentMap[$input] ?? 'personal';
                $formData['product_name'] = $this->productNames[$input] ?? 'Selected Product';
                break;
            case 'currency_selection':
                $formData['currency'] = ($input === '1') ? 'USD' : 'ZiG';
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
            
            // Dynamic Catalog Data (HARDCODED)
            case 'browse_categories':
                Log::info("StateMachine: processing browse_categories input", ['input' => $input]);
                if (str_starts_with($input, 'cat_')) {
                    $catId = substr($input, 4);
                    Log::info("StateMachine: extracted category ID", ['catId' => $catId]);
                    $formData['selected_category_id'] = $catId;
                    
                    // Lookup name from hardcoded arrays
                    $catName = 'Category';
                    foreach ($this->hardcodedCategories as $intent => $cats) {
                        foreach ($cats as $cat) {
                            if ($cat['id'] === $catId) {
                                $catName = $cat['name'];
                                break 2;
                            }
                        }
                    }
                    $formData['selected_category_name'] = $catName;
                }
                break;

            case 'browse_subcategories':
                if (str_starts_with($input, 'sub_')) {
                    $subId = substr($input, 4);
                    $formData['selected_subcategory_id'] = $subId;
                    
                    // Lookup name from hardcoded arrays
                    $subName = 'Business';
                    foreach ($this->hardcodedSubcategories as $catId => $subs) {
                        foreach ($subs as $sub) {
                            if ($sub['id'] === $subId) {
                                $subName = $sub['name'];
                                break 2;
                            }
                        }
                    }
                    $formData['selected_subcategory_name'] = $subName;
                }
                break;
            
            case 'browse_series':
                if (str_starts_with($input, 'ser_')) {
                    $serId = substr($input, 4);
                    $formData['selected_series_id'] = $serId;
                    $series = ProductSeries::find($serId);
                    $formData['selected_series_name'] = $series ? $series->name : 'Series';
                } else {
                     // Fallback: Lookup by Name
                    $series = ProductSeries::where('name', 'like', $input)->first();
                    if ($series) {
                        $formData['selected_series_id'] = $series->id;
                        $formData['selected_series_name'] = $series->name;
                    }
                }
                break;
                
            case 'browse_packages':
                if (str_starts_with($input, 'pkg_')) {
                    $pkgId = substr($input, 4);
                    $formData['selected_package_id'] = $pkgId;
                    $package = ProductPackageSize::find($pkgId);
                    $formData['selected_package_name'] = $package ? $package->name : 'Package';
                    $formData['selected_package_price'] = $package ? $package->custom_price : null;
                } else {
                    // Fallback Name Lookup
                    $package = ProductPackageSize::where('name', 'like', $input)->first();
                    if ($package) {
                        $formData['selected_package_id'] = $package->id;
                        $formData['selected_package_name'] = $package->name;
                        $formData['selected_package_price'] = $package->custom_price;
                    }
                }
                break;
                
            case 'browse_products':
                if (str_starts_with($input, 'prod_')) {
                    $prodId = substr($input, 5);
                    $formData['selected_product_id'] = $prodId;
                    $product = Product::find($prodId);
                    $formData['selected_product_name'] = $product ? $product->name : 'Product';
                } else {
                    // Fallback: Lookup by Name
                    $product = Product::where('name', 'like', $input)->first();
                    if ($product) {
                        $formData['selected_product_id'] = $product->id;
                        $formData['selected_product_name'] = $product->name;
                    }
                }
                break;
        }
        
        return $formData;
    }
    
    /**
     * Send message for a given state
     * Uses interactive buttons/lists where appropriate
     */
    private function sendStateMessage(string $to, string $state, array $formData = []): void
    {
        Log::info("StateMachine: Sending message for state", ['state' => $state, 'to' => $to]);
        
        $result = false;
        
        // Use interactive messages for specific states
        switch ($state) {
            case 'intent_selection':
                $result = $this->sendIntentSelectionList($to);
                break;

            case 'browse_categories':
                $result = $this->sendCategoryList($to, $formData);
                break;

            case 'browse_subcategories':
                $result = $this->sendSubCategoryList($to, $formData);
                break;

            case 'browse_series':
                $result = $this->sendSeriesList($to, $formData);
                break;

            case 'browse_products':
                $result = $this->sendProductList($to, $formData);
                break;

            case 'browse_packages':
                $result = $this->sendPackageList($to, $formData);
                break;
                
            case 'product_link_sent':
                $result = $this->sendProductLink($to, $formData);
                break;
                
            case 'payment_method':
                $result = $this->whatsAppService->sendInteractiveButtons(
                    $to,
                    "💳 *Kindly select your payment option?*\n\n",
                    [
                        ['id' => '1', 'title' => '📋 Credit'],
                        ['id' => '2', 'title' => '💵 Cash'],
                    ],
                    "Payment Option"
                );
                break;
                
            case 'cash_payment_selection':
                 $result = $this->whatsAppService->sendInteractiveList(
                    $to,
                    "💵 *Select your Cash Payment Option*\n\nChoose a provider:",
                    "View Options",
                    [[
                        'title' => 'Cash Options', 
                        'rows' => [
                            ['id' => '1', 'title' => 'SmileCash', 'description' => 'ZB wallet'],
                            ['id' => '2', 'title' => 'Ecocash', 'description' => 'Mobile Money'],
                            ['id' => '3', 'title' => 'Zimswitch', 'description' => 'Bank Transfer'],
                            ['id' => '4', 'title' => 'Mastercard / Visa', 'description' => 'Card Payment'],
                            ['id' => '5', 'title' => 'Sahwira Money', 'description' => 'ZB diaspora'],
                        ]
                    ]]
                );
                break;
                
            case 'currency_selection':
                $productName = $formData['product_name'] ?? 'Your selected product';
                $result = $this->whatsAppService->sendInteractiveButtons(
                    $to,
                    "💱 *Select your preferred currency for:*\n\n📦 {$productName}",
                    [
                        ['id' => '1', 'title' => '🇺🇸 USD'],
                        ['id' => '2', 'title' => '🇿🇼 ZiG'],
                    ],
                    " "
                );
                break;
                
            case 'agent_gender':
                $result = $this->whatsAppService->sendInteractiveButtons(
                    $to,
                    "👤 *What is your gender?*",
                    [
                        ['id' => '1', 'title' => '👨 Male'],
                        ['id' => '2', 'title' => '👩 Female'],
                    ]
                );
                break;
                
            default:
                // Use regular text message for other states
                $message = $this->getStateMessage($state, $formData);
                if ($message) {
                    $result = $this->whatsAppService->sendMessage($to, $message);
                }
                break;
        }
        
        Log::info("StateMachine: Message send result", ['success' => $result]);
    }
    
    private function sendIntentSelectionList(string $to): bool
    {
        $bodyText = "🛒 *What would you like to do today?*\n\nTap the button below to see available options.";
        $buttonText = "View Options (1-10)";
        
        $sections = [];
        
        $sections[] = [
            'title' => '💰 PURCHASE ',
            'rows' => [
                ['id' => '1', 'title' => 'Small & Medium Business', 'description' => 'Starter pack'],
                ['id' => '2', 'title' => 'Homeware & Electronics', 'description' => 'Gadgets, Solar Systems & Furniture'],
                ['id' => '3', 'title' => 'Personal Development', 'description' => 'Life changing skills'],
                ['id' => '4', 'title' => 'Building materials', 'description' => 'Home improvements'],
            ],
        ];
        
        $sections[] = [
            'title' => '🤝 SERVICES ',
            'rows' => [
                ['id' => '5', 'title' => 'Apply for a ZB Bank Acc', 'description' => 'Individual Account'],
                ['id' => '6', 'title' => 'Apply to become an Agent', 'description' => 'Earn passive income online'],
                ['id' => '7', 'title' => 'Track Credit Application', 'description' => 'Check status'],
                ['id' => '8', 'title' => 'Track Product Delivery', 'description' => 'Order tracking'],
            ],
        ];

        
        $sections[] = [
            'title' => 'ℹ️ SUPPORT',
            'rows' => [
                ['id' => '9', 'title' => 'FAQs', 'description' => 'Get quick answers'],
                ['id' => '10', 'title' => 'Customer Service', 'description' => 'Talk to a representative'],
            ],
        ];
        
        return $this->whatsAppService->sendInteractiveList(
            $to,
            $bodyText,
            $buttonText,
            $sections,
            " "
        );
    }
    
    /**
     * Get message template for a state (text-only messages)
     */
    private function getStateMessage(string $state, array $formData = []): ?string
    {
        switch ($state) {
            // payment_method and currency_selection handled by sendStateMessage with buttons
                
            case 'redirect_to_product':
                return $this->getProductRedirectMessage($formData);
                       
            case 'redirect_delivery_tracking':
                return "📦 *Track Your Delivery*\n\n" .
                       "Please login to track your delivery:\n\n" .
                       "🔗 {$this->websiteUrl}/client/login\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_application_status':
                return "📋 *Track Your Application*\n\n" .
                       "Please login to check your application status:\n\n" .
                       "🔗 {$this->websiteUrl}/client/login\n\n" .
                       "Say 'hi' anytime to start a new conversation.";

            case 'redirect_zb_account':
                return "🏦 *Open a ZB Account*\n\n" .
                       "You can open a ZB account online instantly:\n\n" .
                       "🔗 https://zb.co.zw/banking/accounts\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'redirect_agent_login':
                return "👤 *Online Agent Login*\n\n" .
                       "Please login to your agent dashboard:\n\n" .
                       "🔗 {$this->websiteUrl}/agent/login\n\n" .
                       "Say 'hi' anytime to start a new conversation.";
                       
            case 'show_faqs':
                return $this->getFAQsMessage();
                       
            case 'customer_service_wait':
                return "👨‍💼 *Connecting to Customer Service*\n\n" .
                       "Please wait a few minutes while we connect you to a representative...\n\n" .
                       "🔄 A team member will be with you shortly.\n\n" .
                       "In the meantime, you can also reach us at:\n" .
                       "📧 support@bancozim.com\n" .
                       "📞 +263 (0242) 744 840";
                       
            // Agent application flow (option 9 - preserved)
            case 'agent_age_check':
                return "🌟 *Apply to Become Our Online Agent*\n\n" .
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
                       "Almost done! Please send a clear photo of the *front* of your ID card.\n\n" .
                       "After sending your ID, your application will be processed and you'll receive an SMS with confirmation and login details.\n\n" .
                       "🔗 Agent Login Portal:\n{$this->websiteUrl}/agent/login";
                       
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
     * Get product redirect message with personalized link
     */
    private function getProductRedirectMessage(array $formData): string
    {
        $currency = $formData['currency'] ?? 'USD';
        $intent = $formData['intent'] ?? 'personal';
        $language = $formData['language'] ?? 'en';
        
        // Build application URL with query params
        $applicationUrl = "{$this->websiteUrl}/application?currency={$currency}&intent={$intent}&language={$language}";
        
        return "Click to view product catalog:\n\n" .
               "🔗 {$applicationUrl}";
    }
    
    /**
     * Get FAQs message
     */
    private function getFAQsMessage(): string
    {
        return "❓ *Frequently Asked Questions*\n\n" .
               "*Q: What is Bancosystem?*\n" .
               "A: Bancosystem is a digital platform that helps you access products and services on cash or credit.\n\n" .
               "*Q: How do I apply for credit?*\n" .
               "A: Select Credit when asked about payment method, then choose your product.\n\n" .
               "*Q: What are the requirements?*\n" .
               "A: Requirements vary by product. Generally, you need a valid ID and proof of income for credit.\n\n" .
               "*Q: How long does approval take?*\n" .
               "A: Most applications are processed within 24-48 hours.\n\n" .
               "*Q: How do I become an agent?*\n" .
               "A: Say 'hi' and select 'Become Agent' from the menu.\n\n" .
               "Reply *1* to go back to the main menu.";
    }
    
    /**
     * Send invalid input message based on current state
     */
    private function sendInvalidInputMessage(string $to, string $state): void
    {
        $message = match($state) {
            'language_selection' => "Please select a number from 1-5.",
            'intent_selection' => "Please select an option from the menu.",
            'intent_selection_page2' => "Please select an option from the menu.",
            'payment_method' => "Please select Cash or Credit.",
            'currency_selection' => "Please select USD or ZiG.",
            'agent_age_check' => "Please reply with 1 or 2.",
            'agent_province' => "Please reply with a number from 1-10.",
            'agent_gender' => "Please select Male or Female.",
            'agent_age_range' => "Please reply with a number from 1-6.",
            'show_faqs' => "Please reply with 1 to go back to the menu.",
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
        $welcomeText = "Hello *{$displayName}*! 👋\n\n";
        $welcomeText .= "Welcome to *Microbiz Zimbabwe* powered by Qupa (a division of *ZB Bank*).\n\n";
        $welcomeText .= "I am *Adala*, consider me your smart uncle and digital assistant. My mission is to ensure you get the best user experience for your intended acquisition, because we are family.\n\n";
        $welcomeText .= "🌐 Please select your preferred language:";
        
        // Send language selection as interactive list
        $sections = [
            [
                'title' => '🌐 Languages',
                'rows' => [
                    ['id' => '1', 'title' => '🇬🇧 English', 'description' => 'Continue in English'],
                    ['id' => '2', 'title' => '🇿🇼 ChiShona', 'description' => 'Edzai muChiShona'],
                    ['id' => '3', 'title' => '🇿🇼 Ndau', 'description' => 'Edzai muNdau'],
                    ['id' => '4', 'title' => '🇿🇼 isiNdebele', 'description' => 'Qhubekela ngesiNdebele'],
                    ['id' => '5', 'title' => '🇲🇼 Chichewa', 'description' => 'Pitilizani mu Chichewa'],
                ],
            ],
        ];
        
        $result = $this->whatsAppService->sendInteractiveList(
            $from,
            $welcomeText,
            "🌐 Select Language",
            $sections
        );
        
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
    /**
     * Send list of categories based on selected intent (HARDCODED)
     */
    private function sendCategoryList(string $to, array $formData): bool
    {
        $intent = $formData['intent'] ?? 'personal';
        
        // Use hardcoded categories
        $categories = $this->hardcodedCategories[$intent] ?? $this->hardcodedCategories['personal'];
        
        if (empty($categories)) {
            return $this->whatsAppService->sendMessage($to, "⚠️ No categories found for this selection. Please try another option.");
        }

        $sections = [['title' => '📂 Categories', 'rows' => []]];
        
        foreach ($categories as $category) {
            $sections[0]['rows'][] = [
                'id' => 'cat_' . $category['id'],
                'title' => substr($category['name'], 0, 24),
                'description' => substr($category['desc'] ?? 'Browse options', 0, 72)
            ];
        }
        
        // Add back button
        $sections[0]['rows'][] = ['id' => 'back', 'title' => '🔙 Back'];
        
        return $this->whatsAppService->sendInteractiveList(
            $to, 
            "📦 *Product Categories*\n\nPlease select a category:", 
            "View Categories", 
            $sections
        );
    }
    
    /**
     * Send list of subcategories for a category (HARDCODED)
     */
    private function sendSubCategoryList(string $to, array $formData): bool
    {
        $catId = $formData['selected_category_id'] ?? null;
        $catName = $formData['selected_category_name'] ?? 'Category';
        
        if (!$catId) {
            Log::warning("sendSubCategoryList: No category ID provided");
            return $this->whatsAppService->sendMessage($to, "⚠️ Please select a category first.");
        }
        
        // Use hardcoded subcategories
        $subcategories = $this->hardcodedSubcategories[$catId] ?? [];
        
        if (empty($subcategories)) {
            $this->whatsAppService->sendMessage($to, "⚠️ No options found for {$catName}. Proceeding to payment...");
            return true;
        }
        
        $rows = [];
        foreach ($subcategories as $sub) {
            $rows[] = [
                'id' => 'sub_' . $sub['id'],
                'title' => Str::limit($sub['name'], 24)
            ];
        }
        
        $rows[] = ['id' => 'back', 'title' => '🔙 Back'];

        return $this->whatsAppService->sendInteractiveList(
            $to,
            "📦 *{$catName}*\n\nSelect an option:",
            "View Options",
            [['title' => 'Options', 'rows' => $rows]]
        );
    }

    private function sendSeriesList(string $to, array $formData): bool
    {
        $subId = $formData['selected_subcategory_id'] ?? null;
        $subName = $formData['selected_subcategory_name'] ?? 'Subcategory';
        
        if (!$subId) return false;
        
        $series = ProductSeries::where('product_sub_category_id', $subId)->limit(9)->get();
        
        if ($series->isEmpty()) {
            // Should have been skipped by logic in process(), but just in case
            return $this->sendProductList($to, $formData);
        }
        
        $rows = [];
        foreach ($series as $ser) {
            $rows[] = [
                'id' => 'ser_' . $ser->id,
                'title' => Str::limit($ser->name, 24)
            ];
        }
        
        $rows[] = ['id' => 'back', 'title' => '🔙 Back'];

        return $this->whatsAppService->sendInteractiveList(
            $to,
            "✨ *Select Series/Brand*",
            "Option: {$subName}\nSelect a series:",
            "View Series",
            [['title' => 'Series', 'rows' => $rows]]
        );
    }
    
    /**
     * Send list of products for a subcategory
     */
    private function sendProductList(string $to, array $formData): bool
    {
        $subId = $formData['selected_subcategory_id'] ?? null;
        $serId = $formData['selected_series_id'] ?? null;
        $subName = $formData['selected_subcategory_name'] ?? 'Subcategory';
        $serName = $formData['selected_series_name'] ?? 'Series';
        
        if (!$subId && !$serId) return false;
        
        $query = Product::query();
        $displayTitle = "Option: {$subName}";
        
        if ($serId) {
            $query->where('product_series_id', $serId);
            $displayTitle = "Series: {$serName}";
        } elseif ($subId) {
            $query->where('product_sub_category_id', $subId);
        }
        
        $products = $query->limit(9)->get();
        
        if ($products->isEmpty()) {
            $this->whatsAppService->sendMessage($to, "⚠️ No products avaliable in this selection yet.");
            return true;
        }
        
        $rows = [];
        foreach ($products as $product) {
            $rows[] = [
                'id' => 'prod_' . $product->id,
                'title' => Str::limit($product->name, 24),
                'description' => '$' . number_format($product->base_price, 2)
            ];
        }
        
        $rows[] = ['id' => 'back', 'title' => '🔙 Back'];

        return $this->whatsAppService->sendInteractiveList(
            $to,
            "📦 *Select a Product*",
            "{$displayTitle}\nChoose a product:",
            "View Products",
            [['title' => 'Products', 'rows' => $rows]]
        );
    }
    
    private function sendPackageList(string $to, array $formData): bool
    {
        $prodId = $formData['selected_product_id'] ?? null;
        $prodName = $formData['selected_product_name'] ?? 'Product';
        
        if (!$prodId) return false;
        
        $packages = ProductPackageSize::where('product_id', $prodId)->limit(9)->get();
        
        if ($packages->isEmpty()) {
             // Should not be here if no packages, but proceed to payment if so.
             // This method is called by sendStateMessage, so we can't easily valid transition here.
             // But process() checked ->exists().
             // Just in case:
             return $this->whatsAppService->sendMessage($to, "⚠️ No packages found. Type 'back' to go back.");
        }
        
        $rows = [];
        foreach ($packages as $pkg) {
            $price = $pkg->custom_price;
            $rows[] = [
                'id' => 'pkg_' . $pkg->id,
                'title' => Str::limit($pkg->name, 24),
                'description' => '$' . number_format($price, 2)
            ];
        }
        
        $rows[] = ['id' => 'back', 'title' => '🔙 Back'];

        return $this->whatsAppService->sendInteractiveList(
            $to,
            "📦 *Select Package/Size*",
            "Product: {$prodName}\nChoose a package:",
            "View Packages",
            [['title' => 'Packages', 'rows' => $rows]]
        );
    }
    /**
     * Send the final product link (HARDCODED FLOW)
     * Cash goes to /cash-purchase, Credit goes to /application
     */
    private function sendProductLink(string $to, array $formData): bool
    {
        $intent = $formData['intent'] ?? 'personal';
        $categoryId = $formData['selected_category_id'] ?? '';
        $categoryName = $formData['selected_category_name'] ?? 'Selected Product';
        $subcategoryId = $formData['selected_subcategory_id'] ?? '';
        $subcategoryName = $formData['selected_subcategory_name'] ?? '';
        $payment = $formData['payment_method'] ?? 'credit';
        $currency = $formData['currency'] ?? 'USD';
        $paymentDetails = $formData['payment_details'] ?? '';
        $language = $formData['language'] ?? 'en';
        
        // Display name: use subcategory if set, else category
        $displayName = $subcategoryName ?: $categoryName;
        
        // Map intent to type parameter for cash purchase
        $typeMap = [
            'microBiz' => 'microBiz',
            'personal' => 'personalGadgets',
            'personalServices' => 'personalServices',
            'construction' => 'construction',
        ];
        $type = $typeMap[$intent] ?? 'personalGadgets';
        
        // Build link based on payment method
        if ($payment === 'cash') {
            // Cash purchase URL
            $link = "{$this->websiteUrl}/cash-purchase?currency={$currency}&language={$language}&type={$type}";
        } else {
            // Credit/Application URL
            $link = "{$this->websiteUrl}/application?intent={$intent}&currency={$currency}&payment_method={$payment}";
            
            if ($categoryId) {
                $link .= "&category=" . urlencode($categoryId);
            }
            if ($subcategoryId) {
                $link .= "&subcategory=" . urlencode($subcategoryId);
            }
        }
        
        if ($paymentDetails) {
            $link .= "&payment_details=" . urlencode($paymentDetails);
        }
        
        Log::info("StateMachine: Sending product link", ['to' => $to, 'payment' => $payment, 'link' => $link]);
        
        $paymentLabel = ($payment === 'cash') ? '💵 Cash Purchase' : '📋 Credit Application';
        
        return $this->whatsAppService->sendMessage(
            $to,
            "✅ *Ready to proceed with: {$displayName}*\n\n" .
            "💰 Currency: {$currency}\n" .
            "{$paymentLabel}\n\n" .
            "🔗 Click here to continue:\n{$link}\n\n" .
            "Type 'hi' to start a new conversation."
        );
    }
    
    /**
     * Save agent application to database
     * Called when agent flow reaches agent_id_upload step (after all text data collected)
     */
    private function saveAgentApplication(string $from, $state, array $formData): void
    {
        $phoneNumber = $this->whatsAppService->extractPhoneNumber($from);
        
        try {
            $application = \App\Models\AgentApplication::create([
                'whatsapp_number' => $phoneNumber,
                'session_id' => $state->session_id ?? uniqid('agent_'),
                'province' => $formData['province'] ?? '',
                'first_name' => $formData['first_name'] ?? '',
                'surname' => $formData['surname'] ?? '',
                'gender' => $formData['gender'] ?? '',
                'age_range' => $formData['age_range'] ?? '',
                'voice_number' => $formData['voice_number'] ?? '',
                'whatsapp_contact' => $formData['whatsapp_contact'] ?? $phoneNumber,
                'ecocash_number' => $formData['ecocash_number'] ?? '',
                'status' => 'pending',
            ]);
            
            Log::info('AgentApplication saved from StateMachine', [
                'application_id' => $application->id,
                'application_number' => $application->application_number,
                'phone' => $phoneNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save AgentApplication from StateMachine', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
        }
    }
}
