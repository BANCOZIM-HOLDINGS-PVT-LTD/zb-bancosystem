<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StateManager;
use App\Services\Cache\ApplicationCacheManager;
use App\Http\Requests\SaveApplicationStateRequest;
use App\Repositories\ApplicationStateRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Models\ApplicationState;

class StateController extends Controller
{
    private StateManager $stateManager;
    protected ApplicationStateRepository $repository;
    protected ApplicationCacheManager $cacheManager;

    public function __construct(
        StateManager $stateManager,
        ApplicationStateRepository $repository,
        ApplicationCacheManager $cacheManager
    ) {
        $this->stateManager = $stateManager;
        $this->repository = $repository;
        $this->cacheManager = $cacheManager;
    }
    
    /**
     * Save application state
     */
    public function saveState(SaveApplicationStateRequest $request): JsonResponse
    {
        try {
            \Log::info('Entering StateController::saveState', ['session_id' => $request->input('session_id')]);
            
            \Log::info('Entering StateController::saveState', ['session_id' => $request->input('session_id')]);
            
            // Increase execution time for this request to handle slow local DB operations
            set_time_limit(300);

            $validated = $request->validated();
            
            $state = $this->stateManager->saveState(
                $validated['session_id'],
                $validated['channel'],
                $validated['user_identifier'],
                $validated['current_step'],
                $validated['form_data'],
                $validated['metadata'] ?? []
            );

            // Cache the updated state
            $this->cacheManager->cacheApplicationState($state);

            return response()->json([
                'success' => true,
                'state_id' => $state->id,
                'expires_at' => $state->expires_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in StateController::saveState', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save state: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Retrieve application state
     */
    public function retrieveState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user' => 'required|string',
            'channel' => 'nullable|in:web,whatsapp,ussd,mobile_app',
        ]);

        // Create cache key
        $cacheKey = "application_state:{$validated['user']}:" . ($validated['channel'] ?? 'default');

        // Try to get from cache first
        $state = Cache::remember($cacheKey, 300, function () use ($validated) {
            return $this->stateManager->retrieveState(
                $validated['user'],
                $validated['channel'] ?? null
            );
        });

        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'No active state found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'session_id' => $state->session_id,
            'current_step' => $state->current_step,
            'form_data' => $state->form_data,
            'can_resume' => true,
            'expires_in' => $state->expires_at->diffInSeconds(now()),
        ]);
    }
    
    /**
     * Create a new application (final submission)
     */
    public function createApplication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sessionId' => 'required|string',
            'data' => 'required|array',
        ]);

        try {
            // Generate a proper user identifier for submission
            $userIdentifier = $this->getUserIdentifierForSubmission($request, $validated['data']);
            
            // Increase execution time for final submission
            set_time_limit(300);

            // Update user's National ID if authenticated and missing
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (empty($user->national_id)) {
                    $nationalId = $validated['data']['formResponses']['nationalIdNumber'] ?? null;
                    if ($nationalId && is_string($nationalId)) {
                        // Clean and format the ID if needed, or just save as is
                        // Using the same format typically expected: XX-XXXXXXX-Y-XX
                        // We might want to run it through validator or formatter if available, 
                        // but sticking to raw input from form which should optionally be validated on frontend
                        $user->update(['national_id' => $nationalId]);
                    }
                }
            }

            // Update the application state to pending_verification
            $start = microtime(true);
            $state = $this->stateManager->saveState(
                $validated['sessionId'],
                'web', // Default to web channel
                $userIdentifier,
                'pending_verification', 
                $validated['data'],
                [
                    'submitted_at' => now()->toISOString(),
                    'submission_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
            $saveStateDuration = microtime(true) - $start;
            \Log::info("saveState duration: " . round($saveStateDuration, 4) . "s");

            // Generate reference code
            $start = microtime(true);
            if (!$state->reference_code) {
                $referenceCodeService = app(\App\Services\ReferenceCodeService::class);
                $referenceCode = $referenceCodeService->generateReferenceCode($validated['sessionId']);

                // Reload the state to get the updated reference code
                $state = $state->fresh();
            } else {
                $referenceCode = $state->reference_code;
            }
            $refCodeDuration = microtime(true) - $start;
            \Log::info("Reference Code duration: " . round($refCodeDuration, 4) . "s");

            // Check if this is an Account Opening application
            $formData = $validated['data'];
            $isAccountOpening = ($formData['wantsAccount'] ?? false) === true ||
                               ($formData['intent'] ?? '') === 'account' ||
                               ($formData['applicationType'] ?? '') === 'account_opening';

            if ($isAccountOpening) {
                try {
                    $start = microtime(true);
                    $accountOpeningService = app(\App\Services\AccountOpeningService::class);
                    $accountOpening = $accountOpeningService->createFromWizard($formData, $referenceCode);
                    
                    // Link the account opening to the application state
                    $accountOpening->update(['application_state_id' => $state->id]);
                    
                    $accOpenDuration = microtime(true) - $start;
                    \Log::info("Account Opening duration: " . round($accOpenDuration, 4) . "s");

                    \Log::info('Account Opening created successfully', [
                        'reference_code' => $referenceCode,
                        'account_opening_id' => $accountOpening->id
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create Account Opening record', [
                        'error' => $e->getMessage(),
                        'reference_code' => $referenceCode
                    ]);
                    // Don't fail the whole submission - the ApplicationState was already created
                }
            }

            
            // Send confirmation SMS
            try {
                // We use the app() helper to resolve the service since it might not be injected in the method
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendApplicationSubmittedNotification($state);
            } catch (\Exception $e) {
                \Log::error('Failed to send confirmation SMS: ' . $e->getMessage());
                // Don't fail the request if SMS fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $state->session_id,
                'reference_number' => $referenceCode,
                'reference_code' => $referenceCode,
            ]);
        } catch (\Exception $e) {
            \Log::error('Application submission failed', [
                'session_id' => $validated['sessionId'] ?? 'unknown',
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate a proper user identifier for final submission
     */
    private function getUserIdentifierForSubmission(Request $request, array $data): string
    {
        // Try to use email from form data first
        if (!empty($data['formResponses']['emailAddress'])) {
            $email = $data['formResponses']['emailAddress'];
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return substr($email, 0, 255);
            }
        }
        
        // Try to use mobile number
        if (!empty($data['formResponses']['mobile'])) {
            $mobile = preg_replace('/[^0-9+]/', '', $data['formResponses']['mobile']);
            if (!empty($mobile)) {
                return 'mobile_' . substr($mobile, 0, 240); // Leave room for prefix
            }
        }
        
        // Try to use national ID
        if (!empty($data['formResponses']['nationalIdNumber'])) {
            $nationalId = preg_replace('/[^a-zA-Z0-9-]/', '', $data['formResponses']['nationalIdNumber']);
            if (!empty($nationalId)) {
                return 'id_' . substr($nationalId, 0, 250); // Leave room for prefix
            }
        }
        
        // Fallback to sanitized IP + timestamp
        $ip = $request->ip() ?? 'unknown';
        $sanitizedIp = preg_replace('/[^0-9.]/', '', $ip);
        return 'user_' . $sanitizedIp . '_' . time();
    }
    

    /**
     * Link sessions across channels
     */
    public function linkSessions(Request $request): JsonResponse
    {
        // This will be implemented later
        return response()->json([
            'success' => false,
            'message' => 'Feature not yet implemented',
        ], 501);
    }

    /**
     * Check for existing incomplete session by phone number
     */
    public function checkExistingSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'current_session_id' => 'nullable|string',
        ]);
        
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        // Allow strictly Zimbabwe numbers or numbers with reasonable length
        if (strlen($phone) < 9) {
             return response()->json(['has_existing_session' => false]);
        }

        // Look for incomplete applications with this phone
        // We check user_identifier (often "mobile_263...") and form_data
        $existingSession = ApplicationState::query()
            ->where('is_archived', false)
            ->whereNotIn('current_step', ['completed', 'approved', 'rejected'])
            ->where(function ($query) use ($phone) {
                // Check user_identifier (e.g. mobile_26377...)
                $query->where('user_identifier', 'LIKE', "%{$phone}%");
                
                // For MySQL/PostgreSQL compatibility in raw queries:
                 $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                 if ($isPgsql) {
                     $query->orWhereRaw("form_data->'formResponses'->>'mobile' LIKE ?", ["%{$phone}%"])
                           ->orWhereRaw("form_data->'formResponses'->>'phoneNumber' LIKE ?", ["%{$phone}%"]);
                 } else {
                     $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.mobile')) LIKE ?", ["%{$phone}%"])
                           ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.phoneNumber')) LIKE ?", ["%{$phone}%"]);
                 }
            })
            ->when($request->input('current_session_id'), function($q, $id) {
                $q->where('session_id', '!=', $id);
            })
            ->latest()
            ->first();
        
        if ($existingSession) {
            return response()->json([
                'has_existing_session' => true,
                'session_id' => $existingSession->session_id,
                'current_step' => $existingSession->current_step,
                'last_activity' => $existingSession->updated_at,
            ]);
        }
        
        return response()->json(['has_existing_session' => false]);
    }

    /**
     * Discard an existing session (Hard Delete)
     */
    public function discardSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);
        
        $session = ApplicationState::where('session_id', $validated['session_id'])->first();
        
        if ($session) {
            // Hard delete as requested to prevent duplicate applications
            $session->forceDelete();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Session not found'], 404);
    }
}
