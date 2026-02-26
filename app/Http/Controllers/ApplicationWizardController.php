<?php

namespace App\Http\Controllers;

use App\Services\ReferenceCodeService;
use App\Services\StateManager;
use App\Services\CrossPlatformSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Support\Str;
use App\Models\AccountOpening;
use App\Models\ApplicationState;
use App\Models\QupaReferralLink;

class ApplicationWizardController extends Controller
{
    private StateManager $stateManager;
    private ReferenceCodeService $referenceCodeService;
    private CrossPlatformSyncService $syncService;
    
    public function __construct(
        StateManager $stateManager, 
        ReferenceCodeService $referenceCodeService,
        CrossPlatformSyncService $syncService
    ) {
        $this->stateManager = $stateManager;
        $this->referenceCodeService = $referenceCodeService;
        $this->syncService = $syncService;
    }

    /**
     * Convert an account opening record to a new loan application
     */
    public function convertAccountToApplication(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $referenceCode = $request->reference_code;
        
        // Find the account opening record
        $accountOpening = AccountOpening::where('reference_code', $referenceCode)
            ->orWhere('user_identifier', $referenceCode)
            ->first();

        if (!$accountOpening) {
            return response()->json([
                'success' => false,
                'message' => 'Account record not found',
            ], 404);
        }

        // Create new session ID
        $newSessionId = (string) Str::uuid();

        // Prepare form data from account opening record
        $formData = $accountOpening->form_data;
        $formResponses = $formData['formResponses'] ?? [];

        // Add additional info for initialized loan application
        $newFormData = [
            'hasAccount' => true,
            'accountDetails' => [
                 'accountNumber' => $accountOpening->zb_account_number,
                 'verified' => true,
                 'accountHolderName' => ($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? ''),
            ],
            'formResponses' => [
                'firstName' => $formResponses['firstName'] ?? '',
                'lastName' => $formResponses['lastName'] ?? '',
                'nationalIdNumber' => $formResponses['nationalIdNumber'] ?? '',
                'phoneNumber' => $formResponses['phoneNumber'] ?? '',
                'email' => $formResponses['email'] ?? '',
                'dateOfBirth' => $formResponses['dateOfBirth'] ?? '',
                'gender' => $formResponses['gender'] ?? '',
                'title' => $formResponses['title'] ?? '',
                'address' => $formResponses['address'] ?? '',
                'city' => $formResponses['city'] ?? '',
                'accountNumber' => $accountOpening->zb_account_number,
                'existingAccountHolder' => true,
            ],
            'productName' => ($accountOpening->selected_product ?? [])['product_name'] ?? null,
            'productCode' => ($accountOpening->selected_product ?? [])['product_code'] ?? null,
            'category' => ($accountOpening->selected_product ?? [])['category'] ?? null,
        ];

        // Soft delete any existing application states with this reference code
        // so we can reuse the reference code for the new session
        ApplicationState::where('reference_code', $accountOpening->reference_code)->delete();

        // Create new ApplicationState
        $applicationState = ApplicationState::create([
            'session_id' => $newSessionId,
            'current_step' => 'product', // Start at product selection to confirm or change
            'form_data' => $newFormData,
            'metadata' => [
                'converted_from_account_opening_id' => $accountOpening->id,
                'account_opened_at' => $accountOpening->approved_at,
                'loan_eligible' => $accountOpening->loan_eligible,
                'status' => 'pending',
                'channel' => 'web',
            ],
            'expires_at' => now()->addDays(30),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
            'channel' => 'web',
            // We set reference_code to null initially, let generateReferenceCode handle it
            'reference_code' => null, 
        ]);

        // Assign the reference code to the new session
        // This will handle force deleting the soft-deleted old state
        try {
            $this->referenceCodeService->generateReferenceCode($newSessionId, $accountOpening->reference_code);
        } catch (\Exception $e) {
            \Log::error('Failed to assign reference code during conversion: ' . $e->getMessage());
            // Continue anyway, it shouldn't block the user entirely, though it might be confusing
        }

        return response()->json([
            'success' => true,
            'message' => 'Loan application started',
            'redirect_url' => route('application.resume', $newSessionId),
            'session_id' => $newSessionId,
        ]);
    }
    
