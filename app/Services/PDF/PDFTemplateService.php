<?php

namespace App\Services\PDF;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;

class PDFTemplateService
{
    /**
     * Get the appropriate template for the given form ID
     */
    public function getTemplateForFormId(string $formId): string
    {
        $templateMap = [
            'account_holder_loan_application.json' => 'forms.account_holders_pdf',
            'ssb_account_opening_form.json' => 'forms.ssb_form_pdf',
            'individual_account_opening.json' => 'forms.zb_account_opening_pdf',
            'smes_business_account_opening.json' => 'forms.sme_business_pdf',
            'pensioners_loan_account.json' => 'forms.account_holders_pdf',
        ];

        return $templateMap[$formId] ?? 'forms.account_holders_pdf';
    }

    /**
     * Detect form type from application data
     */
    public function detectFormType(ApplicationState $applicationState): string
    {
        $formData = $applicationState->form_data ?? [];

        // Check metadata first
        if (isset($formData['formType'])) {
            return $this->getTemplateForFormType($formData['formType']);
        }

        // Check for specific form indicators
        if (isset($formData['responsibleMinistry'])) {
            return 'forms.ssb_form_pdf';
        }

        if (isset($formData['businessName']) || isset($formData['registeredName']) || isset($formData['businessRegistration'])) {
            return 'forms.sme_business_pdf';
        }

        if (isset($formData['accountType']) || isset($formData['accountCurrency'])) {
            return 'forms.zb_account_opening_pdf';
        }

        // Default to account holders
        return 'forms.account_holders_pdf';
    }

    /**
     * Get template for form type
     */
    protected function getTemplateForFormType(string $formType): string
    {
        $typeMap = [
            'ssb' => 'forms.ssb_form_pdf',
            'sme_business' => 'forms.sme_business_pdf',
            'zb_account_opening' => 'forms.zb_account_opening_pdf',
            'account_holders' => 'forms.account_holders_pdf',
        ];

        return $typeMap[$formType] ?? 'forms.account_holders_pdf';
    }

    /**
     * Prepare data for PDF template rendering
     */
    public function prepareTemplateData(ApplicationState $applicationState): array
    {
        $formData = $applicationState->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $metadata = $applicationState->metadata ?? [];

        // Base data structure
        $pdfData = [
            'sessionId' => $applicationState->session_id,
            'referenceCode' => $applicationState->reference_code,
            'formId' => $formData['formId'] ?? '',
            'submissionDate' => $applicationState->created_at->format('Y-m-d'),
            'submissionTime' => $applicationState->created_at->format('H:i:s'),
        ];

        // Merge form responses
        $pdfData = array_merge($pdfData, $formResponses);

        // Add calculated fields
        $pdfData = $this->addCalculatedFields($pdfData, $formData);

        // Add business and product information
        $pdfData = $this->addBusinessInformation($pdfData, $formData);

        // Add metadata
        $pdfData['metadata'] = $metadata;

        return $pdfData;
    }

