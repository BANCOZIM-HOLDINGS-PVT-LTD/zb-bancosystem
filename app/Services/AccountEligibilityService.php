<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\User;

class AccountEligibilityService
{
    /**
     * Check if user is a first-time account applicant
     * Note 26: Exclude account summary for first-time account openers
     */
    public function isFirstTimeApplicant(string $nationalId, ?string $phone = null, ?string $email = null): bool
    {
        // Check by national ID first (most reliable)
        $existingApplications = ApplicationState::query()
            ->where(function ($query) use ($nationalId, $phone, $email) {
                // Check by national ID
                $query->whereJsonContains('form_data->nationalIdNumber', $nationalId)
                    ->orWhereJsonContains('form_data->idNumber', $nationalId);

                // Also check by phone if provided
                if ($phone) {
                    $query->orWhereJsonContains('form_data->mobile', $phone)
                        ->orWhereJsonContains('form_data->cellNumber', $phone);
                }

                // Also check by email if provided
                if ($email) {
                    $query->orWhereJsonContains('form_data->emailAddress', $email)
                        ->orWhereJsonContains('form_data->email', $email);
                }
            })
            ->where('form_type', '!=', 'new_account') // Exclude current application type
            ->exists();

        return ! $existingApplications;
    }

    /**
     * Check if user is eligible for credit facilities
     * Note 26: Can only apply for credit after account is opened and salary deposited
     */
    public function isEligibleForCredit(string $nationalId): bool
    {
        // Check if user has a completed account opening application
        $hasAccount = ApplicationState::query()
            ->where(function ($query) use ($nationalId) {
                $query->whereJsonContains('form_data->nationalIdNumber', $nationalId)
                    ->orWhereJsonContains('form_data->idNumber', $nationalId);
            })
            ->whereIn('form_type', ['zb_account_opening', 'individual_account_opening'])
            ->whereJsonContains('metadata->admin_status', 'approved')
            ->exists();

        if (! $hasAccount) {
            return false;
        }

        // In a real implementation, you would check:
        // 1. If account is actually opened in banking system
        // 2. If salary has been deposited at least once
        // For now, we assume account is ready if approved

        return true;
    }

    /**
     * Get appropriate message for first-time applicants
     * Note 26: Specific message for new account applications
     */
    public function getFirstTimeApplicantMessage(): string
    {
        return 'Thank you for applying for your ZB individual Account. We will inform you of your account number when its open, at which time you will then be able to apply for a credit facility after your salary has been deposited at least once.';
    }

    /**
     * Get appropriate message for existing customers
     * Note 29: Message with application tracking number
     */
    public function getExistingCustomerMessage(string $applicationNumber): string
    {
        return "Thank you for your application. Your application number is {$applicationNumber}. You can use it to track the progress of your application";
    }

    /**
     * Determine appropriate success flow based on application type and customer status
     */
    public function getSuccessFlow(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $nationalId = $formData['nationalIdNumber'] ?? $formData['idNumber'] ?? '';
        $phone = $formData['mobile'] ?? $formData['cellNumber'] ?? '';
        $email = $formData['emailAddress'] ?? $formData['email'] ?? '';

        $isFirstTime = $this->isFirstTimeApplicant($nationalId, $phone, $email);
        $isAccountOpening = in_array($application->form_type, [
            'zb_account_opening',
            'individual_account_opening',
        ]);

        $flow = [
            'is_first_time' => $isFirstTime,
            'is_account_opening' => $isAccountOpening,
            'show_account_summary' => false,
            'message' => '',
            'next_steps' => [],
            'sms_notification' => false,
        ];

        if ($isAccountOpening && $isFirstTime) {
            // First-time account opening
            $flow['message'] = $this->getFirstTimeApplicantMessage();
            $flow['show_account_summary'] = false;
            $flow['sms_notification'] = true;
            $flow['sms_type'] = 'new_account';
            $flow['next_steps'] = [
                'Account verification will be completed within 3-5 business days',
                'You will receive SMS notification when account is ready',
                'Credit facilities will be available after salary deposit',
                'Keep your application number for reference: '.$application->reference_code,
            ];
        } elseif ($isAccountOpening && ! $isFirstTime) {
            // Existing customer opening additional account
            $flow['message'] = $this->getExistingCustomerMessage($application->reference_code);
            $flow['show_account_summary'] = true;
            $flow['sms_notification'] = true;
            $flow['sms_type'] = 'existing_customer';
            $flow['next_steps'] = [
                'Your application is being processed',
                'Estimated completion: 1-2 business days',
                'You can track progress using: '.$application->reference_code,
            ];
        } else {
            // Credit facility or other application
            $flow['message'] = $this->getExistingCustomerMessage($application->reference_code);
            $flow['show_account_summary'] = ! $isFirstTime;
            $flow['sms_notification'] = true;
            $flow['sms_type'] = 'credit_application';
            $flow['next_steps'] = [
                'Application submitted successfully',
                'Processing time: 3-7 business days',
                'Reference number: '.$application->reference_code,
            ];
        }

        return $flow;
    }

