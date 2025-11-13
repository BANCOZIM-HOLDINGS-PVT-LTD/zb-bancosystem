<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrossPlatformSyncService
{
    private StateManager $stateManager;
    private ReferenceCodeService $referenceCodeService;
    
    public function __construct(
        StateManager $stateManager,
        ReferenceCodeService $referenceCodeService
    ) {
        $this->stateManager = $stateManager;
        $this->referenceCodeService = $referenceCodeService;
    }
    
    /**
     * Synchronize data between web and WhatsApp platforms
     */
    public function synchronizeApplicationData(string $primarySessionId, string $secondarySessionId): array
    {
        return DB::transaction(function () use ($primarySessionId, $secondarySessionId) {
            $primaryState = ApplicationState::where('session_id', $primarySessionId)->first();
            $secondaryState = ApplicationState::where('session_id', $secondarySessionId)->first();
            
            if (!$primaryState && !$secondaryState) {
                throw new \Exception("No application states found for synchronization");
            }
            
            // Determine which state is more recent or complete
            $mergedData = $this->mergeApplicationData($primaryState, $secondaryState);
            
            // Update both states with merged data
            $syncResult = $this->updateBothStates($primaryState, $secondaryState, $mergedData);
            
            Log::info('Cross-platform synchronization completed', [
                'primary_session' => $primarySessionId,
                'secondary_session' => $secondarySessionId,
                'merged_fields' => count($mergedData['form_data'] ?? []),
                'sync_timestamp' => now()->toISOString()
            ]);
            
            return $syncResult;
        });
    }
    
    /**
     * Handle platform switching from web to WhatsApp
     */
    public function switchToWhatsApp(string $webSessionId, string $phoneNumber): array
    {
        $webState = ApplicationState::where('session_id', $webSessionId)->first();
        
        if (!$webState) {
            throw new \Exception("Web session not found: {$webSessionId}");
        }
        
        // Clean phone number (remove + and any non-digits)
        $cleanPhoneNumber = preg_replace('/[^\d]/', '', $phoneNumber);
        $whatsappSessionId = 'whatsapp_' . $cleanPhoneNumber;
        
        // Check if WhatsApp session already exists
        $whatsappState = ApplicationState::where('session_id', $whatsappSessionId)->first();
        
        if ($whatsappState) {
            // Merge existing WhatsApp state with web state
            $syncResult = $this->synchronizeApplicationData($webSessionId, $whatsappSessionId);
        } else {
            // Create new WhatsApp state from web state
            $syncResult = $this->createWhatsAppStateFromWeb($webState, $whatsappSessionId, $cleanPhoneNumber);
        }
        
        // Generate or retrieve reference code for cross-platform access
        $referenceCode = $this->ensureReferenceCode($webState);
        
        Log::info('Platform switch to WhatsApp completed', [
            'web_session' => $webSessionId,
            'whatsapp_session' => $whatsappSessionId,
            'phone_number' => $cleanPhoneNumber,
            'reference_code' => $referenceCode,
            'current_step' => $syncResult['current_step']
        ]);
        
        return array_merge($syncResult, ['reference_code' => $referenceCode]);
    }
    
    /**
     * Handle platform switching from WhatsApp to web
     */
    public function switchToWeb(string $whatsappSessionId, string $webSessionId = null): array
    {
        $whatsappState = ApplicationState::where('session_id', $whatsappSessionId)->first();
        
        if (!$whatsappState) {
            throw new \Exception("WhatsApp session not found: {$whatsappSessionId}");
        }
        
        // Generate web session ID if not provided
        if (!$webSessionId) {
            $webSessionId = $this->stateManager->generateSessionId('web');
        }
        
        // Check if web session already exists
        $webState = ApplicationState::where('session_id', $webSessionId)->first();
        
        if ($webState) {
            // Merge existing web state with WhatsApp state
            $syncResult = $this->synchronizeApplicationData($whatsappSessionId, $webSessionId);
        } else {
            // Create new web state from WhatsApp state
            $syncResult = $this->createWebStateFromWhatsApp($whatsappState, $webSessionId);
        }
        
        Log::info('Platform switch to web completed', [
            'whatsapp_session' => $whatsappSessionId,
            'web_session' => $webSessionId,
            'current_step' => $syncResult['current_step']
        ]);
        
        return $syncResult;
    }
    
    /**
     * Ensure consistent data format across platforms
     */
    public function normalizeDataForPlatform(array $data, string $targetPlatform): array
    {
        $normalizedData = $data;
        
        switch ($targetPlatform) {
            case 'web':
                $normalizedData = $this->normalizeForWeb($data);
                break;
            case 'whatsapp':
                $normalizedData = $this->normalizeForWhatsApp($data);
                break;
        }
        
        return $normalizedData;
    }
    
    /**
     * Validate data consistency across platforms
     */
    public function validateDataConsistency(ApplicationState $state1, ApplicationState $state2): array
    {
        $inconsistencies = [];
        
        // Check critical fields for consistency
        $criticalFields = [
            'language', 'intent', 'employer', 'hasAccount', 
            'selectedCategory', 'selectedBusiness', 'selectedScale',
            'formResponses'
        ];
        
        foreach ($criticalFields as $field) {
            $value1 = data_get($state1->form_data, $field);
            $value2 = data_get($state2->form_data, $field);
            
            if ($this->valuesAreDifferent($value1, $value2)) {
                $inconsistencies[] = [
                    'field' => $field,
                    'state1_value' => $value1,
                    'state2_value' => $value2,
                    'resolution' => $this->getResolutionStrategy($field, $value1, $value2)
                ];
            }
        }
        
        return $inconsistencies;
    }
    
    /**
     * Resolve data conflicts between platforms
     */
    public function resolveDataConflicts(array $inconsistencies, string $preferredSource = 'latest'): array
    {
        $resolvedData = [];
        
        foreach ($inconsistencies as $conflict) {
            $field = $conflict['field'];
            $resolution = $conflict['resolution'];
            
            switch ($resolution['strategy']) {
                case 'prefer_latest':
                    $resolvedData[$field] = $resolution['value'];
                    break;
                case 'prefer_complete':
                    $resolvedData[$field] = $this->selectMoreCompleteValue(
                        $conflict['state1_value'],
                        $conflict['state2_value']
                    );
                    break;
                case 'merge':
                    $resolvedData[$field] = $this->mergeValues(
                        $conflict['state1_value'],
                        $conflict['state2_value']
                    );
                    break;
                default:
                    $resolvedData[$field] = $conflict['state1_value'];
            }
        }
        
        return $resolvedData;
    }
    
    /**
     * Get synchronization status between platforms
     */
    public function getSyncStatus(string $sessionId1, string $sessionId2): array
    {
        $state1 = ApplicationState::where('session_id', $sessionId1)->first();
        $state2 = ApplicationState::where('session_id', $sessionId2)->first();
        
        if (!$state1 || !$state2) {
            return [
                'status' => 'not_linked',
                'message' => 'One or both sessions not found'
            ];
        }
        
        $inconsistencies = $this->validateDataConsistency($state1, $state2);
        $lastSync = $this->getLastSyncTime($state1, $state2);
        
        return [
            'status' => empty($inconsistencies) ? 'synchronized' : 'needs_sync',
            'inconsistencies_count' => count($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'last_sync' => $lastSync,
            'state1_updated' => $state1->updated_at,
            'state2_updated' => $state2->updated_at
        ];
    }
    
    /**
     * Merge application data from two states
     */
    private function mergeApplicationData(?ApplicationState $state1, ?ApplicationState $state2): array
    {
        if (!$state1 && !$state2) {
            return ['form_data' => [], 'metadata' => []];
        }
        
        if (!$state1) {
            return [
                'form_data' => $state2->form_data ?? [],
                'metadata' => $state2->metadata ?? [],
                'current_step' => $state2->current_step,
                'channel' => $state2->channel,
                'user_identifier' => $state2->user_identifier
            ];
        }
        
        if (!$state2) {
            return [
                'form_data' => $state1->form_data ?? [],
                'metadata' => $state1->metadata ?? [],
                'current_step' => $state1->current_step,
                'channel' => $state1->channel,
                'user_identifier' => $state1->user_identifier
            ];
        }
        
        // Determine which state is more recent or complete
        $primaryState = $this->selectPrimaryState($state1, $state2);
        $secondaryState = $primaryState === $state1 ? $state2 : $state1;
        
        // Merge form data with primary state taking precedence
        $mergedFormData = array_merge(
            $secondaryState->form_data ?? [],
            $primaryState->form_data ?? []
        );
        
        // Merge metadata
        $mergedMetadata = array_merge(
            $secondaryState->metadata ?? [],
            $primaryState->metadata ?? [],
            [
                'last_sync' => now()->toISOString(),
                'sync_source' => $primaryState->channel,
                'merged_from' => $secondaryState->session_id
            ]
        );
        
        return [
            'form_data' => $mergedFormData,
            'metadata' => $mergedMetadata,
            'current_step' => $primaryState->current_step,
            'channel' => $primaryState->channel,
            'user_identifier' => $primaryState->user_identifier
        ];
    }
    
    /**
     * Update both states with merged data
     */
    private function updateBothStates(?ApplicationState $state1, ?ApplicationState $state2, array $mergedData): array
    {
        $results = [];
        
        if ($state1) {
            $state1->update([
                'form_data' => $mergedData['form_data'],
                'metadata' => array_merge($mergedData['metadata'], ['platform' => $state1->channel]),
                'current_step' => $mergedData['current_step']
            ]);
            $results['state1'] = $state1->fresh();
        }
        
        if ($state2) {
            $state2->update([
                'form_data' => $mergedData['form_data'],
                'metadata' => array_merge($mergedData['metadata'], ['platform' => $state2->channel]),
                'current_step' => $mergedData['current_step']
            ]);
            $results['state2'] = $state2->fresh();
        }
        
        return [
            'synchronized_states' => $results,
            'current_step' => $mergedData['current_step'],
            'sync_timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Create WhatsApp state from web state
     */
    private function createWhatsAppStateFromWeb(ApplicationState $webState, string $whatsappSessionId, string $phoneNumber): array
    {
        $whatsappState = ApplicationState::create([
            'session_id' => $whatsappSessionId,
            'channel' => 'whatsapp',
            'user_identifier' => $phoneNumber,
            'current_step' => $webState->current_step,
            'form_data' => $this->normalizeForWhatsApp($webState->form_data ?? []),
            'metadata' => array_merge(
                $webState->metadata ?? [],
                [
                    'created_from_web' => $webState->session_id,
                    'platform_switch_time' => now()->toISOString(),
                    'phone_number' => $phoneNumber
                ]
            ),
            'expires_at' => now()->addDays(7), // WhatsApp sessions last longer
            'reference_code' => $webState->reference_code,
            'reference_code_expires_at' => $webState->reference_code_expires_at
        ]);
        
        return [
            'whatsapp_state' => $whatsappState,
            'current_step' => $whatsappState->current_step,
            'sync_timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Create web state from WhatsApp state
     */
    private function createWebStateFromWhatsApp(ApplicationState $whatsappState, string $webSessionId): array
    {
        $webState = ApplicationState::create([
            'session_id' => $webSessionId,
            'channel' => 'web',
            'user_identifier' => $webSessionId, // Web uses session ID as identifier
            'current_step' => $whatsappState->current_step,
            'form_data' => $this->normalizeForWeb($whatsappState->form_data ?? []),
            'metadata' => array_merge(
                $whatsappState->metadata ?? [],
                [
                    'created_from_whatsapp' => $whatsappState->session_id,
                    'platform_switch_time' => now()->toISOString()
                ]
            ),
            'expires_at' => now()->addHours(24), // Web sessions are shorter
            'reference_code' => $whatsappState->reference_code,
            'reference_code_expires_at' => $whatsappState->reference_code_expires_at
        ]);
        
        return [
            'web_state' => $webState,
            'current_step' => $webState->current_step,
            'sync_timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Normalize data for web platform
     */
    private function normalizeForWeb(array $data): array
    {
        // Convert WhatsApp-specific data structures to web format
        $normalized = $data;
        
        // Convert simple selections to web format
        if (isset($data['selectedCategory']) && is_array($data['selectedCategory'])) {
            $normalized['category'] = $data['selectedCategory']['id'] ?? null;
        }
        
        if (isset($data['selectedBusiness']) && is_array($data['selectedBusiness'])) {
            $normalized['business'] = $data['selectedBusiness']['id'] ?? null;
        }
        
        if (isset($data['selectedScale']) && is_array($data['selectedScale'])) {
            $normalized['scale'] = $data['selectedScale']['id'] ?? null;
        }
        
        // Ensure form responses are in the correct format for web
        if (isset($data['formResponses']) && is_array($data['formResponses'])) {
            $normalized['formResponses'] = $this->normalizeFormResponses($data['formResponses'], 'web');
        }
        
        return $normalized;
    }
    
    /**
     * Normalize data for WhatsApp platform
     */
    private function normalizeForWhatsApp(array $data): array
    {
        // Convert web-specific data structures to WhatsApp format
        $normalized = $data;
        
        // Ensure WhatsApp has the detailed objects it needs
        if (isset($data['category']) && !isset($data['selectedCategory'])) {
            $normalized['selectedCategory'] = $this->getCategoryDetails($data['category']);
        }
        
        if (isset($data['business']) && !isset($data['selectedBusiness'])) {
            $normalized['selectedBusiness'] = $this->getBusinessDetails($data['business']);
        }
        
        if (isset($data['scale']) && !isset($data['selectedScale'])) {
            $normalized['selectedScale'] = $this->getScaleDetails($data['scale']);
        }
        
        // Ensure form responses are in the correct format for WhatsApp
        if (isset($data['formResponses']) && is_array($data['formResponses'])) {
            $normalized['formResponses'] = $this->normalizeFormResponses($data['formResponses'], 'whatsapp');
        }
        
        return $normalized;
    }
    
    /**
     * Select the primary state based on completeness and recency
     */
    private function selectPrimaryState(ApplicationState $state1, ApplicationState $state2): ApplicationState
    {
        // Prefer the state with more form data
        $state1DataCount = count($state1->form_data ?? []);
        $state2DataCount = count($state2->form_data ?? []);
        
        if ($state1DataCount !== $state2DataCount) {
            return $state1DataCount > $state2DataCount ? $state1 : $state2;
        }
        
        // If equal data, prefer the more recent one
        return $state1->updated_at->gt($state2->updated_at) ? $state1 : $state2;
    }
    
    /**
     * Check if two values are different
     */
    private function valuesAreDifferent($value1, $value2): bool
    {
        if (is_array($value1) && is_array($value2)) {
            return json_encode($value1) !== json_encode($value2);
        }
        
        return $value1 !== $value2;
    }
    
    /**
     * Get resolution strategy for field conflicts
     */
    private function getResolutionStrategy(string $field, $value1, $value2): array
    {
        // Define resolution strategies for different fields
        $strategies = [
            'formResponses' => 'merge',
            'selectedCategory' => 'prefer_complete',
            'selectedBusiness' => 'prefer_complete',
            'selectedScale' => 'prefer_complete',
            'language' => 'prefer_latest',
            'intent' => 'prefer_latest',
            'employer' => 'prefer_latest'
        ];
        
        $strategy = $strategies[$field] ?? 'prefer_latest';
        
        return [
            'strategy' => $strategy,
            'value' => $this->selectValueByStrategy($strategy, $value1, $value2)
        ];
    }
    
    /**
     * Select value based on resolution strategy
     */
    private function selectValueByStrategy(string $strategy, $value1, $value2)
    {
        switch ($strategy) {
            case 'prefer_complete':
                return $this->selectMoreCompleteValue($value1, $value2);
            case 'merge':
                return $this->mergeValues($value1, $value2);
            case 'prefer_latest':
            default:
                return $value2; // Assume value2 is more recent
        }
    }
    
    /**
     * Select the more complete value
     */
    private function selectMoreCompleteValue($value1, $value2)
    {
        if (is_array($value1) && is_array($value2)) {
            return count($value1) >= count($value2) ? $value1 : $value2;
        }
        
        if (empty($value1) && !empty($value2)) {
            return $value2;
        }
        
        if (!empty($value1) && empty($value2)) {
            return $value1;
        }
        
        return $value2; // Default to second value
    }
    
    /**
     * Merge two values
     */
    private function mergeValues($value1, $value2)
    {
        if (is_array($value1) && is_array($value2)) {
            return array_merge($value1, $value2);
        }
        
        return $value2 ?? $value1;
    }
    
    /**
     * Get last sync time between states
     */
    private function getLastSyncTime(ApplicationState $state1, ApplicationState $state2): ?string
    {
        $sync1 = data_get($state1->metadata, 'last_sync');
        $sync2 = data_get($state2->metadata, 'last_sync');
        
        if (!$sync1 && !$sync2) {
            return null;
        }
        
        if (!$sync1) {
            return $sync2;
        }
        
        if (!$sync2) {
            return $sync1;
        }
        
        return Carbon::parse($sync1)->gt(Carbon::parse($sync2)) ? $sync1 : $sync2;
    }
    
    /**
     * Ensure reference code exists for cross-platform access
     */
    private function ensureReferenceCode(ApplicationState $state): string
    {
        if ($state->reference_code) {
            return $state->reference_code;
        }
        
        return $this->stateManager->generateResumeCode($state->session_id);
    }
    
    /**
     * Normalize form responses for platform
     */
    private function normalizeFormResponses(array $responses, string $platform): array
    {
        // Platform-specific normalization of form responses
        return $responses; // Placeholder - implement specific normalization as needed
    }
    
    /**
     * Get category details by ID
     */
    private function getCategoryDetails(string $categoryId): ?array
    {
        // This would typically fetch from a service or database
        // Placeholder implementation
        return ['id' => $categoryId, 'name' => 'Category ' . $categoryId];
    }
    
    /**
     * Get business details by ID
     */
    private function getBusinessDetails(string $businessId): ?array
    {
        // This would typically fetch from a service or database
        // Placeholder implementation
        return ['id' => $businessId, 'name' => 'Business ' . $businessId];
    }
    
    /**
     * Get scale details by ID
     */
    private function getScaleDetails(string $scaleId): ?array
    {
        // This would typically fetch from a service or database
        // Placeholder implementation
        return ['id' => $scaleId, 'name' => 'Scale ' . $scaleId];
    }
}