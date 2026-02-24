<?php

namespace App\Services;

use App\Models\AccountOpening;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

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
     * Send SMS when referred to branch
     */
    public function sendReferredSMS(AccountOpening $accountOpening): void
    {
        $phone = $accountOpening->phone;
        if (!$phone) {
            Log::warning('No phone number for account opening', ['id' => $accountOpening->id]);
            return;
        }

        $branch = $accountOpening->branch ?? 'your nearest branch';
        $message = "Your account opening application has been sent to {$branch}. Please visit the branch to sign your documents. Ref: {$accountOpening->reference_code}";
        
        $this->notificationService->sendSMS($phone, $message);
    }

    /**
     * Refer account openings to branch via email with PDFs attached
     */
    public function referToBranch(Collection $records, string $branchName, string $branchEmail): int
    {
        $pdfPaths = [];
        $referredCount = 0;

        foreach ($records as $record) {
            try {
                // Generate PDF
                $pdfPath = $this->generatePDF($record);
                $pdfPaths[] = storage_path('app/' . $pdfPath);

                // Mark as referred
                $record->markAsReferred($branchName);

                // Send SMS notification
                $this->sendReferredSMS($record);

                $referredCount++;
            } catch (\Exception $e) {
                Log::error("Failed to refer account opening {$record->id}: " . $e->getMessage());
            }
        }

        // Send email to branch with all PDFs attached
        if (!empty($pdfPaths)) {
            try {
                Mail::raw(
                    "Please find attached {$referredCount} account opening application(s) for processing at {$branchName}.\n\nThese applications were submitted via the BancoSystem platform and require in-person document verification and signature.",
                    function ($message) use ($branchEmail, $branchName, $pdfPaths, $referredCount) {
                        $message->to($branchEmail)
                            ->subject("Account Opening Applications for {$branchName} ({$referredCount} applications)")
                            ->from(config('mail.from.address'), config('mail.from.name'));

                        foreach ($pdfPaths as $path) {
                            if (file_exists($path)) {
                                $message->attach($path);
                            }
                        }
                    }
                );
            } catch (\Exception $e) {
                Log::error("Failed to send refer email to {$branchEmail}: " . $e->getMessage());
            }
        }

        return $referredCount;
    }

    /**
     * Archive (soft-delete) a completed account opening record
     */
    public function archiveRecord(AccountOpening $accountOpening): void
    {
        $accountOpening->delete(); // Uses SoftDeletes trait
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
