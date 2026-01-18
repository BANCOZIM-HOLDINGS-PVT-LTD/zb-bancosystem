<?php

namespace App\Services;

use App\Enums\ZBLoanStatus;
use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZBStatusService
{
    private SMSService $smsService;
    private EcocashPaymentService $ecocashService;

    public function __construct(SMSService $smsService, EcocashPaymentService $ecocashService)
    {
        $this->smsService = $smsService;
        $this->ecocashService = $ecocashService;
    }

    /**
     * Initialize ZB application workflow
     */
    public function initializeZBApplication(ApplicationState $application): void
    {
        $this->updateStatus(
            $application,
            ZBLoanStatus::SUBMITTED,
            'Application submitted to ZB loan workflow'
        );

        // Automatically move to awaiting credit check
        $this->updateStatus(
            $application,
            ZBLoanStatus::AWAITING_CREDIT_CHECK,
            'Application received, awaiting credit check rating'
        );

        $this->sendStatusNotification($application);
    }

    /**
     * Update application status
     */
    public function updateStatus(
        ApplicationState $application,
        ZBLoanStatus $newStatus,
        string $notes = '',
        array $additionalData = []
    ): bool {
        $metadata = $application->metadata ?? [];
        $currentStatus = isset($metadata['zb_status'])
            ? ZBLoanStatus::from($metadata['zb_status'])
            : null;

        // Validate transition
        if ($currentStatus && !$this->canTransition($currentStatus, $newStatus)) {
            Log::warning('Invalid ZB status transition attempted', [
                'application_id' => $application->id,
                'from' => $currentStatus->value,
                'to' => $newStatus->value,
            ]);
            return false;
        }

        // Update status
        $metadata['zb_status'] = $newStatus->value;
        $metadata['zb_status_updated_at'] = now()->toISOString();
        $metadata['zb_status_message'] = $newStatus->getMessage();

        // Add status history
        if (!isset($metadata['zb_status_history'])) {
            $metadata['zb_status_history'] = [];
        }

        $metadata['zb_status_history'][] = [
            'status' => $newStatus->value,
            'message' => $newStatus->getMessage(),
            'notes' => $notes,
            'data' => $additionalData,
            'timestamp' => now()->toISOString(),
        ];

        // Merge additional data
        if (!empty($additionalData)) {
            $metadata['zb_data'] = array_merge($metadata['zb_data'] ?? [], $additionalData);
        }

        $application->update(['metadata' => $metadata]);

        Log::info('ZB status updated', [
            'application_id' => $application->id,
            'session_id' => $application->session_id,
            'from' => $currentStatus?->value,
            'to' => $newStatus->value,
        ]);

        return true;
    }

    /**
     * Process credit check good response
     */
    public function processCreditCheckGood(ApplicationState $application, string $adminNotes = ''): bool
    {
        try {
            DB::beginTransaction();

            $this->updateStatus(
                $application,
                ZBLoanStatus::CREDIT_CHECK_GOOD_APPROVED,
                $adminNotes ?: 'Credit check rating: Good - Approved by admin',
                ['credit_rating' => 'good', 'approved_at' => now()->toISOString()]
            );

            DB::commit();
            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process credit check good', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process credit check poor response
     */
    public function processCreditCheckPoor(ApplicationState $application, string $adminNotes = ''): bool
    {
        try {
            DB::beginTransaction();

            // First mark as rejected due to poor credit
            $this->updateStatus(
                $application,
                ZBLoanStatus::CREDIT_CHECK_POOR_REJECTED,
                $adminNotes ?: 'Credit check rating: Poor - Rejected by admin',
                ['credit_rating' => 'poor', 'rejected_at' => now()->toISOString()]
            );

            // Then offer blacklist report option
            $this->updateStatus(
                $application,
                ZBLoanStatus::AWAITING_BLACKLIST_REPORT_DECISION,
                'Offering blacklist report to client'
            );

            DB::commit();
            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process credit check poor', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process salary not regular rejection
     */
    public function processSalaryNotRegular(ApplicationState $application, string $adminNotes = ''): bool
    {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::SALARY_NOT_REGULAR_REJECTED,
                $adminNotes ?: 'Salary not being deposited regularly - Rejected by admin',
                ['rejection_reason' => 'irregular_salary', 'rejected_at' => now()->toISOString()]
            );

            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process salary not regular', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process insufficient salary rejection
     */
    public function processInsufficientSalary(
        ApplicationState $application,
        int $recommendedPeriod,
        string $adminNotes = ''
    ): bool {
        try {
            DB::beginTransaction();

            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            $currentPeriod = (int)($formResponses['loanPeriod'] ?? 12);
            $loanAmount = (float)($formResponses['loanAmount'] ?? 0);

            // Mark as rejected due to insufficient salary
            $this->updateStatus(
                $application,
                ZBLoanStatus::INSUFFICIENT_SALARY_REJECTED,
                $adminNotes ?: 'Insufficient salary - Rejected by admin',
                [
                    'rejection_reason' => 'insufficient_salary',
                    'current_period' => $currentPeriod,
                    'recommended_period' => $recommendedPeriod,
                    'current_installment' => $this->calculateInstallment($loanAmount, $currentPeriod),
                    'recommended_installment' => $this->calculateInstallment($loanAmount, $recommendedPeriod),
                    'rejected_at' => now()->toISOString(),
                ]
            );

            // Offer period adjustment option
            $this->updateStatus(
                $application,
                ZBLoanStatus::AWAITING_PERIOD_ADJUSTMENT_DECISION,
                'Offering period adjustment to client'
            );

            DB::commit();
            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process insufficient salary', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process approved response
     */
    public function processApproved(ApplicationState $application, string $adminNotes = ''): bool
    {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::APPROVED_AWAITING_DELIVERY,
                $adminNotes ?: 'Approved by ZB - Awaiting delivery',
                ['approved_at' => now()->toISOString()]
            );

            // Create Delivery Tracking Record
            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            $deliverySelection = $formData['deliverySelection'] ?? [];

            // Determine depot
            $depot = '';
            if (!empty($deliverySelection['city'])) {
                $depot = $deliverySelection['city'] . ' (' . ($deliverySelection['agent'] ?? 'Zim Post Office') . ')';
            } elseif (!empty($deliverySelection['depot'])) {
                $depot = $deliverySelection['depot'];
            }

            \App\Models\DeliveryTracking::create([
                'application_state_id' => $application->id,
                'status' => 'pending',
                'recipient_name' => trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['surname'] ?? '')),
                'recipient_phone' => $formResponses['mobile'] ?? $formResponses['cellNumber'] ?? null,
                'client_national_id' => $formResponses['nationalIdNumber'] ?? $formResponses['idNumber'] ?? null,
                'product_type' => $formData['business'] ?? $formData['category'] ?? 'N/A',
                'courier_type' => $deliverySelection['agent'] ?? 'Zim Post Office',
                'delivery_depot' => $depot,
                'delivery_address' => $depot, // Use depot as address for now
            ]);

            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process approved', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Client declines blacklist report
     */
    public function declineBlacklistReport(ApplicationState $application): bool
    {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::BLACKLIST_REPORT_DECLINED,
                'Client declined blacklist report'
            );

            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to decline blacklist report', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Client requests blacklist report (initiates payment)
     */
    public function requestBlacklistReport(ApplicationState $application): array
    {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::AWAITING_BLACKLIST_REPORT_PAYMENT,
                'Client requested blacklist report - awaiting payment',
                ['report_fee' => 5.00, 'request_date' => now()->toISOString()]
            );

            $this->sendStatusNotification($application);

            // Initiate Ecocash payment
            $paymentResult = $this->ecocashService->initiateBlacklistReportPayment($application);

            if ($paymentResult['success']) {
                Log::info('Blacklist report payment initiated', [
                    'reference' => $application->reference_code,
                    'transaction_ref' => $paymentResult['transaction_reference'] ?? null,
                ]);

                return [
                    'success' => true,
                    'payment_required' => true,
                    'payment_initiated' => true,
                    'transaction_reference' => $paymentResult['transaction_reference'] ?? null,
                    'amount' => 5.00,
                    'currency' => 'USD',
                    'mobile' => $paymentResult['mobile'] ?? null,
                    'message' => $paymentResult['message'] ?? 'Payment initiated. Check your phone for Ecocash prompt.',
                    'test_mode' => $paymentResult['test_mode'] ?? false,
                ];
            } else {
                Log::warning('Blacklist report payment initiation failed', [
                    'reference' => $application->reference_code,
                    'error' => $paymentResult['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $paymentResult['error'] ?? 'Payment initiation failed',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to request blacklist report', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process request',
            ];
        }
    }

    /**
     * Process blacklist report payment
     */
    public function processBlacklistReportPayment(
        ApplicationState $application,
        string $paymentReference,
        array $blacklistData = []
    ): bool {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::BLACKLIST_REPORT_PAID,
                'Blacklist report payment received',
                [
                    'payment_reference' => $paymentReference,
                    'payment_date' => now()->toISOString(),
                    'blacklist_institutions' => $blacklistData,
                ]
            );

            $this->sendStatusNotification($application);
            $this->sendBlacklistReport($application, $blacklistData);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process blacklist payment', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Client declines period adjustment
     */
    public function declinePeriodAdjustment(ApplicationState $application): bool
    {
        try {
            $this->updateStatus(
                $application,
                ZBLoanStatus::PERIOD_ADJUSTMENT_DECLINED,
                'Client declined period adjustment'
            );

            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to decline period adjustment', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Client accepts period adjustment
     */
    public function acceptPeriodAdjustment(ApplicationState $application): bool
    {
        try {
            $metadata = $application->metadata ?? [];
            $zbData = $metadata['zb_data'] ?? [];
            $recommendedPeriod = (int)($zbData['recommended_period'] ?? 0);

            if ($recommendedPeriod <= 0) {
                throw new \Exception('No recommended period found');
            }

            // Update form data with new period
            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            $oldPeriod = $formResponses['loanPeriod'] ?? null;
            $formResponses['loanPeriod'] = $recommendedPeriod;
            $formData['formResponses'] = $formResponses;

            $application->update(['form_data' => $formData]);

            $this->updateStatus(
                $application,
                ZBLoanStatus::PERIOD_ADJUSTED_RESUBMITTED,
                "Period adjusted from $oldPeriod to $recommendedPeriod months - Resubmitted",
                [
                    'old_period' => $oldPeriod,
                    'new_period' => $recommendedPeriod,
                    'resubmitted_at' => now()->toISOString(),
                ]
            );

            $this->sendStatusNotification($application);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to accept period adjustment', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get current application status
     */
    public function getCurrentStatus(ApplicationState $application): ?ZBLoanStatus
    {
        $metadata = $application->metadata ?? [];

        if (!isset($metadata['zb_status'])) {
            return null;
        }

        return ZBLoanStatus::from($metadata['zb_status']);
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
                'message' => 'Application not found in ZB workflow',
                'requires_action' => false,
            ];
        }

        $zbData = $metadata['zb_data'] ?? [];
        $details = [
            'status' => $status->value,
            'message' => $status->getMessage(),
            'requires_action' => $status->requiresUserAction(),
            'is_final' => $status->isFinalState(),
            'allows_delivery_tracking' => $status->allowsDeliveryTracking(),
            'updated_at' => $metadata['zb_status_updated_at'] ?? null,
        ];

        // Add action-specific data
        switch ($status) {
            case ZBLoanStatus::AWAITING_BLACKLIST_REPORT_DECISION:
                $details['action_required'] = [
                    'type' => 'blacklist_report_decision',
                    'question' => 'Do you want to see which institution blacklisted you?',
                    'options' => [
                        ['value' => 'yes', 'label' => 'Yes - Pay $5 for report'],
                        ['value' => 'no', 'label' => 'No - Exit'],
                    ],
                ];
                break;

            case ZBLoanStatus::AWAITING_BLACKLIST_REPORT_PAYMENT:
                $details['action_required'] = [
                    'type' => 'payment',
                    'amount' => 5.00,
                    'currency' => 'USD',
                    'description' => 'Blacklist report search fee',
                ];
                break;

            case ZBLoanStatus::AWAITING_PERIOD_ADJUSTMENT_DECISION:
                $details['action_required'] = [
                    'type' => 'period_adjustment_decision',
                    'question' => 'Do you want to apply for a longer period so the installment reduces?',
                    'current_period' => $zbData['current_period'] ?? null,
                    'recommended_period' => $zbData['recommended_period'] ?? null,
                    'current_installment' => $zbData['current_installment'] ?? null,
                    'recommended_installment' => $zbData['recommended_installment'] ?? null,
                    'options' => [
                        ['value' => 'yes', 'label' => 'Yes - Resubmit with adjusted period'],
                        ['value' => 'no', 'label' => 'No - Exit'],
                    ],
                ];
                break;

            case ZBLoanStatus::BLACKLIST_REPORT_PAID:
                $details['blacklist_report'] = [
                    'institutions' => $zbData['blacklist_institutions'] ?? [],
                    'payment_reference' => $zbData['payment_reference'] ?? null,
                ];
                break;
        }

        return $details;
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
        $message = $metadata['zb_status_message'] ?? 'Your loan application status has been updated.';
        $referenceCode = $application->reference_code;

        $fullMessage = "ZB Loan Application Update\n";
        $fullMessage .= "Reference: $referenceCode\n";
        $fullMessage .= "$message\n";
        $fullMessage .= "Check status: [Your Portal URL]";

        try {
            $this->smsService->sendSMS($mobile, $fullMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send ZB status SMS', [
                'application_id' => $application->id,
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send blacklist report to client via SMS only
     */
    private function sendBlacklistReport(ApplicationState $application, array $blacklistData): void
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $mobile = $formResponses['mobile'] ?? null;

        if (!$mobile) {
            Log::warning('Cannot send blacklist report - no mobile number', [
                'application_id' => $application->id,
                'reference' => $application->reference_code,
            ]);
            return;
        }

        $referenceCode = $application->reference_code;
        $institutions = !empty($blacklistData) ? implode(', ', $blacklistData) : 'Multiple institutions';

        // Send blacklist report via SMS
        $message = "ZB BLACKLIST REPORT\n";
        $message .= "Ref: $referenceCode\n\n";
        $message .= "You are blacklisted by:\n";
        $message .= "$institutions\n\n";
        $message .= "Please contact these institutions to resolve outstanding issues before reapplying.\n";
        $message .= "Thank you.";

        try {
            $sent = $this->smsService->sendSMS($mobile, $message);

            if ($sent) {
                Log::info('Blacklist report sent successfully via SMS', [
                    'application_id' => $application->id,
                    'reference' => $referenceCode,
                    'mobile' => $mobile,
                    'institutions' => $institutions,
                ]);
            } else {
                Log::error('Failed to send blacklist report SMS', [
                    'application_id' => $application->id,
                    'reference' => $referenceCode,
                    'mobile' => $mobile,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exception sending blacklist report', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if status transition is valid
     */
    private function canTransition(ZBLoanStatus $from, ZBLoanStatus $to): bool
    {
        return in_array($to, $from->getAllowedTransitions());
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
}
