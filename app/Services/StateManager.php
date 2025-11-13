<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\StateTransition;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StateManager
{
    /**
     * Default TTL for states in seconds
     */
    private const DEFAULT_TTL = 86400; // 24 hours
    private const WHATSAPP_TTL = 604800; // 7 days
    
    /**
     * @var ReferenceCodeService
     */
    private $referenceCodeService;
    
    /**
     * @var CrossPlatformSyncService
     */
    private $syncService;
    
    /**
     * Constructor
     */
    public function __construct(ReferenceCodeService $referenceCodeService)
    {
        $this->referenceCodeService = $referenceCodeService;
        // Lazy load sync service to avoid circular dependency
    }
    
    /**
     * Save or update application state
     */
    public function saveState(string $sessionId, string $channel, string $userIdentifier, string $step, array $data, array $metadata = []): ApplicationState
    {
        // Validate and sanitize inputs
        $userIdentifier = $this->sanitizeUserIdentifier($userIdentifier);
        $step = $this->validateStep($step);
        $data = $this->sanitizeFormData($data);
        
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                // Reconnect to database if connection was lost
                \DB::reconnect();
                
                $state = ApplicationState::updateOrCreate(
                    ['session_id' => $sessionId],
                    [
                        'channel' => $channel,
                        'user_identifier' => $userIdentifier,
                        'current_step' => $step,
                        'form_data' => $data,
                        'metadata' => $metadata,
                        'expires_at' => $this->getExpirationTime($channel),
                    ]
                );
                
                // Log state transition
                try {
                    $this->logTransition($state->id, $state->getOriginal('current_step'), $step, $channel, $data);
                } catch (\Exception $e) {
                    // Log transition failure but don't fail the main operation
                    \Log::warning('Failed to log state transition: ' . $e->getMessage());
                }

                if (!empty($data['referenceCode']) && is_string($data['referenceCode'])) {
                    $sanitizedCode = strtoupper(trim($data['referenceCode']));
                    if ($sanitizedCode && $sanitizedCode !== ($state->reference_code ?? null)) {
                        $this->referenceCodeService->storeReferenceCode($sessionId, $sanitizedCode);
                        $state = $state->fresh();
                    }
                }
                
                return $state;
                
            } catch (\Exception $e) {
                $retryCount++;
                
                // Check if it's a connection issue
                if ($this->isConnectionError($e) && $retryCount < $maxRetries) {
                    \Log::warning("Database connection lost, retrying... (attempt {$retryCount}/{$maxRetries})");
                    sleep(1); // Wait 1 second before retry
                    continue;
                }
                
                // Log the error with context
                \Log::error('Failed to save application state', [
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'user_identifier' => $userIdentifier,
                    'step' => $step,
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount
                ]);
                
                throw new \Exception("Failed to save application state: " . $e->getMessage());
            }
        }
        
        throw new \Exception("Failed to save application state after {$maxRetries} retries");
    }
    
    /**
     * Sanitize user identifier to prevent database issues
     */
    private function sanitizeUserIdentifier(string $userIdentifier): string
    {
        // Ensure user identifier is not too long and contains valid characters
        $sanitized = preg_replace('/[^a-zA-Z0-9@._+-]/', '', $userIdentifier);
        return substr($sanitized, 0, 255);
    }
    
    /**
     * Validate step name
     */
    private function validateStep(string $step): string
    {
        $validSteps = [
            'language', 'intent', 'employer', 'product', 'account',
            'summary', 'form', 'documents', 'completed', 'in_review',
            'approved', 'rejected', 'pending_documents', 'processing'
        ];
        
        return in_array($step, $validSteps) ? $step : 'language';
    }
    
    /**
     * Sanitize form data to prevent database issues
     */
    private function sanitizeFormData(array $data): array
    {
        // Remove any null bytes or problematic characters that could cause MySQL issues
        return $this->recursiveClean($data);
    }
    
    /**
     * Recursively clean data
     */
    private function recursiveClean($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'recursiveClean'], $data);
        } elseif (is_string($data)) {
            // Remove null bytes and control characters that can cause MySQL issues
            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        }
        
        return $data;
    }
    
    /**
     * Check if the exception is a connection error
     */
    private function isConnectionError(\Exception $e): bool
    {
        $connectionErrors = [
            'MySQL server has gone away',
            'Lost connection to MySQL server',
            'Connection refused',
            'No connection could be made',
            'SQLSTATE[HY000]: General error: 2006',
            'SQLSTATE[08S01]'
        ];
        
        foreach ($connectionErrors as $error) {
            if (strpos($e->getMessage(), $error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Retrieve state for user
     */
    public function retrieveState(string $userIdentifier, ?string $channel = null): ?ApplicationState
    {
        $query = ApplicationState::where('user_identifier', $userIdentifier)
            ->where('expires_at', '>', now())
            ->orderBy('updated_at', 'desc');
            
        if ($channel) {
            $query->where('channel', $channel);
        }
        
        return $query->first();
    }
    
    /**
     * Merge states from different channels
     */
    public function mergeStates(ApplicationState $primaryState, ApplicationState $secondaryState): ApplicationState
    {
        $mergedData = array_merge(
            $secondaryState->form_data ?? [],
            $primaryState->form_data ?? []
        );
        
        $primaryState->update([
            'form_data' => $mergedData,
            'metadata' => array_merge(
                $secondaryState->metadata ?? [],
                $primaryState->metadata ?? [],
                ['merged_from' => $secondaryState->session_id]
            ),
        ]);
        
        return $primaryState;
    }
    
    /**
     * Generate new session ID
     */
    public function generateSessionId(string $channel): string
    {
        return $channel . '_' . Str::random(20);
    }
    
    /**
     * Clear expired states
     */
    public function clearExpiredStates(): int
    {
        return ApplicationState::where('expires_at', '<', now())->delete();
    }
    
    /**
     * Get expiration time based on channel
     */
    private function getExpirationTime(string $channel): Carbon
    {
        $ttl = $channel === 'whatsapp' ? self::WHATSAPP_TTL : self::DEFAULT_TTL;
        return now()->addSeconds($ttl);
    }
    
    /**
     * Generate a reference code for cross-channel linking
     * 
     * @param string $sessionId The session ID to generate a reference code for
     * @return string The generated reference code
     * @throws \Exception If a reference code cannot be generated
     */
    public function generateResumeCode(string $sessionId): string
    {
        // Check if the session already has a reference code
        $state = ApplicationState::where('session_id', $sessionId)->first();
        
        if ($state && $state->reference_code) {
            // If the reference code is about to expire, extend it
            if ($state->reference_code_expires_at && $state->reference_code_expires_at->diffInDays(now()) < 5) {
                $this->referenceCodeService->extendReferenceCode($state->reference_code);
            }
            
            return $state->reference_code;
        }
        
        // Use the ReferenceCodeService to generate a new reference code
        return $this->referenceCodeService->generateReferenceCode($sessionId);
    }
    
    /**
     * Get contextual expiration time based on application state
     */
    private function getContextualExpiration(ApplicationState $state): Carbon
    {
        $currentStep = $state->current_step;
        $formData = $state->form_data ?? [];
        
        // Different expiration times based on context
        switch ($currentStep) {
            case 'form':
                // Long expiration during form filling (user might need time)
                $completedFields = count($formData['formResponses'] ?? []);
                $totalFields = count($formData['formFields'] ?? []);
                
                if ($completedFields > 0) {
                    // User has started filling - give them 2 hours
                    return now()->addHours(2);
                } else {
                    // Just starting form - give them 4 hours
                    return now()->addHours(4);
                }
                
            case 'product':
            case 'business':
            case 'scale':
                // Medium expiration during product selection (less sensitive)
                return now()->addHours(1);
                
            case 'completed':
                // Short expiration for completed applications (just for viewing)
                return now()->addMinutes(15);
                
            default:
                // Default for early stages
                return now()->addMinutes(30);
        }
    }
    
    /**
     * Get state by reference code
     */
    public function getStateByResumeCode(string $resumeCode): ?ApplicationState
    {
        // Use the ReferenceCodeService to get the state by reference code
        return $this->referenceCodeService->getStateByReferenceCode($resumeCode);
    }
    
    /**
     * Link sessions for cross-channel functionality
     */
    public function linkSessions(string $primarySessionId, string $secondarySessionId, string $channel, string $userIdentifier): void
    {
        $primaryState = ApplicationState::where('session_id', $primarySessionId)->first();
        $secondaryState = ApplicationState::where('session_id', $secondarySessionId)->first();
        
        if (!$primaryState) {
            throw new \Exception("Primary session not found: {$primarySessionId}");
        }
        
        if (!$secondaryState) {
            // Create new secondary session linked to primary
            $this->saveState(
                $secondarySessionId,
                $channel,
                $userIdentifier,
                $primaryState->current_step,
                $primaryState->form_data ?? [],
                array_merge($primaryState->metadata ?? [], ['linked_to' => $primarySessionId])
            );
        } else {
            // Use sync service for proper data synchronization
            $this->getSyncService()->synchronizeApplicationData($primarySessionId, $secondarySessionId);
        }
    }
    
    /**
     * Switch platform from web to WhatsApp
     */
    public function switchToWhatsApp(string $webSessionId, string $phoneNumber): array
    {
        return $this->getSyncService()->switchToWhatsApp($webSessionId, $phoneNumber);
    }
    
    /**
     * Switch platform from WhatsApp to web
     */
    public function switchToWeb(string $whatsappSessionId, string $webSessionId = null): array
    {
        return $this->getSyncService()->switchToWeb($whatsappSessionId, $webSessionId);
    }
    
    /**
     * Get synchronization status between sessions
     */
    public function getSyncStatus(string $sessionId1, string $sessionId2): array
    {
        return $this->getSyncService()->getSyncStatus($sessionId1, $sessionId2);
    }
    
    /**
     * Get sync service instance (lazy loading to avoid circular dependency)
     */
    private function getSyncService(): CrossPlatformSyncService
    {
        if (!$this->syncService) {
            $this->syncService = app(CrossPlatformSyncService::class);
        }
        
        return $this->syncService;
    }
    
    /**
     * Log state transition
     */
    private function logTransition(int $stateId, ?string $fromStep, string $toStep, string $channel, array $transitionData): void
    {
        StateTransition::create([
            'state_id' => $stateId,
            'from_step' => $fromStep,
            'to_step' => $toStep,
            'channel' => $channel,
            'transition_data' => $transitionData,
        ]);
    }
}