    /**
     * Add calculated fields to PDF data
     */
    private function addCalculatedFields(array $pdfData, array $formData): array
    {
        // Calculate loan details if present
        if (isset($formData['amount'])) {
            $amount = (float) $formData['amount'];
            $term = (int) ($formData['creditTerm'] ?? 12);
            $interestRate = (float) str_replace('%', '', $formData['interestRate'] ?? '15');

            $pdfData['loanAmount'] = number_format($amount, 2);
            $pdfData['loanTerm'] = $term;
            $pdfData['interestRate'] = $interestRate.'%';

            // Calculate monthly payment
            if ($amount > 0 && $term > 0 && $interestRate > 0) {
                $monthlyRate = $interestRate / 100 / 12;
                $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $term)) / (pow(1 + $monthlyRate, $term) - 1);
                $pdfData['monthlyPayment'] = number_format($monthlyPayment, 2);
                $pdfData['totalPayment'] = number_format($monthlyPayment * $term, 2);
                $pdfData['totalInterest'] = number_format(($monthlyPayment * $term) - $amount, 2);
            }
        }

        // Format currency fields
        $currencyFields = ['salary', 'otherIncome', 'monthlyExpenses', 'existingLoans'];
        foreach ($currencyFields as $field) {
            if (isset($pdfData[$field]) && is_numeric($pdfData[$field])) {
                $pdfData[$field] = number_format((float) $pdfData[$field], 2);
            }
        }

        // Format date fields
        $dateFields = ['dateOfBirth', 'passportExpiry', 'employmentStartDate'];
        foreach ($dateFields as $field) {
            if (isset($pdfData[$field]) && $pdfData[$field]) {
                try {
                    $date = new \DateTime($pdfData[$field]);
                    $pdfData[$field] = $date->format('d/m/Y');
                } catch (\Exception $e) {
                    Log::warning("Invalid date format for field {$field}: {$pdfData[$field]}");
                }
            }
        }

        return $pdfData;
    }

    /**
     * Add business and product information
     */
    private function addBusinessInformation(array $pdfData, array $formData): array
    {
        // Add selected business information
        if (isset($formData['selectedBusiness'])) {
            $business = $formData['selectedBusiness'];
            $pdfData['businessName'] = $business['name'] ?? '';
            $pdfData['businessDescription'] = $business['description'] ?? '';
        }

        // Add selected scale information
        if (isset($formData['selectedScale'])) {
            $scale = $formData['selectedScale'];
            $pdfData['scaleName'] = $scale['name'] ?? '';
            $pdfData['scaleDescription'] = $scale['description'] ?? '';
        }

        // Add category information
        $pdfData['category'] = $formData['category'] ?? '';
        $pdfData['subcategory'] = $formData['subcategory'] ?? '';
        $pdfData['business'] = $formData['business'] ?? '';
        $pdfData['scale'] = $formData['scale'] ?? '';

        return $pdfData;
    }

    /**
     * Get application type from form ID
     */
    public function getApplicationTypeFromFormId(string $formId): string
    {
        $typeMap = [
            'account_holder_loan_application.json' => 'Account Holder Loan Application',
            'ssb_account_opening_form.json' => 'SSB Account Opening',
            'individual_account_opening.json' => 'New ZB Account Opening',
            'smes_business_account_opening.json' => 'SME Business Account Opening',
            'pensioners_loan_account.json' => 'Pensioners Loan Account',
        ];

        return $typeMap[$formId] ?? 'Unknown Application Type';
    }

    /**
     * Validate template data before rendering
     */
    public function validateTemplateData(array $data, string $formId): array
    {
        $errors = [];

        // Required fields based on form type
        $requiredFields = $this->getRequiredFieldsForForm($formId);

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate specific field formats
        if (isset($data['emailAddress']) && ! filter_var($data['emailAddress'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address format';
        }

        if (isset($data['mobile']) && ! preg_match('/^\+?[1-9]\d{1,14}$/', $data['mobile'])) {
            $errors[] = 'Invalid mobile number format';
        }

        return $errors;
    }

    /**
     * Get required fields for specific form types
     */
    private function getRequiredFieldsForForm(string $formId): array
    {
        $requiredFieldsMap = [
            'account_holder_loan_application.json' => [
                'firstName', 'lastName', 'nationalIdNumber', 'mobile',
            ],
            'ssb_account_opening_form.json' => [
                'firstName', 'surname', 'nationalIdNumber', 'mobile',
            ],
            'individual_account_opening.json' => [
                'firstName', 'surname', 'nationalIdNumber', 'mobile',
            ],
            'smes_business_account_opening.json' => [
                'firstName', 'surname', 'businessName', 'nationalIdNumber', 'mobile',
            ],
            'pensioners_loan_account.json' => [
                'firstName', 'lastName', 'nationalIdNumber', 'mobile',
            ],
        ];

        return $requiredFieldsMap[$formId] ?? ['firstName', 'lastName'];
    }
}
