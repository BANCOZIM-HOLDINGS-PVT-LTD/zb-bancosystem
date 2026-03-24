<?php

namespace App\Http\Controllers;

use App\Models\AccountOpening;
use App\Models\ApplicationState;
use App\Services\NotificationService;
use App\Services\ReferenceCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedResponse;
use Carbon\Carbon;

class ApplicationStatusController extends Controller
{
    protected $referenceCodeService;
    protected $notificationService;

    public function __construct(ReferenceCodeService $referenceCodeService, NotificationService $notificationService)
    {
        $this->referenceCodeService = $referenceCodeService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get application status by reference number
     */
    public function getStatus(string $reference): JsonResponse
    {
        $application = null;
        $reference = strtoupper(str_replace([' ', '-'], '', trim($reference)));

        // Check AccountOpening first
        $accountOpening = AccountOpening::where('reference_code', $reference)
            ->orWhere('user_identifier', $reference)
            ->first();

        if ($accountOpening) {
            return $this->getAccountOpeningStatus($accountOpening);
        }

        // Check ApplicationState
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        if (!$application) {
            // Last resort: search in form_data
            $application = ApplicationState::where(function($query) use ($reference) {
                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                if ($isPgsql) {
                    $query->whereRaw("form_data->'formResponses'->>'nationalIdNumber' = ?", [$reference])
                          ->orWhereRaw("form_data->'formResponses'->>'nationalId' = ?", [$reference]);
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.nationalIdNumber')) = ?", [$reference])
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.nationalId')) = ?", [$reference]);
                }
            })->first();
        }

        if (!$application) {
            return response()->json([
                'error' => 'Application not found. Please check your reference number.'
            ], 404);
        }

        $formData = $application->form_data ?? [];
        $metadata = $application->metadata ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        $status = $this->determineApplicationStatus($application);
        $timeline = $this->buildApplicationTimeline($application, $status);

        $applicantName = trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))) ?: 'N/A';
        $productName = $formData['productName'] ?? 'ZB Product';

        return response()->json([
            'sessionId' => $application->session_id,
            'status' => $status,
            'currentStep' => $application->current_step,
            'applicantName' => $applicantName,
            'productName' => $productName,
            'loanAmount' => $formData['finalPrice'] ?? ($formResponses['loanAmount'] ?? '0'),
            'submittedAt' => $application->created_at->format('F j, Y'),
            'lastUpdated' => $application->updated_at->format('F j, Y'),
            'timeline' => $timeline,
            'progressPercentage' => $this->calculateProgressPercentage($application),
            'nextAction' => $metadata['client_status_message'] ?? $this->getNextAction($application),
            'unclearDocuments' => $metadata['unclear_documents'] ?? [],
        ]);
    }
/**
 * Handle document resubmission from client
 */
