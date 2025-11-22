<?php

namespace App\Enums;

enum ZBLoanStatus: string
{
    case SUBMITTED = 'submitted';
    case AWAITING_CREDIT_CHECK = 'awaiting_credit_check';
    case CREDIT_CHECK_GOOD_APPROVED = 'credit_check_good_approved';
    case CREDIT_CHECK_POOR_REJECTED = 'credit_check_poor_rejected';
    case AWAITING_BLACKLIST_REPORT_DECISION = 'awaiting_blacklist_report_decision';
    case BLACKLIST_REPORT_DECLINED = 'blacklist_report_declined';
    case AWAITING_BLACKLIST_REPORT_PAYMENT = 'awaiting_blacklist_report_payment';
    case BLACKLIST_REPORT_PAID = 'blacklist_report_paid';
    case SALARY_NOT_REGULAR_REJECTED = 'salary_not_regular_rejected';
    case INSUFFICIENT_SALARY_REJECTED = 'insufficient_salary_rejected';
    case AWAITING_PERIOD_ADJUSTMENT_DECISION = 'awaiting_period_adjustment_decision';
    case PERIOD_ADJUSTMENT_DECLINED = 'period_adjustment_declined';
    case PERIOD_ADJUSTED_RESUBMITTED = 'period_adjusted_resubmitted';
    case APPROVED_AWAITING_DELIVERY = 'approved_awaiting_delivery';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * Get user-friendly status message
     */
    public function getMessage(): string
    {
        return match($this) {
            self::SUBMITTED => 'Application submitted successfully',
            self::AWAITING_CREDIT_CHECK => 'Application received successfully, awaiting credit check rating',
            self::CREDIT_CHECK_GOOD_APPROVED => 'Credit check rating received - Good - Approved',
            self::CREDIT_CHECK_POOR_REJECTED => 'Credit check rating received - Poor - Rejected',
            self::AWAITING_BLACKLIST_REPORT_DECISION => 'Do you want to see which institution blacklisted you?',
            self::BLACKLIST_REPORT_DECLINED => 'Thank you for your interest. Kindly reapply when circumstances in your credit rating have changed.',
            self::AWAITING_BLACKLIST_REPORT_PAYMENT => 'Blacklist report available. Search fee is $5. Proceed to payment.',
            self::BLACKLIST_REPORT_PAID => 'Blacklist report payment received. Report will be sent shortly.',
            self::SALARY_NOT_REGULAR_REJECTED => 'ZB response received - Salary not being deposited regularly - Rejected',
            self::INSUFFICIENT_SALARY_REJECTED => 'ZB response received - Insufficient salary - Rejected',
            self::AWAITING_PERIOD_ADJUSTMENT_DECISION => 'Do you want to apply for a longer period so the installment reduces?',
            self::PERIOD_ADJUSTMENT_DECLINED => 'Application declined. Thank you for your interest.',
            self::PERIOD_ADJUSTED_RESUBMITTED => 'Application resubmitted with adjusted period. Check again after 24 hours.',
            self::APPROVED_AWAITING_DELIVERY => 'ZB response received - Approved. Track your delivery after 24 hours.',
            self::APPROVED => 'Application approved',
            self::REJECTED => 'Application rejected',
            self::CANCELLED => 'Application cancelled',
        };
    }

    /**
     * Check if status requires user action
     */
    public function requiresUserAction(): bool
    {
        return in_array($this, [
            self::AWAITING_BLACKLIST_REPORT_DECISION,
            self::AWAITING_BLACKLIST_REPORT_PAYMENT,
            self::AWAITING_PERIOD_ADJUSTMENT_DECISION,
        ]);
    }

    /**
     * Check if application is in final state
     */
    public function isFinalState(): bool
    {
        return in_array($this, [
            self::CREDIT_CHECK_GOOD_APPROVED,
            self::BLACKLIST_REPORT_DECLINED,
            self::SALARY_NOT_REGULAR_REJECTED,
            self::PERIOD_ADJUSTMENT_DECLINED,
            self::APPROVED,
            self::REJECTED,
            self::CANCELLED,
        ]);
    }

    /**
     * Get next allowed statuses
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::SUBMITTED => [self::AWAITING_CREDIT_CHECK],
            self::AWAITING_CREDIT_CHECK => [
                self::CREDIT_CHECK_GOOD_APPROVED,
                self::CREDIT_CHECK_POOR_REJECTED,
                self::SALARY_NOT_REGULAR_REJECTED,
                self::INSUFFICIENT_SALARY_REJECTED,
                self::APPROVED_AWAITING_DELIVERY,
            ],
            self::CREDIT_CHECK_POOR_REJECTED => [
                self::AWAITING_BLACKLIST_REPORT_DECISION,
            ],
            self::AWAITING_BLACKLIST_REPORT_DECISION => [
                self::BLACKLIST_REPORT_DECLINED,
                self::AWAITING_BLACKLIST_REPORT_PAYMENT,
            ],
            self::AWAITING_BLACKLIST_REPORT_PAYMENT => [
                self::BLACKLIST_REPORT_PAID,
                self::BLACKLIST_REPORT_DECLINED,
            ],
            self::INSUFFICIENT_SALARY_REJECTED => [
                self::AWAITING_PERIOD_ADJUSTMENT_DECISION,
            ],
            self::AWAITING_PERIOD_ADJUSTMENT_DECISION => [
                self::PERIOD_ADJUSTED_RESUBMITTED,
                self::PERIOD_ADJUSTMENT_DECLINED,
            ],
            self::PERIOD_ADJUSTED_RESUBMITTED => [
                self::CREDIT_CHECK_GOOD_APPROVED,
                self::CREDIT_CHECK_POOR_REJECTED,
                self::SALARY_NOT_REGULAR_REJECTED,
                self::INSUFFICIENT_SALARY_REJECTED,
                self::APPROVED_AWAITING_DELIVERY,
            ],
            self::APPROVED_AWAITING_DELIVERY => [self::APPROVED],
            default => [],
        };
    }

    /**
     * Check if status allows delivery tracking
     */
    public function allowsDeliveryTracking(): bool
    {
        return in_array($this, [
            self::APPROVED_AWAITING_DELIVERY,
            self::APPROVED,
        ]);
    }
}
