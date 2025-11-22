<?php

namespace App\Services;

use App\Enums\SSBLoanStatus;
use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SSBStatusService
{
    private SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Initialize SSB application status
     */
    public function initializeSSBApplication(ApplicationState $application): void
    {
        $this->updateStatus(
            $application,
            SSBLoanStatus::SUBMITTED,
            'Application submitted to SSB loan workflow'
        );

        // Automatically move to awaiting approval
        $this->updateStatus(
            $application,
            SSBLoanStatus::AWAITING_SSB_APPROVAL,
            'Application received, awaiting SSB approval check'
        );

        $this->sendStatusNotification($application);
    }

    /**
     * Update application status
     */
    public function updateStatus(
        ApplicationState $application,
        SSBLoanStatus $newStatus,
        string $notes = '',
        array $additionalData = []
    ): bool {
        $metadata = $application->metadata ?? [];
        $currentStatus = isset($metadata['ssb_status'])
            ? SSBLoanStatus::from($metadata['ssb_status'])
            : null;

        // Validate transition
        if ($currentStatus && !$this->canTransition($currentStatus, $newStatus)) {
            Log::warning('Invalid SSB status transition attempted', [
                'application_id' => $application->id,
                'from' => $currentStatus->value,
                'to' => $newStatus->value,
            ]);
            return false;
        }

        // Update status
        $metadata['ssb_status'] = $newStatus->value;
        $metadata['ssb_status_updated_at'] = now()->toISOString();
        $metadata['ssb_status_message'] = $newStatus->getMessage();

        // Add status history
        if (!isset($metadata['ssb_status_history'])) {
            $metadata['ssb_status_history'] = [];
        }

        $metadata['ssb_status_history'][] = [
            'status' => $newStatus->value,
            'message' => $newStatus->getMessage(),
            'notes' => $notes,
            'data' => $additionalData,
            'timestamp' => now()->toISOString(),
        ];

        // Merge additional data
        if (!empty($additionalData)) {
            $metadata['ssb_data'] = array_merge($metadata['ssb_data'] ?? [], $additionalData);
        }

        $application->update(['metadata' => $metadata]);

        Log::info('SSB status updated', [
            'application_id' => $application->id,
            'session_id' => $application->session_id,
            'from' => $currentStatus?->value,
            'to' => $newStatus->value,
        ]);

        return true;
    }

    /**
     * Process SSB CSV response
     */
    public function processSSBResponse(ApplicationState $application, array $ssbResponse): bool
    {
        try {
            DB::beginTransaction();

            $responseType = strtolower($ssbResponse['response_type'] ?? '');
            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];

            switch ($responseType) {
                case 'approved':
                    $this->handleApproved($application);
                    break;

                case 'insufficient_salary':
                    $this->handleInsufficientSalary($application, $ssbResponse);
                    break;

                case 'invalid_id':
                    $this->handleInvalidID($application, $ssbResponse);
                    break;

                case 'contract_expiring':
                    $this->handleContractExpiring($application, $ssbResponse);
                    break;

                case 'rejected':
                    $this->handleRejected($application, $ssbResponse);
                    break;

                default:
                    Log::warning('Unknown SSB response type', [
                        'application_id' => $application->id,
                        'response_type' => $responseType,
                    ]);
                    DB::rollBack();
                    return false;
            }

            DB::commit();

            // Send notification to client
            $this->sendStatusNotification($application);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process SSB response', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle approved response
     */
    private function handleApproved(ApplicationState $application): void
    {
        $this->updateStatus(
            $application,
            SSBLoanStatus::SSB_APPROVED,
            'Application approved by SSB',
            ['approved_at' => now()->toISOString()]
        );
    }

    /**
     * Handle insufficient salary response
     */
    private function handleInsufficientSalary(ApplicationState $application, array $ssbResponse): void
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        $currentPeriod = (int)($formResponses['loanPeriod'] ?? 12);
        $loanAmount = (float)($formResponses['loanAmount'] ?? 0);

        // Admin must provide recommended period - no automatic calculation
        $recommendedPeriod = (int)($ssbResponse['recommended_period'] ?? 0);

        if ($recommendedPeriod <= 0) {
            throw new \Exception('Recommended period must be provided by admin for insufficient salary response');
        }

        $this->updateStatus(
            $application,
            SSBLoanStatus::INSUFFICIENT_SALARY,
            'Insufficient salary for chosen loan period',
            [
                'current_period' => $currentPeriod,
                'recommended_period' => $recommendedPeriod,
                'current_installment' => $this->calculateInstallment($loanAmount, $currentPeriod),
                'recommended_installment' => $this->calculateInstallment($loanAmount, $recommendedPeriod),
                'salary' => $ssbResponse['salary'] ?? null,
            ]
        );
    }

    /**
     * Handle invalid ID response
     */
    private function handleInvalidID(ApplicationState $application, array $ssbResponse): void
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        $this->updateStatus(
            $application,
            SSBLoanStatus::INVALID_ID_NUMBER,
            'Invalid ID number provided',
            [
                'submitted_id' => $formResponses['idNumber'] ?? null,
                'error_message' => $ssbResponse['error_message'] ?? 'Please provide correct ID number',
            ]
        );
    }

    /**
     * Handle contract expiring response
     */
    private function handleContractExpiring(ApplicationState $application, array $ssbResponse): void
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        $contractExpiryDate = $ssbResponse['contract_expiry_date'] ?? null;
        $loanPeriod = (int)($formResponses['loanPeriod'] ?? 12);
        $loanAmount = (float)($formResponses['loanAmount'] ?? 0);

        // Admin must provide recommended period based on contract expiry
        $recommendedPeriod = (int)($ssbResponse['recommended_period'] ?? 0);

        if (!$contractExpiryDate) {
            throw new \Exception('Contract expiry date must be provided for contract_expiring response');
        }

        if ($recommendedPeriod <= 0) {
            throw new \Exception('Recommended period must be provided by admin for contract_expiring response');
        }

        $this->updateStatus(
            $application,
            SSBLoanStatus::CONTRACT_EXPIRING_ISSUE,
            'Employment contract expires before loan completion',
            [
                'contract_expiry_date' => $contractExpiryDate,
                'requested_period' => $loanPeriod,
                'recommended_period' => $recommendedPeriod,
                'adjusted_installment' => $this->calculateInstallment($loanAmount, $recommendedPeriod),
            ]
        );
    }

    /**
     * Handle rejected response
     */
    private function handleRejected(ApplicationState $application, array $ssbResponse): void
    {
        $this->updateStatus(
            $application,
            SSBLoanStatus::REJECTED,
            'Application rejected by SSB',
            [
                'rejection_reason' => $ssbResponse['reason'] ?? 'No reason provided',
                'rejected_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Adjust loan period
     */
    public function adjustLoanPeriod(ApplicationState $application, int $newPeriod, string $adjustmentType = 'salary'): bool
    {
        $metadata = $application->metadata ?? [];
        $currentStatus = isset($metadata['ssb_status'])
            ? SSBLoanStatus::from($metadata['ssb_status'])
            : null;

        if (!$currentStatus) {
            return false;
        }

        // Update form data with new period
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $oldPeriod = $formResponses['loanPeriod'] ?? null;
        $formResponses['loanPeriod'] = $newPeriod;
        $formData['formResponses'] = $formResponses;

        $application->update(['form_data' => $formData]);

        // Update status based on adjustment type
        $newStatus = match($adjustmentType) {
            'salary' => SSBLoanStatus::PERIOD_ADJUSTED_RESUBMITTED,
            'contract' => SSBLoanStatus::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED,
            default => SSBLoanStatus::PERIOD_ADJUSTED_RESUBMITTED,
        };

        $this->updateStatus(
            $application,
            $newStatus,
            "Loan period adjusted from $oldPeriod to $newPeriod months",
            [
                'old_period' => $oldPeriod,
                'new_period' => $newPeriod,
                'adjustment_type' => $adjustmentType,
                'resubmitted_at' => now()->toISOString(),
            ]
        );

        $this->sendStatusNotification($application);

        return true;
    }

    /**
     * Update ID number
     */
    public function updateIDNumber(ApplicationState $application, string $newIDNumber): bool
    {
        $metadata = $application->metadata ?? [];
        $currentStatus = isset($metadata['ssb_status'])
            ? SSBLoanStatus::from($metadata['ssb_status'])
            : null;

        if ($currentStatus !== SSBLoanStatus::INVALID_ID_NUMBER) {
            return false;
        }

        // Update form data with new ID
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $oldID = $formResponses['idNumber'] ?? null;
        $formResponses['idNumber'] = $newIDNumber;
        $formData['formResponses'] = $formResponses;

        $application->update(['form_data' => $formData]);

        $this->updateStatus(
            $application,
            SSBLoanStatus::ID_CORRECTED_RESUBMITTED,
            'ID number corrected and resubmitted',
            [
                'old_id' => substr($oldID, 0, 3) . '****' . substr($oldID, -2), // Masked for security
                'resubmitted_at' => now()->toISOString(),
            ]
        );

        $this->sendStatusNotification($application);

        return true;
    }

    /**
     * Decline adjustment and cancel application
     */
    public function declineAdjustmentAndCancel(ApplicationState $application): bool
    {
        $this->updateStatus(
            $application,
            SSBLoanStatus::CANCELLED,
            'Application cancelled by user - adjustment declined',
            ['cancelled_at' => now()->toISOString()]
        );

        $this->sendStatusNotification($application);

        return true;
    }

    /**
     * Get current application status
     */
    public function getCurrentStatus(ApplicationState $application): ?SSBLoanStatus
    {
        $metadata = $application->metadata ?? [];

        if (!isset($metadata['ssb_status'])) {
            return null;
        }

        return SSBLoanStatus::from($metadata['ssb_status']);
    }

    /**
     * Get status details for client
     */
    public function getStatusDetailsForClient(ApplicationState $application): array
    {
        $metadata = $application->metadata ?? [];
        $status = $this->getCurrentStatus($application);

        if (!$status) {
            return [
                'status' => null,
                'message' => 'Application not found in SSB workflow',
                'requires_action' => false,
            ];
        }

        $ssbData = $metadata['ssb_data'] ?? [];
        $details = [
            'status' => $status->value,
            'message' => $status->getMessage(),
            'requires_action' => $status->requiresUserAction(),
            'is_final' => $status->isFinalState(),
            'updated_at' => $metadata['ssb_status_updated_at'] ?? null,
        ];

        // Add action-specific data
        switch ($status) {
            case SSBLoanStatus::INSUFFICIENT_SALARY:
                $details['action_required'] = [
                    'type' => 'period_adjustment',
                    'question' => 'Do you want to apply for a longer period so the installment reduces?',
                    'current_period' => $ssbData['current_period'] ?? null,
                    'recommended_period' => $ssbData['recommended_period'] ?? null,
                    'current_installment' => $ssbData['current_installment'] ?? null,
                    'recommended_installment' => $ssbData['recommended_installment'] ?? null,
                ];
                break;

            case SSBLoanStatus::INVALID_ID_NUMBER:
                $details['action_required'] = [
                    'type' => 'id_correction',
                    'question' => 'Please provide the correct ID number',
                    'error_message' => $ssbData['error_message'] ?? 'Invalid ID number',
                ];
                break;

            case SSBLoanStatus::CONTRACT_EXPIRING_ISSUE:
                $details['action_required'] = [
                    'type' => 'contract_period_adjustment',
                    'question' => 'Your employment contract expires before loan completion. Adjust loan period?',
                    'contract_expiry_date' => $ssbData['contract_expiry_date'] ?? null,
                    'requested_period' => $ssbData['requested_period'] ?? null,
                    'recommended_period' => $ssbData['recommended_period'] ?? null,
                    'adjusted_installment' => $ssbData['adjusted_installment'] ?? null,
                ];
                break;
        }

        return $details;
    }

    /**
     * Parse SSB CSV file and update applications
     */
    public function parseAndProcessSSBCSV(string $filePath): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath)) {
            $results['errors'][] = 'CSV file not found';
            return $results;
        }

        try {
            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file);

            // Expected headers: reference_code, response_type, recommended_period, salary, contract_expiry_date, error_message, reason
            $headerMap = array_flip($headers);

            while (($row = fgetcsv($file)) !== false) {
                try {
                    $referenceCode = $row[$headerMap['reference_code'] ?? 0] ?? null;

                    if (!$referenceCode) {
                        $results['failed']++;
                        $results['errors'][] = "Missing reference code in row";
                        continue;
                    }

                    $application = ApplicationState::where('reference_code', $referenceCode)->first();

                    if (!$application) {
                        $results['failed']++;
                        $results['errors'][] = "Application not found for reference: $referenceCode";
                        continue;
                    }

                    $responseType = $row[$headerMap['response_type'] ?? 1] ?? 'rejected';

                    $ssbResponse = [
                        'response_type' => $responseType,
                        'recommended_period' => $row[$headerMap['recommended_period'] ?? 2] ?? null,
                        'salary' => $row[$headerMap['salary'] ?? 3] ?? null,
                        'contract_expiry_date' => $row[$headerMap['contract_expiry_date'] ?? 4] ?? null,
                        'error_message' => $row[$headerMap['error_message'] ?? 5] ?? null,
                        'reason' => $row[$headerMap['reason'] ?? 6] ?? null,
                    ];

                    // Validate required fields for specific response types
                    if ($responseType === 'insufficient_salary' && empty($ssbResponse['recommended_period'])) {
                        $results['failed']++;
                        $results['errors'][] = "Missing recommended_period for insufficient_salary: $referenceCode";
                        continue;
                    }

                    if ($responseType === 'contract_expiring' && (empty($ssbResponse['contract_expiry_date']) || empty($ssbResponse['recommended_period']))) {
                        $results['failed']++;
                        $results['errors'][] = "Missing contract_expiry_date or recommended_period for contract_expiring: $referenceCode";
                        continue;
                    }

                    if ($this->processSSBResponse($application, $ssbResponse)) {
                        $results['processed']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to process reference: $referenceCode";
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error processing row for $referenceCode: " . $e->getMessage();
                }
            }

            fclose($file);

        } catch (\Exception $e) {
            $results['errors'][] = "CSV parsing error: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Process SSB API response (for future API integration)
     */
    public function processSSBAPIResponse(string $referenceCode, array $apiResponse): bool
    {
        $application = ApplicationState::where('reference_code', $referenceCode)->first();

        if (!$application) {
            Log::warning('Application not found for SSB API response', [
                'reference_code' => $referenceCode,
            ]);
            return false;
        }

        return $this->processSSBResponse($application, $apiResponse);
    }

    /**
     * Send status notification to client
     */
    private function sendStatusNotification(ApplicationState $application): void
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $mobile = $formResponses['mobile'] ?? null;

        if (!$mobile) {
            return;
        }

        $metadata = $application->metadata ?? [];
        $message = $metadata['ssb_status_message'] ?? 'Your loan application status has been updated.';
        $referenceCode = $application->reference_code;

        $fullMessage = "Loan Application Update\n";
        $fullMessage .= "Reference: $referenceCode\n";
        $fullMessage .= "$message\n";
        $fullMessage .= "Check status: [Your Portal URL]";

        try {
            $this->smsService->sendSMS($mobile, $fullMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send SSB status SMS', [
                'application_id' => $application->id,
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if status transition is valid
     */
    private function canTransition(SSBLoanStatus $from, SSBLoanStatus $to): bool
    {
        return in_array($to, $from->getAllowedTransitions());
    }

    /**
     * Calculate recommended period based on salary
     */
    private function calculateRecommendedPeriod(float $loanAmount, array $ssbResponse): int
    {
        $salary = (float)($ssbResponse['salary'] ?? 0);

        if ($salary <= 0) {
            return 24; // Default to 24 months if salary not provided
        }

        // Assume max 30% of salary can go to loan repayment
        $maxInstallment = $salary * 0.30;

        // Calculate period needed (assuming 10% annual interest)
        $monthlyRate = 0.10 / 12;
        $period = ceil(log($maxInstallment / ($maxInstallment - $loanAmount * $monthlyRate)) / log(1 + $monthlyRate));

        // Round up to nearest multiple of 6, max 60 months
        $period = min(60, ceil($period / 6) * 6);

        return max(6, (int)$period);
    }

    /**
     * Calculate loan installment
     */
    private function calculateInstallment(float $loanAmount, int $period): float
    {
        if ($period <= 0) {
            return 0;
        }

        // Simple interest calculation (10% annual rate)
        $monthlyRate = 0.10 / 12;
        $installment = $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $period)) / (pow(1 + $monthlyRate, $period) - 1);

        return round($installment, 2);
    }

    /**
     * Calculate maximum period from contract expiry
     */
    private function calculateMaxPeriodFromContractExpiry(?string $contractExpiryDate): int
    {
        if (!$contractExpiryDate) {
            return 12;
        }

        try {
            $expiryDate = Carbon::parse($contractExpiryDate);
            $monthsUntilExpiry = Carbon::now()->diffInMonths($expiryDate);

            // One month buffer before contract expiry
            return max(1, $monthsUntilExpiry - 1);

        } catch (\Exception $e) {
            return 12;
        }
    }
}
