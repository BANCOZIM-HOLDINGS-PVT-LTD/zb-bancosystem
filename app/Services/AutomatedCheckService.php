<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;

/**
 * Automated Check Service (Disabled for Manual Processing)
 * 
 * Logic replaced with manual document verification workflow.
 */
class AutomatedCheckService
{
    public function __construct() {
        // Dependencies removed for cleanup
    }

    /**
     * Execute automated checks based on application type.
     * 
     * @param ApplicationState $state
     * @return void
     */
    public function executeAutomatedChecks(ApplicationState $state): void
    {
        Log::info('[AutomatedCheckService] Automated checks are currently disabled. Moving to manual verification.', [
            'session_id' => $state->session_id
        ]);
        
        // No operations here. All logic moved to Filament resources for manual review.
    }
}