    /**
     * Show the application wizard
     */
    public function show(Request $request): Response
    {
        $intent = $request->query('intent');
        $language = $request->query('language', 'en');
        $currency = $request->query('currency', 'USD');
        $sessionId = $request->query('session_id');
        $referralCode = $request->query('ref');

        $initialData = [];
        $initialStep = 'product'; // Start at product step
        $agentId = null;

        // Handle referral code if provided (from welcome page)
        if ($referralCode) {
            $referralLink = \App\Models\AgentReferralLink::where('code', $referralCode)
                ->usable()
                ->with('agent')
                ->first();

            if ($referralLink) {
                // Set agent ID for tracking
                $agentId = $referralLink->agent_id;
                $initialData['agentId'] = $agentId;
                $initialData['referralCode'] = $referralCode;
                $initialData['agentName'] = $referralLink->agent->full_name;

                \Log::info('Agent referral applied to wizard', [
                    'referral_code' => $referralCode,
                    'agent_id' => $agentId,
                    'intent' => $intent,
                ]);
            }
        }

        // If language is provided, add it to initial data
        if ($language) {
            $initialData['language'] = $language;
        }
        
        // If currency is provided, add it to initial data
        if ($currency) {
            $initialData['currency'] = $currency;
        }

        // If intent is provided, add it to initial data (always the case now)
        if ($intent) {
            $initialData['intent'] = $intent;
        }

        return Inertia::render('ApplicationWizard', [
            'initialStep' => $initialStep,
            'initialData' => $initialData,
            'sessionId' => $sessionId,
            'agentId' => $agentId,
            'referralCode' => $referralCode,
        ]);
    }