public function resubmit(Request $request): JsonResponse
{
    $request->validate([
        'sessionId' => 'required|string',
        'type' => 'required|in:reupload,employment_proof',
        'documents' => 'required|array',
    ]);

    $application = ApplicationState::where('session_id', $request->sessionId)->first();

    if (!$application) {
        return response()->json(['success' => false, 'message' => 'Application not found'], 404);
    }

    $formData = $application->form_data ?? [];
    $metadata = $application->metadata ?? [];
    $resubmittedDocs = [];

    foreach ($request->file('documents') as $key => $file) {
        $path = $file->store('applications/resubmissions/' . $application->session_id, 'public');
        $resubmittedDocs[$key] = [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'type' => $file->getClientMimeType(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        // Update the main form_data documents if it's a re-upload of existing type
        if ($request->type === 'reupload') {
            // This logic depends on how documents are structured in form_data
            // We'll add to a resubmission log in metadata for admin visibility
        }
    }

    $metadata['resubmissions'][] = [
        'type' => $request->type,
        'docs' => $resubmittedDocs,
        'at' => now()->toIso8601String(),
    ];

    // Update application state
    if ($request->type === 'reupload') {
        $application->current_step = 'pending_review'; // Go back to Bancozim check
        $application->status = 'resubmitted';
        $metadata['client_status_message'] = "Documents resubmitted. Awaiting Bancozim Re-verification.";
    } else {
        // Proof of employment submitted
        $application->current_step = 'officer_check'; // Move to Stage 2: Qupa Officer
        $application->status = 'employment_proof_submitted';
        $metadata['client_status_message'] = "Proof of employment submitted. Awaiting Qupa Loan Officer Checking";
    }

    $application->metadata = $metadata;
    $application->save();

    return response()->json([
        'success' => true,
        'message' => 'Documents submitted successfully. Your application status has been updated.',
    ]);
}

private function determineApplicationStatus(ApplicationState $application): string
{
    return match($application->current_step) {
        'pending_review' => 'document_verification',
        'awaiting_document_reupload' => 'resubmission_required',
        'awaiting_proof_of_employment' => 'employment_proof_required',
        'qupa_allocation_pending' => 'allocation',
        'officer_check' => 'under_review',
        'manager_approval' => 'final_approval',
        'approved' => 'approved',
        'rejected' => 'rejected',
        default => $application->status ?: 'processing',
    };
}

    }

    private function buildApplicationTimeline(ApplicationState $application, string $status): array
    {
        $timeline = [];
        $metadata = $application->metadata ?? [];
        $step = $application->current_step;

        // 1. Submission
        $timeline[] = [
            'title' => 'Application Submitted',
            'description' => 'Your application has been received and is entering verification.',
            'timestamp' => $application->created_at->format('M d, Y'),
            'status' => 'completed',
        ];

        // 2. Doc Verification
        $isDocDone = isset($metadata['bancozim_verification']) || in_array($step, ['qupa_allocation_pending', 'officer_check', 'manager_approval', 'approved']);
        $timeline[] = [
            'title' => 'Document Verification',
            'description' => $isDocDone ? 'Documents verified by Bancozim Admin.' : 'Documents are being checked for clarity.',
            'timestamp' => isset($metadata['bancozim_verification']['verified_at']) ? Carbon::parse($metadata['bancozim_verification']['verified_at'])->format('M d, Y') : ($step === 'pending_review' ? 'In Progress' : ''),
            'status' => $isDocDone ? 'completed' : ($step === 'pending_review' ? 'current' : 'pending'),
        ];

        // 3. Officer Review
        $isOfficerDone = isset($metadata['officer_check']) || in_array($step, ['manager_approval', 'approved']);
        $timeline[] = [
            'title' => 'Loan Officer Assessment',
            'description' => $isOfficerDone ? 'Financial assessment completed.' : 'An officer is reviewing your financial eligibility.',
            'timestamp' => isset($metadata['officer_check']['date']) ? Carbon::parse($metadata['officer_check']['date'])->format('M d, Y') : ($step === 'officer_check' ? 'In Progress' : ''),
            'status' => $isOfficerDone ? 'completed' : ($step === 'officer_check' ? 'current' : 'pending'),
        ];

        // 4. Final Approval
        $isApproved = $step === 'approved';
        $timeline[] = [
            'title' => 'Manager Approval',
            'description' => $isApproved ? 'Application approved by Branch Manager.' : 'Awaiting final sign-off.',
            'timestamp' => $application->approved_at ? $application->approved_at->format('M d, Y') : ($step === 'manager_approval' ? 'In Progress' : ''),
            'status' => $isApproved ? 'completed' : ($step === 'manager_approval' ? 'current' : 'pending'),
        ];

        return $timeline;
    }

    private function calculateProgressPercentage(ApplicationState $application): int
    {
        return match($application->current_step) {
            'pending_review' => 20,
            'qupa_allocation_pending' => 40,
            'officer_check' => 60,
            'manager_approval' => 80,
            'approved' => 100,
            'rejected' => 0,
            default => 10,
        };
    }

    private function getNextAction(ApplicationState $application): string
    {
        return match($application->current_step) {
            'pending_review' => 'Bancozim Admin is currently verifying your uploaded documents.',
            'qupa_allocation_pending' => 'Your application is being allocated to a specific branch.',
            'officer_check' => 'A Loan Officer is performing a financial assessment.',
            'manager_approval' => 'Application is with the Branch Manager for final approval.',
            'approved' => 'Application Approved! You will receive delivery updates shortly.',
            'rejected' => 'Unfortunately, your application was not successful at this time.',
            default => 'Your application is being processed.',
        };
    }

    private function getAccountOpeningStatus(AccountOpening $accountOpening): JsonResponse
    {
        return response()->json([
            'sessionId' => $accountOpening->reference_code,
            'status' => $accountOpening->status,
            'applicantName' => $accountOpening->applicant_name,
            'productName' => 'Account Opening',
            'submittedAt' => $accountOpening->created_at->format('F j, Y'),
            'progressPercentage' => $accountOpening->status === 'approved' ? 100 : 50,
            'nextAction' => 'Account opening in progress.',
        ]);
    }
}
