<?php

namespace App\Observers;

use App\Models\ApplicationState;
use App\Models\PersonalService;
use Illuminate\Support\Facades\Log;

class ApplicationStateObserver
{
    /**
     * Prevent infinite loops
     */
    private static bool $isProcessing = false;

    /**
     * Handle the ApplicationState "updated" event.
     * Auto-create PersonalService records when personal service loans are approved.
     */
    public function updated(ApplicationState $applicationState): void
    {
        // Prevent infinite recursion if we trigger another update
        if (self::$isProcessing) {
            return;
        }

        self::$isProcessing = true;

        try {
            // Track if status changed
            $statusChanged = $applicationState->isDirty('current_step');
            $oldStatus = $applicationState->getOriginal('current_step');
            $newStatus = $applicationState->current_step;

        // Send status update notification if status changed
        if ($statusChanged && $oldStatus && $newStatus) {
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendStatusUpdateNotification(
                    $applicationState,
                    $oldStatus,
                    $newStatus
                );
                
                Log::info('Status update notification sent', [
                    'application_id' => $applicationState->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            } catch (\Exception $e) {
                // Log and continue - ensure this doesn't block the request
                Log::error('Failed to send status update notification', [
                    'application_id' => $applicationState->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Only process PersonalService creation when status changes to approved/completed
        if (!in_array($applicationState->current_step, ['approved', 'completed'])) {
            return;
        }

        // Check if this is a personal service application
        try {
            if (!$this->isPersonalService($applicationState)) {
                return;
            }
        } catch (\Exception $e) {
             Log::error('Error checking isPersonalService', ['error' => $e->getMessage()]);
             return;
        }

        // Check if PersonalService already exists for this application
        if (PersonalService::where('application_state_id', $applicationState->id)->exists()) {
            return;
        }

        // Create PersonalService record
        try {
            $this->createPersonalService($applicationState);
        } catch (\Exception $e) {
            // Log and continue - do not fail the main request
            Log::error('Failed to auto-create PersonalService', [
                'application_id' => $applicationState->id,
                'error' => $e->getMessage(),
            ]);
        }
        } catch (\Exception $e) {
            // Log global observer error
            Log::error('Observer Error', ['error' => $e->getMessage()]);
        } finally {
            self::$isProcessing = false;
        }
    }

    /**
     * Determine if the application is for a personal service
     */
    protected function isPersonalService(ApplicationState $applicationState): bool
    {
        $formData = $applicationState->form_data;
        
        // Check category or business type
        $category = $formData['category'] ?? '';
        $business = $formData['business'] ?? '';
        $productName = $formData['productName'] ?? '';
        
        // Personal service indicators
        $personalServiceKeywords = [
            'vacation',
            'holiday',
            'zimparks',
            'school fees',
            'education',
            'license',
            'driving',
            'funeral',
            'personal service',
            'chicken',
            'poultry',
            'broiler',
            'layer',
            'grocery',
            'groceries',
            'tuckshop',
            'food',
            'building',
            'material',
            'cement',
            'timber',
            'roofing',
            'brick',
        ];

        $searchText = strtolower($category . ' ' . $business . ' ' . $productName);
        
        foreach ($personalServiceKeywords as $keyword) {
            if (str_contains($searchText, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create PersonalService record from ApplicationState
     */
    protected function createPersonalService(ApplicationState $applicationState): void
    {
        $formData = $applicationState->form_data;
        $formResponses = $formData['formResponses'] ?? [];

        // Determine service type
        $serviceType = $this->determineServiceType($formData);

        // Extract relevant data
        $personalService = PersonalService::create([
            'application_state_id' => $applicationState->id,
            'service_type' => $serviceType,
            'reference_code' => $applicationState->reference_code,
            'client_name' => trim(
                ($formResponses['firstName'] ?? '') . ' ' . 
                ($formResponses['lastName'] ?? '')
            ),
            'national_id' => $formResponses['nationalIdNumber'] ?? $formResponses['idNumber'] ?? null,
            'phone' => $formResponses['phoneNumber'] ?? $formResponses['mobile'] ?? null,
            'destination' => $formData['destination'] ?? $formData['location'] ?? null,
            'start_date' => $formData['startDate'] ?? $formData['start_date'] ?? null,
            'end_date' => $formData['endDate'] ?? $formData['end_date'] ?? null,
            'total_cost' => $formData['finalPrice'] ?? $formData['amount'] ?? 0,
            'deposit_amount' => $formData['depositAmount'] ?? 0,
            'status' => PersonalService::STATUS_APPROVED,
            'notes' => $formData['notes'] ?? null,
        ]);

        Log::info('Auto-created PersonalService from approved application', [
            'application_id' => $applicationState->id,
            'personal_service_id' => $personalService->id,
            'service_type' => $serviceType,
        ]);
    }

    /**
     * Determine the service type from form data
     */
    protected function determineServiceType(array $formData): string
    {
        $category = strtolower($formData['category'] ?? '');
        $business = strtolower($formData['business'] ?? '');
        $productName = strtolower($formData['productName'] ?? '');
        
        $searchText = $category . ' ' . $business . ' ' . $productName;

        if (str_contains($searchText, 'vacation') || str_contains($searchText, 'holiday') || str_contains($searchText, 'zimparks')) {
            return PersonalService::TYPE_VACATION;
        }

        if (str_contains($searchText, 'school') || str_contains($searchText, 'education') || str_contains($searchText, 'fees')) {
            return PersonalService::TYPE_SCHOOL_FEES;
        }

        if (str_contains($searchText, 'license') || str_contains($searchText, 'driving')) {
            return PersonalService::TYPE_DRIVING_LICENSE;
        }

        if (str_contains($searchText, 'funeral') || str_contains($searchText, 'cover')) {
            return PersonalService::TYPE_FUNERAL_COVER;
        }

        if (str_contains($searchText, 'chicken') || str_contains($searchText, 'poultry') || str_contains($searchText, 'broiler') || str_contains($searchText, 'layer')) {
            return PersonalService::TYPE_POULTRY;
        }

        if (str_contains($searchText, 'grocery') || str_contains($searchText, 'groceries') || str_contains($searchText, 'tuckshop') || str_contains($searchText, 'food')) {
            return PersonalService::TYPE_GROCERIES;
        }

        if (str_contains($searchText, 'building') || str_contains($searchText, 'cement') || str_contains($searchText, 'brick') || str_contains($searchText, 'timber') || str_contains($searchText, 'roofing')) {
            return PersonalService::TYPE_BUILDING_MATERIALS;
        }

        return PersonalService::TYPE_OTHER;
    }
}