    /**
     * Show the application wizard with agent/qupa referral handling
     * Now redirects to welcome page with referral code in session
     */
    public function showWithReferral(Request $request): \Illuminate\Http\RedirectResponse
    {
        $referralCode = $request->query('ref');
        $qupaRefCode = $request->query('qref');

        // Handle agent referral
        if ($referralCode) {
            $referralLink = \App\Models\AgentReferralLink::where('code', $referralCode)
                ->usable()
                ->with('agent')
                ->first();

            if ($referralLink) {
                $referralLink->recordClick();

                session([
                    'referral_code' => $referralCode,
                    'agent_id' => $referralLink->agent_id,
                    'agent_name' => $referralLink->agent->full_name,
                    'campaign_name' => $referralLink->campaign_name,
                ]);

                \Log::info('Agent referral accessed', [
                    'referral_code' => $referralCode,
                    'agent_id' => $referralLink->agent_id,
                    'agent_name' => $referralLink->agent->full_name,
                    'campaign' => $referralLink->campaign_name,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } else {
                \Log::warning('Invalid referral code accessed', [
                    'referral_code' => $referralCode,
                    'ip' => $request->ip(),
                ]);
            }
        }

        // Handle Qupa Admin referral
        if ($qupaRefCode) {
            $qupaLink = QupaReferralLink::where('code', $qupaRefCode)
                ->active()
                ->with('user')
                ->first();

            if ($qupaLink) {
                $qupaLink->recordClick();

                session([
                    'qupa_referral_code' => $qupaRefCode,
                    'qupa_admin_id' => $qupaLink->user_id,
                    'qupa_admin_name' => $qupaLink->user->name,
                    'qupa_admin_branch_id' => $qupaLink->user->branch_id,
                ]);

                \Log::info('Qupa Admin referral accessed', [
                    'qupa_referral_code' => $qupaRefCode,
                    'qupa_admin_id' => $qupaLink->user_id,
                    'qupa_admin_name' => $qupaLink->user->name,
                    'branch_id' => $qupaLink->user->branch_id,
                    'ip' => $request->ip(),
                ]);
            } else {
                \Log::warning('Invalid Qupa referral code accessed', [
                    'qupa_referral_code' => $qupaRefCode,
                    'ip' => $request->ip(),
                ]);
            }
        }

        return redirect()->route('home');
    }
    
    /**
     * Resume an existing application
     */
    public function resume(Request $request, string $identifier): Response|\Illuminate\Http\RedirectResponse
    {
        $state = null;
        $referenceCode = null;
        
        // Check if it's a reference code (6 characters) or session ID
        if (strlen($identifier) === 6 && ctype_alnum($identifier)) {
            // It's a reference code
            $referenceCode = strtoupper($identifier);
            $state = $this->referenceCodeService->getStateByReferenceCode($referenceCode);
        } else {
            // It's a session ID
            $state = \App\Models\ApplicationState::where('session_id', $identifier)
                ->where('expires_at', '>', now())
                ->first();
                
            // If we found a state by session ID, check if it has a reference code
            if ($state && $state->reference_code) {
                $referenceCode = $state->reference_code;
            }
        }
        
        if (!$state) {
            return redirect()->route('home')->with('error', 'Application session not found or expired');
        }
        
        // If the reference code is about to expire, extend it
        if ($referenceCode && $state->reference_code_expires_at) {
            $expiresAt = is_string($state->reference_code_expires_at) 
                ? \Carbon\Carbon::parse($state->reference_code_expires_at)
                : $state->reference_code_expires_at;
                
            if ($expiresAt->diffInDays(now()) < 5) {
                $this->referenceCodeService->extendReferenceCode($referenceCode);
            }
        }
        
        // Check if there's a WhatsApp session to sync with
        $syncStatus = null;
        if ($state->channel === 'web') {
            $phoneNumber = data_get($state->metadata, 'phone_number');
            if ($phoneNumber) {
                $whatsappSessionId = 'whatsapp_' . $phoneNumber;
                try {
                    $syncStatus = $this->syncService->getSyncStatus($state->session_id, $whatsappSessionId);
                } catch (\Exception $e) {
                    Log::warning('Could not get sync status: ' . $e->getMessage());
                }
            }
        }
        
        return Inertia::render('ApplicationWizard', [
            'initialStep' => $state->current_step,
            'initialData' => $this->syncService->normalizeDataForPlatform($state->form_data ?? [], 'web'),
            'sessionId' => $state->session_id,
            'referenceCode' => $referenceCode,
            'syncStatus' => $syncStatus,
            'platformSwitchAvailable' => !empty(data_get($state->metadata, 'phone_number')),
        ]);
    }
    
    /**
     * Show application status page
     */
    public function status(): Response
    {
        return Inertia::render('ApplicationStatus');
    }

    /**
     * Show application success/thank you page
     */
    public function success(Request $request): Response
    {
        $referenceCode = $request->query('ref', 'N/A');
        $phoneNumber = null;

        // Try to get phone number from the application state
        if ($referenceCode !== 'N/A') {
            try {
                // Look up application state by reference code (National ID)
                $state = \App\Models\ApplicationState::where('reference_code', $referenceCode)
                    ->orWhere(function($query) use ($referenceCode) {
                        $query->whereJsonContains('form_data->nationalId', $referenceCode)
                              ->orWhereJsonContains('form_data->contact->phone', $referenceCode);
                    })
                    ->latest()
                    ->first();

                if ($state && $state->form_data) {
                    // Try to get phone from different possible locations in form_data
                    $formData = is_string($state->form_data) ? json_decode($state->form_data, true) : $state->form_data;

                    // Check in formResponses first (most common location)
                    $phoneNumber = data_get($formData, 'formResponses.mobile')
                        ?? data_get($formData, 'formResponses.cellNumber')
                        ?? data_get($formData, 'formResponses.whatsApp')
                        ?? data_get($formData, 'formResponses.phoneNumber')
                        ?? data_get($formData, 'formResponses.contactPhone')
                        ?? $formData['phone']
                        ?? $formData['contact']['phone']
                        ?? $formData['phoneNumber']
                        ?? data_get($state->metadata, 'phone_number')
                        ?? null;
                }
            } catch (\Exception $e) {
                \Log::warning('Could not retrieve phone number for application success page', [
                    'reference_code' => $referenceCode,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $productName = 'ZB Product';
        $category = 'General';
        $applicationType = 'General'; // Default application type

        if ($state && $state->form_data) {
            $formData = is_string($state->form_data) ? json_decode($state->form_data, true) : $state->form_data;

            // Extract product name
            $productName = data_get($formData, 'selectedProduct.name')
                ?? data_get($formData, 'product.name')
                ?? data_get($formData, 'productName')
                ?? 'ZB Product';

            // Extract category
            $category = data_get($formData, 'selectedProduct.category')
                ?? data_get($formData, 'product.category')
                ?? data_get($formData, 'category')
                ?? 'General';

            // Check for SSB/Government
            $employer = data_get($formData, 'employer') ?? data_get($formData, 'formResponses.employer');
            $employerType = data_get($formData, 'formResponses.employerType');

            // Logic to determine if it's SSB
            if (($employer && stripos($employer, 'Civil Service') !== false) ||
                ($employer && stripos($employer, 'SSB') !== false) ||
                ($employer && stripos($employer, 'Government') !== false) ||
                (is_array($employerType) && !empty($employerType['government'])) ||
                ($employerType === 'government')
            ) {
                $applicationType = 'SSB';
            }
        }

        // Generate tracking URL
        $trackingUrl = route('application.status');

        return Inertia::render('ApplicationSuccess', [
            'referenceCode' => $referenceCode,
            'phoneNumber' => $phoneNumber,
            'applicationType' => $applicationType,
            'productName' => $productName,
            'category' => $category,
            'trackingUrl' => $trackingUrl,
        ]);
    }

    /**
     * Show delivery tracking page
     */
    public function tracking(): Response
    {
        return Inertia::render('DeliveryTracking');
    }
    
    /**
     * Show reference code lookup page
     */
    public function referenceCodeLookup(): Response
    {
        return Inertia::render('ReferenceCodeLookup');
    }
    
    /**
     * Switch application to WhatsApp
     */
    public function switchToWhatsApp(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);
        
        try {
            $syncResult = $this->syncService->switchToWhatsApp(
                $request->session_id,
                $request->phone_number
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Application successfully linked to WhatsApp',
                'reference_code' => $syncResult['reference_code'],
                'whatsapp_instructions' => $this->getWhatsAppInstructions($syncResult['reference_code']),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to switch to WhatsApp: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to link application to WhatsApp. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Switch application to web
     */
    public function switchToWeb(Request $request)
    {
        $request->validate([
            'whatsapp_session_id' => 'required|string',
        ]);
        
        try {
            $syncResult = $this->syncService->switchToWeb($request->whatsapp_session_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Application successfully switched to web',
                'web_session_id' => $syncResult['web_state']->session_id,
                'resume_url' => route('application.resume', $syncResult['web_state']->session_id),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to switch to web: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch application to web. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Get synchronization status
     */
    public function getSyncStatus(Request $request)
    {
        $request->validate([
            'session_id_1' => 'required|string',
            'session_id_2' => 'required|string',
        ]);
        
        try {
            $syncStatus = $this->syncService->getSyncStatus(
                $request->session_id_1,
                $request->session_id_2
            );
            
            return response()->json([
                'success' => true,
                'sync_status' => $syncStatus,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get sync status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get synchronization status.',
            ], 500);
        }
    }
    
    /**
     * Manually synchronize application data
     */
    public function synchronizeData(Request $request)
    {
        $request->validate([
            'primary_session_id' => 'required|string',
            'secondary_session_id' => 'required|string',
        ]);
        
        try {
            $syncResult = $this->syncService->synchronizeApplicationData(
                $request->primary_session_id,
                $request->secondary_session_id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Application data synchronized successfully',
                'sync_result' => $syncResult,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to synchronize data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to synchronize application data. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Get WhatsApp instructions for user
     */
    private function getWhatsAppInstructions(string $referenceCode): array
    {
        return [
            'message' => "Your application is now linked to WhatsApp!",
            'steps' => [
                "Send a WhatsApp message to " . config('services.twilio.whatsapp_from'),
                "Type: resume {$referenceCode}",
                "Continue your application via WhatsApp",
            ],
            'reference_code' => $referenceCode,
        ];
    }
}