    /**
     * Check if customer has existing ZB account
     */
    public function hasExistingZBAccount(string $nationalId): bool
    {
        // This would typically check against bank's core banking system
        // For now, check if they have approved account applications
        return ApplicationState::query()
            ->where(function ($query) use ($nationalId) {
                $query->whereJsonContains('form_data->nationalIdNumber', $nationalId)
                    ->orWhereJsonContains('form_data->idNumber', $nationalId);
            })
            ->whereIn('form_type', ['zb_account_opening', 'individual_account_opening'])
            ->whereJsonContains('metadata->admin_status', 'approved')
            ->exists();
    }

    /**
     * Get customer's previous applications summary
     */
    public function getCustomerApplicationsSummary(string $nationalId): array
    {
        $applications = ApplicationState::query()
            ->where(function ($query) use ($nationalId) {
                $query->whereJsonContains('form_data->nationalIdNumber', $nationalId)
                    ->orWhereJsonContains('form_data->idNumber', $nationalId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_applications' => $applications->count(),
            'approved_applications' => 0,
            'pending_applications' => 0,
            'rejected_applications' => 0,
            'recent_applications' => [],
            'has_zb_account' => false,
            'has_active_loans' => false,
        ];

        foreach ($applications as $app) {
            $status = $app->metadata['admin_status'] ?? 'pending';

            switch ($status) {
                case 'approved':
                    $summary['approved_applications']++;
                    break;
                case 'rejected':
                    $summary['rejected_applications']++;
                    break;
                default:
                    $summary['pending_applications']++;
                    break;
            }

            if (in_array($app->form_type, ['zb_account_opening', 'individual_account_opening']) && $status === 'approved') {
                $summary['has_zb_account'] = true;
            }

            if (in_array($app->form_type, ['ssb', 'account_holder_loan']) && $status === 'approved') {
                $summary['has_active_loans'] = true;
            }

            // Keep recent 5 applications
            if (count($summary['recent_applications']) < 5) {
                $summary['recent_applications'][] = [
                    'reference_code' => $app->reference_code,
                    'form_type' => $app->form_type,
                    'status' => $status,
                    'created_at' => $app->created_at->format('Y-m-d'),
                    'amount' => $app->form_data['loanAmount'] ?? $app->form_data['amount'] ?? null,
                ];
            }
        }

        return $summary;
    }

    /**
     * Validate if customer is eligible for specific product
     */
    public function validateProductEligibility(string $nationalId, string $productType): array
    {
        $hasAccount = $this->hasExistingZBAccount($nationalId);
        $isFirstTime = $this->isFirstTimeApplicant($nationalId);

        $result = [
            'eligible' => true,
            'message' => 'You are eligible for this product',
            'requirements' => [],
            'blocking_factors' => [],
        ];

        switch ($productType) {
            case 'credit_facility':
            case 'ssb':
            case 'account_holder_loan':
                if (! $hasAccount) {
                    $result['eligible'] = false;
                    $result['message'] = 'You must have a ZB account to apply for credit facilities';
                    $result['blocking_factors'][] = 'No existing ZB account';
                    $result['requirements'][] = 'Open a ZB account first';
                    $result['requirements'][] = 'Deposit salary at least once';
                }
                break;

            case 'zb_account_opening':
            case 'individual_account_opening':
                if ($hasAccount) {
                    $result['message'] = 'You already have a ZB account. You can now apply for credit facilities.';
                    $result['requirements'][] = 'Consider applying for credit facilities instead';
                }
                break;
        }

        return $result;
    }
}
