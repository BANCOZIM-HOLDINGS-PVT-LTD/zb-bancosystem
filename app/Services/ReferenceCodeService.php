<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReferenceCodeService
{
    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Constructor
     */
    public function __construct(NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Generate a reference code using the applicant's national ID number
     *
     * @param string $sessionId The session ID to associate with the reference code
     * @param string|null $nationalId The national ID number (optional, will be extracted from session if not provided)
     * @return string The national ID as the reference code
     * @throws \Exception If national ID is not found or already exists
     */
    public function generateReferenceCode(string $sessionId, ?string $nationalId = null): string
    {
        // Get the application state
        $state = ApplicationState::where('session_id', $sessionId)->first();

        if (!$state) {
            Log::error("Application state not found for session {$sessionId}");
            throw new \Exception("Application state not found for session {$sessionId}");
        }

        // If national ID not provided, try to extract from form data
        if (!$nationalId) {
            $formData = $state->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];

            // Try multiple possible keys for national ID
            $nationalId = $formResponses['idNumber']
                       ?? $formResponses['nationalIdNumber']
                       ?? $formResponses['nationalId']
                       ?? null;
        }

        if (!$nationalId) {
            Log::error("National ID not found in application data for session {$sessionId}");
            throw new \Exception("National ID is required to generate a reference code. Please ensure your ID number is provided in the application.");
        }

        // Sanitize and format the national ID
        $code = strtoupper(trim($nationalId));

        // Remove any spaces or special characters
        $code = preg_replace('/[^A-Z0-9]/', '', $code);

        // Check if this national ID is already used by another application
        $existingApplication = ApplicationState::where('reference_code', $code)
            ->where('session_id', '!=', $sessionId)
            ->first();

        if ($existingApplication) {
            Log::error("National ID {$code} is already associated with another application");
            throw new \Exception("This national ID number is already associated with an existing application. Each ID can only be used for one application.");
        }

        // Store the reference code with the application state
        $updatedState = $this->storeReferenceCode($sessionId, $code);

        if (!$updatedState) {
            Log::error("Failed to store reference code {$code} for session {$sessionId}");
            throw new \Exception("Failed to store reference code for session {$sessionId}");
        }

        // Send notification with the reference code
        if ($updatedState && $this->notificationService) {
            $this->notificationService->sendReferenceCodeNotification($updatedState, $code);
        }

        Log::info("Generated reference code (National ID) {$code} for session {$sessionId}");
        return $code;
    }

    /**
     * Check if a reference code already exists
     *
     * @param string $code The reference code to check
     * @return bool True if the code exists, false otherwise
     */
    public function referenceCodeExists(string $code): bool
    {
        return ApplicationState::where('reference_code', $code)->exists();
    }

    /**
     * Store a reference code with an application state
     *
     * @param string $sessionId The session ID of the application state
     * @param string $code The reference code to store
     * @return ApplicationState|null The updated application state or null if not found
     */
    public function storeReferenceCode(string $sessionId, string $code): ?ApplicationState
    {
        $code = strtoupper(trim($code));
        $state = ApplicationState::where('session_id', $sessionId)->first();

        if ($state) {
            $state->update([
                'reference_code' => $code,
                'reference_code_expires_at' => Carbon::now()->addDays(30), // Reference codes valid for 30 days
            ]);

            return $state->fresh();
        }

        return null;
    }

    /**
     * Get an application state by reference code
     *
     * @param string $code The reference code to look up
     * @return ApplicationState|null The application state or null if not found
     */
    public function getStateByReferenceCode(string $code): ?ApplicationState
    {
        return ApplicationState::where('reference_code', $code)
            ->where(function($query) {
                $query->whereNull('reference_code_expires_at')
                      ->orWhere('reference_code_expires_at', '>', Carbon::now());
            })
            ->first();
    }

    /**
     * Validate a reference code
     *
     * @param string $code The reference code to validate
     * @return bool True if the code is valid, false otherwise
     */
    public function validateReferenceCode(string $code): bool
    {
        return $this->getStateByReferenceCode($code) !== null;
    }

    /**
     * Extend the expiration time of a reference code
     *
     * @param string $code The reference code to extend
     * @param int $days The number of days to extend the expiration by
     * @return bool True if the code was extended, false otherwise
     */
    public function extendReferenceCode(string $code, int $days = 30): bool
    {
        $state = $this->getStateByReferenceCode($code);

        if ($state) {
            $state->update([
                'reference_code_expires_at' => Carbon::now()->addDays($days),
            ]);
            Log::info("Extended reference code {$code} expiration by {$days} days");
            return true;
        }

        return false;
    }

    /**
     * Get application status by reference code
     *
     * @param string $code The reference code to look up
     * @return array|null The application status or null if not found
     */
    public function getApplicationStatusByReferenceCode(string $code): ?array
    {
        $state = $this->getStateByReferenceCode($code);

        if (!$state) {
            return null;
        }

        $metadata = $state->metadata ?? [];
        $status = $metadata['status'] ?? 'pending';

        return [
            'session_id' => $state->session_id,
            'status' => $status,
            'current_step' => $state->current_step,
            'created_at' => $state->created_at,
            'updated_at' => $state->updated_at,
        ];
    }
}

