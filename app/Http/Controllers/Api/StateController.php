<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveApplicationStateRequest;
use App\Repositories\ApplicationStateRepository;
use App\Services\Cache\ApplicationCacheManager;
use App\Services\StateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $cacheKey = "application_state:{$validated['user']}:".($validated['channel'] ?? 'default');

        // Try to get from cache first
        $state = Cache::remember($cacheKey, 300, function () use ($validated) {
            return $this->stateManager->retrieveState(
                $validated['user'],
                $validated['channel'] ?? null
            );
        });

        if (! $state) {
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

            // Update the application state to completed
            $state = $this->stateManager->saveState(
                $validated['sessionId'],
                'web', // Default to web channel
                $userIdentifier,
                'completed',
                $validated['data'],
                [
                    'completed_at' => now()->toISOString(),
                    'submission_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Generate reference code (National ID) if not already present
            if (! $state->reference_code) {
                $referenceCodeService = app(\App\Services\ReferenceCodeService::class);
                $referenceCode = $referenceCodeService->generateReferenceCode($validated['sessionId']);

                // Reload the state to get the updated reference code
                $state = $state->fresh();
            } else {
                $referenceCode = $state->reference_code;
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
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a proper user identifier for final submission
     */
    private function getUserIdentifierForSubmission(Request $request, array $data): string
    {
        // Try to use email from form data first
        if (! empty($data['formResponses']['emailAddress'])) {
            $email = $data['formResponses']['emailAddress'];
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return substr($email, 0, 255);
            }
        }

        // Try to use mobile number
        if (! empty($data['formResponses']['mobile'])) {
            $mobile = preg_replace('/[^0-9+]/', '', $data['formResponses']['mobile']);
            if (! empty($mobile)) {
                return 'mobile_'.substr($mobile, 0, 240); // Leave room for prefix
            }
        }

        // Try to use national ID
        if (! empty($data['formResponses']['nationalIdNumber'])) {
            $nationalId = preg_replace('/[^a-zA-Z0-9-]/', '', $data['formResponses']['nationalIdNumber']);
            if (! empty($nationalId)) {
                return 'id_'.substr($nationalId, 0, 250); // Leave room for prefix
            }
        }

        // Fallback to sanitized IP + timestamp
        $ip = $request->ip() ?? 'unknown';
        $sanitizedIp = preg_replace('/[^0-9.]/', '', $ip);

        return 'user_'.$sanitizedIp.'_'.time();
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
}
