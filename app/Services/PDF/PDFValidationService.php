<?php

namespace App\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDFLoggingService;

class PDFValidationService
{
    private PDFLoggingService $logger;

    public function __construct(PDFLoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validate application state before PDF generation
     */
    public function validateApplicationState(ApplicationState $applicationState): array
    {
        $errors = [];

        // Check if application state exists and is valid
        if (! $applicationState) {
            $errors[] = 'Application state not found';

            return $errors;
        }

        // Check if form data exists
        if (empty($applicationState->form_data)) {
            $errors[] = 'No form data found in application state';
        }

        // Check if session is not expired
        if ($applicationState->expires_at && $applicationState->expires_at->isPast()) {
            $errors[] = 'Application session has expired';
        }

        // Check if application is in a valid state for PDF generation
        $validStates = ['completed', 'in_review', 'approved'];
        if (! in_array($applicationState->current_step, $validStates)) {
            $errors[] = "Application is not in a valid state for PDF generation. Current state: {$applicationState->current_step}";
        }

        return $errors;
    }

    /**
     * Validate form data completeness
     */
    public function validateFormData(array $formData, string $formId): array
    {
        $errors = [];

        // Check if form ID is present
        if (empty($formId)) {
            $errors[] = 'Form ID is missing';
        }

        // Check if form responses exist
        if (empty($formData['formResponses'])) {
            $errors[] = 'Form responses are missing';

            return $errors;
        }

        $formResponses = $formData['formResponses'];

        // Validate required fields based on form type
        $requiredFields = $this->getRequiredFields($formId);
        foreach ($requiredFields as $field) {
            if (empty($formResponses[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Validate field formats
        $formatErrors = $this->validateFieldFormats($formResponses);
        $errors = array_merge($errors, $formatErrors);

        // Validate business logic
        $businessErrors = $this->validateBusinessLogic($formData, $formId);
        $errors = array_merge($errors, $businessErrors);

        return $errors;
    }

    /**
     * Get required fields for each form type
     */
    private function getRequiredFields(string $formId): array
    {
        $requiredFieldsMap = [
            'account_holder_loan_application.json' => [
                'firstName', 'lastName', 'nationalIdNumber', 'mobile', 'emailAddress',
                'residentialAddress', 'dateOfBirth', 'maritalStatus',
            ],
            'ssb_account_opening_form.json' => [
                'firstName', 'surname', 'nationalIdNumber', 'mobile', 'emailAddress',
                'residentialAddress', 'dateOfBirth', 'maritalStatus',
            ],
            'individual_account_opening.json' => [
                'firstName', 'surname', 'nationalIdNumber', 'mobile', 'emailAddress',
                'residentialAddress', 'dateOfBirth', 'maritalStatus',
            ],
            'smes_business_account_opening.json' => [
                'firstName', 'surname', 'businessName', 'nationalIdNumber', 'mobile',
                'emailAddress', 'businessAddress', 'dateOfBirth',
            ],
            'pensioners_loan_account.json' => [
                'firstName', 'lastName', 'nationalIdNumber', 'mobile', 'emailAddress',
                'residentialAddress', 'dateOfBirth', 'pensionNumber',
            ],
        ];

        return $requiredFieldsMap[$formId] ?? ['firstName', 'lastName', 'nationalIdNumber'];
    }

    /**
     * Validate field formats
     */
    private function validateFieldFormats(array $formResponses): array
    {
        $errors = [];

        // Email validation
        if (isset($formResponses['emailAddress']) && ! empty($formResponses['emailAddress'])) {
            if (! filter_var($formResponses['emailAddress'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address format';
            }
        }

        // Mobile number validation
        if (isset($formResponses['mobile']) && ! empty($formResponses['mobile'])) {
            if (! preg_match('/^\+?[1-9]\d{1,14}$/', $formResponses['mobile'])) {
                $errors[] = 'Invalid mobile number format';
            }
        }

        // National ID validation (basic format check)
        if (isset($formResponses['nationalIdNumber']) && ! empty($formResponses['nationalIdNumber'])) {
            if (! preg_match('/^[a-zA-Z0-9-]{5,20}$/', $formResponses['nationalIdNumber'])) {
                $errors[] = 'Invalid national ID number format';
            }
        }

        // Date of birth validation
        if (isset($formResponses['dateOfBirth']) && ! empty($formResponses['dateOfBirth'])) {
            try {
                $dob = new \DateTime($formResponses['dateOfBirth']);
                $now = new \DateTime;
                $age = $now->diff($dob)->y;

                if ($age < 18) {
                    $errors[] = 'Applicant must be at least 18 years old';
                }
                if ($age > 120) {
                    $errors[] = 'Invalid date of birth - age cannot exceed 120 years';
                }
            } catch (\Exception $e) {
                $errors[] = 'Invalid date of birth format';
            }
        }

        // Salary validation (if present)
        if (isset($formResponses['salary']) && ! empty($formResponses['salary'])) {
            if (! is_numeric($formResponses['salary']) || (float) $formResponses['salary'] < 0) {
                $errors[] = 'Invalid salary amount';
            }
        }

        return $errors;
    }

    /**
     * Validate business logic rules
     */
    private function validateBusinessLogic(array $formData, string $formId): array
    {
        $errors = [];

        // Loan amount validation
        if (isset($formData['amount'])) {
            $amount = (float) $formData['amount'];

            if ($amount <= 0) {
                $errors[] = 'Loan amount must be greater than zero';
            }

            // Maximum loan amount check
            $maxLoanAmount = 1000000; // $1M
            if ($amount > $maxLoanAmount) {
                $errors[] = 'Loan amount cannot exceed $'.number_format($maxLoanAmount);
            }

            // Minimum loan amount check
            $minLoanAmount = 100;
            if ($amount < $minLoanAmount) {
                $errors[] = 'Loan amount must be at least $'.number_format($minLoanAmount);
            }
        }

        // Credit term validation
        if (isset($formData['creditTerm'])) {
            $term = (int) $formData['creditTerm'];

            if ($term <= 0) {
                $errors[] = 'Credit term must be greater than zero';
            }

            if ($term > 360) { // 30 years max
                $errors[] = 'Credit term cannot exceed 360 months';
            }
        }

        // Interest rate validation
        if (isset($formData['interestRate'])) {
            $rate = (float) str_replace('%', '', $formData['interestRate']);

            if ($rate <= 0) {
                $errors[] = 'Interest rate must be greater than zero';
            }

            if ($rate > 50) { // 50% max
                $errors[] = 'Interest rate cannot exceed 50%';
            }
        }

        // Business-specific validations
        if ($formId === 'smes_business_account_opening.json') {
            $errors = array_merge($errors, $this->validateSMEBusinessLogic($formData));
        }

        return $errors;
    }

    /**
     * Validate SME business-specific logic
     */
    private function validateSMEBusinessLogic(array $formData): array
    {
        $errors = [];
        $formResponses = $formData['formResponses'] ?? [];

        // Business name validation
        if (isset($formResponses['businessName']) && strlen($formResponses['businessName']) < 2) {
            $errors[] = 'Business name must be at least 2 characters long';
        }

        // Business registration number validation
        if (isset($formResponses['businessRegistrationNumber']) && ! empty($formResponses['businessRegistrationNumber'])) {
            if (! preg_match('/^[a-zA-Z0-9\/\-]{5,20}$/', $formResponses['businessRegistrationNumber'])) {
                $errors[] = 'Invalid business registration number format';
            }
        }

        // Annual turnover validation
        if (isset($formResponses['annualTurnover']) && ! empty($formResponses['annualTurnover'])) {
            $turnover = (float) $formResponses['annualTurnover'];
            if ($turnover < 0) {
                $errors[] = 'Annual turnover cannot be negative';
            }
        }

        return $errors;
    }

    /**
     * Validate PDF generation environment
     */
    public function validatePDFEnvironment(): array
    {
        $errors = [];

        // Check if DomPDF is available
        if (! class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $errors[] = 'DomPDF package is not available';
        }

        // Check memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $recommendedMemory = 256 * 1024 * 1024; // 256MB

        if ($memoryLimitBytes < $recommendedMemory) {
            $errors[] = "Memory limit ({$memoryLimit}) is below recommended 256MB for PDF generation";
        }

        // Check execution time limit
        $timeLimit = ini_get('max_execution_time');
        if ($timeLimit > 0 && $timeLimit < 60) {
            $errors[] = "Execution time limit ({$timeLimit}s) may be too low for PDF generation";
        }

        // Check if storage is writable
        if (! is_writable(storage_path('app/public'))) {
            $errors[] = 'Storage directory is not writable';
        }

        return $errors;
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
