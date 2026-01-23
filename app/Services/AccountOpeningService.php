<?php

namespace App\Services;

use App\Models\AccountOpening;
use Illuminate\Support\Facades\Log;

class AccountOpeningService
{
    protected NotificationService $notificationService;
    protected PDFGeneratorService $pdfGenerator;

    public function __construct(
        NotificationService $notificationService,
        PDFGeneratorService $pdfGenerator
    ) {
        $this->notificationService = $notificationService;
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Send SMS when account is opened
     */
    public function sendAccountOpenedSMS(AccountOpening $accountOpening): void
    {
        $phone = $accountOpening->phone;
        if (!$phone) {
            Log::warning('No phone number for account opening', ['id' => $accountOpening->id]);
            return;
        }

        $message = "Your ZB account has been opened. Account #: {$accountOpening->zb_account_number}. Visit your nearest ZB Bank to complete the process.";
        
        $this->notificationService->sendSMS($phone, $message);
    }

    /**
     * Send SMS when approved for loan credibility
     */
    public function sendLoanEligibleSMS(AccountOpening $accountOpening): void
    {
        $phone = $accountOpening->phone;
        if (!$phone) {
            Log::warning('No phone number for account opening', ['id' => $accountOpening->id]);
            return;
        }

        $statusUrl = route('application.status');
        $message = "Congratulations! You are now eligible to apply for loans. Check your application status at {$statusUrl}";
        
        $this->notificationService->sendSMS($phone, $message);
    }

    /**
     * Send SMS when application is rejected
     */
    public function sendRejectionSMS(AccountOpening $accountOpening): void
    {
        $phone = $accountOpening->phone;
        if (!$phone) {
            Log::warning('No phone number for account opening', ['id' => $accountOpening->id]);
            return;
        }

        $reason = $accountOpening->rejection_reason ?? 'Not specified';
        $message = "Your account opening was not approved. Reason: {$reason}. You may try again.";
        
        $this->notificationService->sendSMS($phone, $message);
    }

    /**
     * Send custom SMS (Super Admin)
     */
    public function sendCustomSMS(AccountOpening $accountOpening, string $message): void
    {
        $phone = $accountOpening->phone;
        if (!$phone) {
            Log::warning('No phone number for account opening', ['id' => $accountOpening->id]);
            return;
        }

        $this->notificationService->sendSMS($phone, $message);
    }

    /**
     * Generate PDF for account opening (without invoice)
     */
    public function generatePDF(AccountOpening $accountOpening): string
    {
        // Convert AccountOpening to ApplicationState-like structure for PDF generation
        $applicationState = new \stdClass();
        $applicationState->reference_code = $accountOpening->reference_code;
        $applicationState->form_data = $accountOpening->form_data;
        $applicationState->session_id = 'account_' . $accountOpening->id;
        
        // Use existing PDF generator but specify account opening type
        return $this->pdfGenerator->generateAccountOpeningPDF($accountOpening);
    }

    /**
     * Create account opening from wizard data
     */
    public function createFromWizard(array $wizardData, string $referenceCode): AccountOpening
    {
        return AccountOpening::create([
            'reference_code' => $referenceCode,
            'user_identifier' => $wizardData['formResponses']['nationalIdNumber'] ?? $wizardData['formResponses']['phoneNumber'],
            'form_data' => $wizardData,
            'status' => AccountOpening::STATUS_PENDING,
            'selected_product' => [
                'product_name' => $wizardData['productName'] ?? null,
                'product_code' => $wizardData['productCode'] ?? null,
                'category' => $wizardData['category'] ?? null,
            ],
        ]);
    }
}
