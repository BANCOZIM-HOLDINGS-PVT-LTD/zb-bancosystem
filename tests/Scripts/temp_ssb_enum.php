<?php

namespace App\Enums;

enum SSBLoanStatus: string
{
    case SUBMITTED = 'submitted';
    case AWAITING_SSB_APPROVAL = 'awaiting_ssb_approval';
    case SSB_APPROVED = 'ssb_approved';
    case INSUFFICIENT_SALARY = 'insufficient_salary';
    case AWAITING_PERIOD_ADJUSTMENT = 'awaiting_period_adjustment';
    case PERIOD_ADJUSTED_RESUBMITTED = 'period_adjusted_resubmitted';
    case INVALID_ID_NUMBER = 'invalid_id_number';
    case AWAITING_ID_CORRECTION = 'awaiting_id_correction';
    case ID_CORRECTED_RESUBMITTED = 'id_corrected_resubmitted';
    case CONTRACT_EXPIRING_ISSUE = 'contract_expiring_issue';
    case AWAITING_CONTRACT_PERIOD_ADJUSTMENT = 'awaiting_contract_period_adjustment';
    case CONTRACT_PERIOD_ADJUSTED_RESUBMITTED = 'contract_period_adjusted_resubmitted';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';

    /**
     * Get user-friendly status message
     */
    public function getMessage(): string
    {
        return match($this) {
            self::SUBMITTED => 'Application submitted successfully',
            self::AWAITING_SSB_APPROVAL => 'Application received successfully, awaiting SSB approval check within 24 hours',
            self::SSB_APPROVED => 'SSB response received - Approved',
            self::INSUFFICIENT_SALARY => 'SSB response received - Insufficient salary for the product chosen',
            self::AWAITING_PERIOD_ADJUSTMENT => 'Awaiting your decision on loan period adjustment',
            self::PERIOD_ADJUSTED_RESUBMITTED => 'Application resubmitted to SSB with adjusted period, awaiting approval',
            self::INVALID_ID_NUMBER => 'SSB response received - Invalid ID number',
            self::AWAITING_ID_CORRECTION => 'Awaiting ID number correction',
            self::ID_CORRECTED_RESUBMITTED => 'Application resubmitted to SSB with corrected ID, awaiting approval',
            self::CONTRACT_EXPIRING_ISSUE => 'Employment contract expiry date affects loan period',
            self::AWAITING_CONTRACT_PERIOD_ADJUSTMENT => 'Awaiting your decision on contract period adjustment',
            self::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED => 'Application resubmitted to SSB with adjusted period, awaiting approval',
            self::REJECTED => 'Application rejected',
            self::APPROVED => 'Application approved',
            self::CANCELLED => 'Application cancelled',
        };
    }

    /**
     * Check if status requires user action
     */
    public function requiresUserAction(): bool
    {
        return in_array($this, [
            self::INSUFFICIENT_SALARY,
            self::AWAITING_PERIOD_ADJUSTMENT,
            self::INVALID_ID_NUMBER,
            self::AWAITING_ID_CORRECTION,
            self::CONTRACT_EXPIRING_ISSUE,
            self::AWAITING_CONTRACT_PERIOD_ADJUSTMENT,
        ]);
    }

    /**
     * Check if application is in final state
     */
    public function isFinalState(): bool
    {
        return in_array($this, [
            self::SSB_APPROVED,
            self::REJECTED,
            self::APPROVED,
            self::CANCELLED,
        ]);
    }

    /**
     * Get next allowed statuses
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::SUBMITTED => [self::AWAITING_SSB_APPROVAL],
            self::AWAITING_SSB_APPROVAL => [
                self::SSB_APPROVED,
                self::INSUFFICIENT_SALARY,
                self::INVALID_ID_NUMBER,
                self::CONTRACT_EXPIRING_ISSUE,
                self::REJECTED,
            ],
            self::INSUFFICIENT_SALARY => [self::AWAITING_PERIOD_ADJUSTMENT, self::CANCELLED],
            self::AWAITING_PERIOD_ADJUSTMENT => [self::PERIOD_ADJUSTED_RESUBMITTED, self::CANCELLED],
            self::PERIOD_ADJUSTED_RESUBMITTED => [
                self::SSB_APPROVED,
                self::INVALID_ID_NUMBER,
                self::CONTRACT_EXPIRING_ISSUE,
                self::REJECTED,
            ],
            self::INVALID_ID_NUMBER => [self::AWAITING_ID_CORRECTION, self::CANCELLED],
            self::AWAITING_ID_CORRECTION => [self::ID_CORRECTED_RESUBMITTED, self::CANCELLED],
            self::ID_CORRECTED_RESUBMITTED => [
                self::SSB_APPROVED,
                self::INSUFFICIENT_SALARY,
                self::CONTRACT_EXPIRING_ISSUE,
                self::REJECTED,
            ],
            self::CONTRACT_EXPIRING_ISSUE => [self::AWAITING_CONTRACT_PERIOD_ADJUSTMENT, self::CANCELLED],
            self::AWAITING_CONTRACT_PERIOD_ADJUSTMENT => [self::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED, self::CANCELLED],
            self::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED => [
                self::SSB_APPROVED,
                self::INVALID_ID_NUMBER,
                self::INSUFFICIENT_SALARY,
                self::REJECTED,
            ],
            self::SSB_APPROVED => [self::APPROVED],
            default => [],
        };
    }
}
