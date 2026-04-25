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
        $deliveryTracking = \App\Models\DeliveryTracking::where('application_state_id', $application->id)->first();
        
        $timeline = $this->buildApplicationTimeline($application, $status, $deliveryTracking);

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
            'progressPercentage' => $this->calculateProgressPercentage($application, $deliveryTracking),
            'nextAction' => $deliveryTracking ? "Your delivery status: " . $deliveryTracking->status_label : ($metadata['client_status_message'] ?? $this->getNextAction($application)),
            'unclearDocuments' => $metadata['unclear_documents'] ?? [],
            'deliveryTracking' => $deliveryTracking ? [
                'status' => $deliveryTracking->status,
                'statusLabel' => $deliveryTracking->status_label,
                'courierType' => $deliveryTracking->courier_type,
                'depot' => $deliveryTracking->delivery_depot,
                'dispatchedAt' => $deliveryTracking->dispatched_at ? $deliveryTracking->dispatched_at->format('M d, Y') : null,
                'estimatedDelivery' => $deliveryTracking->estimated_delivery_date ? $deliveryTracking->estimated_delivery_date->format('M d, Y') : null,
            ] : null,
        ]);
    }
/**
 * Handle document resubmission from client
 */
public function resubmit(Request $request): JsonResponse
{
    $request->validate([
        'sessionId' => 'required|string',
        'type' => 'required|in:reupload,employment_proof,deposit_payment',
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
    } elseif ($request->type === 'employment_proof') {
        // Keep current_step — admin verifies via ProofVerificationResource
        $application->status = 'employment_proof_submitted';
        $metadata['client_status_message'] = "Proof of employment submitted. Awaiting verification.";
    } elseif ($request->type === 'deposit_payment') {
        // Keep current_step at awaiting_deposit_payment — admin verifies
        $application->status = 'deposit_proof_submitted';
        $metadata['client_status_message'] = "Proof of deposit payment submitted. Awaiting verification.";
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
        // Check for SME-specific stages
        $metadata = $application->metadata ?? [];
        $smeStage = $metadata['sme_stage'] ?? null;
        
        if ($smeStage && $application->application_type === 'sme') {
            return match($smeStage) {
                'submitted' => 'sme_submitted',
                'sme_document_review' => 'sme_document_review',
                'sme_credit_assessment' => 'sme_credit_assessment',
                'sme_committee_review' => 'sme_committee_review',
                'sme_pending_documents' => 'sme_pending_documents',
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => $application->status ?: 'processing',
            };
        }

        return match($application->current_step) {
            'pending_review' => 'document_verification',
            'awaiting_document_reupload' => 'resubmission_required',
            'awaiting_proof_of_employment' => 'employment_proof_required',
            'awaiting_deposit_payment' => 'deposit_payment_required',
            'vlc_allocation_pending' => 'vlc_allocation',
            'qupa_allocation_pending' => 'allocation',
            'awaiting_ssb_csv_export' => 'ssb_batching',
            'awaiting_ssb_approval' => 'ssb_verification',
            'officer_check' => 'under_review',
            'manager_approval' => 'final_approval',
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => $application->status ?: 'processing',
        };
    }

    private function buildApplicationTimeline(ApplicationState $application, string $status, $deliveryTracking = null): array
    {
        $metadata = $application->metadata ?? [];
        
        // SME applications get their own dedicated timeline
        if ($application->application_type === 'sme' || ($metadata['workflow_type'] ?? '') === 'sme') {
            return $this->buildSMETimeline($application, $status, $metadata, $deliveryTracking);
        }

        $timeline = [];
        $step = $application->current_step;
        $appType = $application->getApplicationType();

        // 1. Submission
        $timeline[] = [
            'title' => 'Application Submitted',
            'description' => 'Your application has been received and is entering verification.',
            'timestamp' => $application->created_at->format('M d, Y'),
            'status' => 'completed',
        ];

        // 2. Doc Verification
        $isDocDone = isset($metadata['bancozim_verification']) || in_array($step, [
            'awaiting_deposit_payment', 'awaiting_proof_of_employment',
            'vlc_allocation_pending', 'qupa_allocation_pending', 'awaiting_ssb_csv_export', 
            'awaiting_ssb_approval', 'officer_check', 'manager_approval', 'approved',
        ]);
        $timeline[] = [
            'title' => 'Document Verification',
            'description' => $isDocDone ? 'Documents verified by Bancozim Admin.' : 'Documents are being checked for clarity.',
            'timestamp' => isset($metadata['bancozim_verification']['verified_at']) ? Carbon::parse($metadata['bancozim_verification']['verified_at'])->format('M d, Y') : ($step === 'pending_review' ? 'In Progress' : ''),
            'status' => $isDocDone ? 'completed' : ($step === 'pending_review' ? 'current' : 'pending'),
        ];

        // 3. Allocation / Review Stage
        if ($appType === 'ssb') {
            // SSB Path
            $isAllocated = isset($record->qupa_admin_id) || in_array($step, ['awaiting_ssb_csv_export', 'awaiting_ssb_approval', 'approved']);
            $timeline[] = [
                'title' => 'VLC Allocation',
                'description' => $isAllocated ? 'Application allocated to branch and officer.' : 'VLC is allocating your application to a review officer.',
                'timestamp' => $isAllocated ? 'Done' : ($step === 'vlc_allocation_pending' ? 'In Progress' : ''),
                'status' => $isAllocated ? 'completed' : ($step === 'vlc_allocation_pending' ? 'current' : 'pending'),
            ];

            $isSSBDone = in_array($step, ['approved', 'completed']) || ($application->status === 'approved');
            $timeline[] = [
                'title' => 'SSB Verification',
                'description' => $isSSBDone ? 'SSB approval received.' : 'Your application is being verified with the Salary Service Bureau.',
                'timestamp' => isset($metadata['ssb_status_updated_at']) ? Carbon::parse($metadata['ssb_status_updated_at'])->format('M d, Y') : (in_array($step, ['awaiting_ssb_csv_export', 'awaiting_ssb_approval']) ? 'In Progress' : ''),
                'status' => $isSSBDone ? 'completed' : (in_array($step, ['awaiting_ssb_csv_export', 'awaiting_ssb_approval']) ? 'current' : 'pending'),
            ];
        } else {
            // Standard Path
            if (in_array($appType, ['zb_account_opening', 'account_holder'])) {
                $proofTitle = $appType === 'zb_account_opening' ? 'Deposit Payment Verification' : 'Employment Proof Verification';
                $proofDesc = $appType === 'zb_account_opening' ? 'deposit payment' : 'employment proof';
                $proofStep = $appType === 'zb_account_opening' ? 'awaiting_deposit_payment' : 'awaiting_proof_of_employment';

                $isProofDone = isset($metadata['proof_verified']) || in_array($step, ['qupa_allocation_pending', 'officer_check', 'manager_approval', 'approved']);
                $timeline[] = [
                    'title' => $proofTitle,
                    'description' => $isProofDone ? ucfirst($proofDesc) . ' verified.' : 'Awaiting ' . $proofDesc . '.',
                    'timestamp' => isset($metadata['proof_verified']['verified_at'])
                        ? Carbon::parse($metadata['proof_verified']['verified_at'])->format('M d, Y')
                        : ($step === $proofStep ? 'In Progress' : ''),
                    'status' => $isProofDone ? 'completed' : ($step === $proofStep ? 'current' : 'pending'),
                ];
            }

            // 3. Officer Review
            $isOfficerDone = isset($metadata['officer_check']) || in_array($step, ['manager_approval', 'approved']);
            $timeline[] = [
                'title' => 'Loan Officer Assessment',
                'description' => $isOfficerDone ? 'Financial assessment completed.' : 'An officer is reviewing your financial eligibility.',
                'timestamp' => isset($metadata['officer_check']['date']) ? Carbon::parse($metadata['officer_check']['date'])->format('M d, Y') : ($step === 'officer_check' ? 'In Progress' : ''),
                'status' => $isOfficerDone ? 'completed' : ($step === 'officer_check' ? 'current' : 'pending'),
            ];

            // 4. Final Approval
            $isApproved = in_array($step, ['approved', 'completed']) || ($application->status === 'approved');
            $timeline[] = [
                'title' => 'Manager Approval',
                'description' => $isApproved ? 'Application approved by Branch Manager.' : 'Awaiting final sign-off.',
                'timestamp' => $application->approved_at ? $application->approved_at->format('M d, Y') : ($step === 'manager_approval' ? 'In Progress' : ''),
                'status' => $isApproved ? 'completed' : ($step === 'manager_approval' ? 'current' : 'pending'),
            ];
        }

        // Final. Product Delivery
        $isFinalApproved = in_array($step, ['approved', 'completed']) || ($application->status === 'approved');
        if ($isFinalApproved) {
            $isDelivered = $deliveryTracking && $deliveryTracking->status === 'delivered';
            $timeline[] = [
                'title' => 'Product Delivery',
                'description' => $deliveryTracking 
                    ? "Status: " . $deliveryTracking->status_label . ($deliveryTracking->courier_type ? " via " . $deliveryTracking->courier_type : "")
                    : 'Delivery process initiated.',
                'timestamp' => $deliveryTracking && $deliveryTracking->delivered_at ? $deliveryTracking->delivered_at->format('M d, Y') : ($deliveryTracking ? 'In Transit' : 'Pending'),
                'status' => $isDelivered ? 'completed' : 'current',
            ];
        }

        return $timeline;
    }

    /**
     * Build SME-specific approval timeline for client tracking
     */
    private function buildSMETimeline(ApplicationState $application, string $status, array $metadata, $deliveryTracking = null): array
    {
        $smeStage = $metadata['sme_stage'] ?? 'submitted';
        $timeline = [];

        // Define SME stages in order
        $stages = [
            'submitted' => [
                'title' => 'Application Submitted',
                'description_done' => 'Your SME Booster application has been submitted.',
                'description_pending' => 'Submit your SME Booster application.',
            ],
            'sme_document_review' => [
                'title' => 'Document Review',
                'description_done' => 'Business documents have been reviewed and verified.',
                'description_pending' => 'Your business documents are being reviewed.',
            ],
            'sme_credit_assessment' => [
                'title' => 'Credit Assessment',
                'description_done' => 'Financial assessment completed.',
                'description_pending' => 'Your business financials are being assessed.',
            ],
            'sme_committee_review' => [
                'title' => 'Committee Review',
                'description_done' => 'Committee has reviewed your application.',
                'description_pending' => 'Your application is with the approval committee.',
            ],
            'approved' => [
                'title' => 'Approved',
                'description_done' => 'Your SME Booster application has been approved!',
                'description_pending' => 'Awaiting final approval.',
            ],
        ];

        $foundCurrent = false;
        foreach ($stages as $stageKey => $stageInfo) {
            $stageTimestamp = $metadata["sme_{$stageKey}_at"] ?? null;
            $stageBy = $metadata["sme_{$stageKey}_by"] ?? null;

            if ($stageKey === $smeStage) {
                // Current stage
                $timeline[] = [
                    'title' => $stageInfo['title'],
                    'description' => $stageInfo['description_pending'],
                    'timestamp' => $stageTimestamp ? Carbon::parse($stageTimestamp)->format('M d, Y') : 'In Progress',
                    'status' => ($smeStage === 'approved' || $smeStage === 'rejected') ? 'completed' : 'current',
                ];
                $foundCurrent = true;
            } elseif (!$foundCurrent) {
                // Completed stage
                $timeline[] = [
                    'title' => $stageInfo['title'],
                    'description' => $stageInfo['description_done'],
                    'timestamp' => $stageTimestamp ? Carbon::parse($stageTimestamp)->format('M d, Y') : 'Done',
                    'status' => 'completed',
                ];
            } else {
                // Future stage
                $timeline[] = [
                    'title' => $stageInfo['title'],
                    'description' => $stageInfo['description_pending'],
                    'timestamp' => '',
                    'status' => 'pending',
                ];
            }
        }

        // Add delivery stage if approved
        if ($smeStage === 'approved') {
            $isDelivered = $deliveryTracking && $deliveryTracking->status === 'delivered';
            $timeline[] = [
                'title' => 'Product Delivery',
                'description' => $deliveryTracking
                    ? "Status: " . $deliveryTracking->status_label
                    : 'Delivery process initiated.',
                'timestamp' => $deliveryTracking && $deliveryTracking->delivered_at ? $deliveryTracking->delivered_at->format('M d, Y') : 'Pending',
                'status' => $isDelivered ? 'completed' : 'current',
            ];
        }

        // Handle rejection
        if ($smeStage === 'rejected') {
            $timeline[] = [
                'title' => 'Application Rejected',
                'description' => 'Unfortunately, your SME application did not meet the criteria at this time.',
                'timestamp' => $metadata['sme_rejected_at'] ?? now()->format('M d, Y'),
                'status' => 'rejected',
            ];
        }

        return $timeline;
    }

    private function calculateProgressPercentage(ApplicationState $application, $deliveryTracking = null): int
    {
        if ($deliveryTracking) {
            return match($deliveryTracking->status) {
                'pending' => 85,
                'processing', 'dispatched' => 90,
                'in_transit', 'out_for_delivery' => 95,
                'delivered' => 100,
                default => 85,
            };
        }

        // SME-specific progress percentages
        $metadata = $application->metadata ?? [];
        if ($application->application_type === 'sme') {
            $smeStage = $metadata['sme_stage'] ?? 'submitted';
            return match($smeStage) {
                'submitted' => 15,
                'sme_document_review' => 35,
                'sme_credit_assessment' => 55,
                'sme_committee_review' => 75,
                'sme_pending_documents' => 30,
                'approved' => 90,
                'rejected' => 0,
                default => 10,
            };
        }

        return match($application->current_step) {
            'pending_review' => 20,
            'awaiting_document_reupload' => 15,
            'awaiting_deposit_payment' => 30,
            'awaiting_proof_of_employment' => 30,
            'vlc_allocation_pending' => 35,
            'qupa_allocation_pending' => 40,
            'awaiting_ssb_csv_export' => 50,
            'awaiting_ssb_approval' => 70,
            'officer_check' => 60,
            'manager_approval' => 80,
            'approved' => 85,
            'rejected' => 0,
            default => 10,
        };
    }

    private function getNextAction(ApplicationState $application): string
    {
        $formData = $application->form_data ?? [];
        $creditType = $formData['creditType'] ?? '';
        $isPDC = str_starts_with($creditType, 'PDC');

        // SME-specific next actions
        $metadata = $application->metadata ?? [];
        if ($application->application_type === 'sme') {
            $smeStage = $metadata['sme_stage'] ?? 'submitted';
            return match($smeStage) {
                'submitted' => 'Your SME Booster application has been submitted and is entering document review.',
                'sme_document_review' => 'Your business documents are being reviewed by our SME team.',
                'sme_credit_assessment' => 'A credit officer is assessing your business financials.',
                'sme_committee_review' => 'Your application is with the approval committee for final review.',
                'sme_pending_documents' => 'Please submit the additional documents requested by our team.',
                'approved' => 'Your SME Booster application has been approved! You will receive delivery updates shortly.',
                'rejected' => 'Unfortunately, your SME application did not meet the criteria at this time.',
                default => 'Your SME application is being processed.',
            };
        }

        return match($application->current_step) {
            'pending_review' => 'Bancozim Admin is currently verifying your uploaded documents.',
            'awaiting_document_reupload' => 'Please re-upload the requested documents using the form below.',
            'awaiting_deposit_payment' => $isPDC 
                ? 'Your application has been approved! Please pay the required deposit below to initiate delivery.'
                : 'Please upload your proof of deposit payment using the form below.',
            'awaiting_proof_of_employment' => 'Please upload your Confirmation of Employment letter using the form below.',
            'vlc_allocation_pending' => 'VLC Manager is allocating your application for processing.',
            'qupa_allocation_pending' => 'Your application is being allocated to a specific branch.',
            'awaiting_ssb_csv_export' => 'Your application is ready and waiting for the next SSB batch export.',
            'awaiting_ssb_approval' => 'Your application has been sent to SSB for final verification.',
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
