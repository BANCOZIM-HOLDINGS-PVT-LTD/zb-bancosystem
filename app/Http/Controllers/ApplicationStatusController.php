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

    /**
     * Create a new controller instance.
     *
     * @param ReferenceCodeService $referenceCodeService
     * @param NotificationService $notificationService
     * @return void
     */
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
        // Look up application by session ID or reference code (national ID)
        $application = null;
        $accountOpening = null;

        // Sanitize the reference by removing spaces and special characters
        $reference = strtoupper(str_replace([' ', '-'], '', trim($reference)));

        // First check AccountOpening table (for account opening applications)
        $accountOpening = AccountOpening::where('reference_code', $reference)->first();
        
        // If not found by reference_code, try user_identifier
        if (!$accountOpening) {
            $accountOpening = AccountOpening::where('user_identifier', $reference)->first();
        }

        // If we found an AccountOpening, return its status
        if ($accountOpening) {
            return $this->getAccountOpeningStatus($accountOpening);
        }

        // Fall back to ApplicationState lookup for ZB/SSB loan applications
        // First try to find by reference code (national ID)
        // National IDs are alphanumeric and can be of varying lengths
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        // If not found by reference code, try by session ID
        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        // If still not found, search in form_data for National ID
        if (!$application) {
            $applications = ApplicationState::whereNotNull('form_data')->get();
            foreach ($applications as $app) {
                $formData = $app->form_data ?? [];
                $formResponses = $formData['formResponses'] ?? [];
                $natId = $formResponses['idNumber'] ?? ($formResponses['nationalIdNumber'] ?? null);

                if ($natId && strtoupper(str_replace([' ', '-'], '', $natId)) === $reference) {
                    $application = $app;
                    break; // Get the first matching application
                }
            }
        }

        if (!$application) {
            return response()->json([
                'error' => 'Application not found. Please check your National ID number or reference number and try again.'
            ], 404);
        }

        $formData = $application->form_data ?? [];
        $metadata = $application->metadata ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        // Determine application status
        $status = $this->determineApplicationStatus($application);

        // Build timeline
        $timeline = $this->buildApplicationTimeline($application, $status);

        // Get applicant name
        $applicantName = trim(
            ($formResponses['firstName'] ?? '') . ' ' .
            ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))
        ) ?: 'N/A';

        // Get business/product info
        $business = $formData['business'] ?? 'N/A';
        $loanAmount = $formData['amount'] ?? ($formResponses['loanAmount'] ?? '0');

        // Format response
        $response = [
            'sessionId' => $application->session_id,
            'status' => $status,
            'applicantName' => $applicantName,
            'business' => $business,
            'loanAmount' => $loanAmount,
            'submittedAt' => $application->created_at->format('F j, Y g:i A'),
            'lastUpdated' => $application->updated_at->format('F j, Y g:i A'),
            'timeline' => $timeline,
            'progressPercentage' => $this->calculateProgressPercentage($status, $timeline),
            'estimatedCompletionDate' => $this->getEstimatedCompletionDate($status),
            'nextAction' => $this->getNextAction($status),
            'notifications' => $this->generateNotifications($application, $status),
        ];

        // Add rejection reason if rejected
        if ($status === 'rejected' && isset($metadata['rejection_reason'])) {
            $response['rejectionReason'] = $metadata['rejection_reason'];
        }

        // Add approval details if approved
        if ($status === 'approved' && isset($metadata['approval_details'])) {
            $response['approvalDetails'] = [
                'approvedAmount' => $metadata['approval_details']['amount'] ?? $loanAmount,
                'approvedAt' => Carbon::parse($metadata['approval_details']['approved_at'] ?? now())->format('F j, Y'),
                'disbursementDate' => isset($metadata['approval_details']['disbursement_date'])
                    ? Carbon::parse($metadata['approval_details']['disbursement_date'])->format('F j, Y')
                    : null,
            ];
        }

        return response()->json($response);
    }

    /**
     * Get status for Account Opening applications
     */
    private function getAccountOpeningStatus(AccountOpening $accountOpening): JsonResponse
    {
        $formData = $accountOpening->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        // Map AccountOpening status to user-facing status
        $status = $this->mapAccountOpeningStatus($accountOpening->status);

        // Get applicant name
        $applicantName = $accountOpening->applicant_name;

        // Get product info
        $selectedProduct = $accountOpening->selected_product ?? [];
        $business = $selectedProduct['product_name'] ?? ($formData['business'] ?? 'Account Opening');

        // Build response
        $response = [
            'sessionId' => $accountOpening->reference_code,
            'status' => $status,
            'applicationType' => 'account_opening',
            'applicantName' => $applicantName,
            'business' => $business,
            'loanAmount' => '0', // Account opening doesn't have loan amount
            'submittedAt' => $accountOpening->created_at->format('F j, Y g:i A'),
            'lastUpdated' => $accountOpening->updated_at->format('F j, Y g:i A'),
            'progressPercentage' => $this->calculateAccountOpeningProgress($accountOpening->status),
            'nextAction' => $this->getAccountOpeningNextAction($accountOpening->status),
        ];

        // Add ZB account number if account has been opened
        if ($accountOpening->zb_account_number) {
            $response['zbAccountNumber'] = $accountOpening->zb_account_number;
        }

        // Add rejection reason if rejected
        if ($accountOpening->status === AccountOpening::STATUS_REJECTED && $accountOpening->rejection_reason) {
            $response['rejectionReason'] = $accountOpening->rejection_reason;
        }

        // Add loan eligibility info
        if ($accountOpening->loan_eligible) {
            $response['loanEligible'] = true;
            $response['loanEligibleAt'] = $accountOpening->loan_eligible_at 
                ? $accountOpening->loan_eligible_at->format('F j, Y g:i A') 
                : null;
        }

        return response()->json($response);
    }

    /**
     * Map AccountOpening status to user-facing status
     */
    private function mapAccountOpeningStatus(string $status): string
    {
        return match ($status) {
            AccountOpening::STATUS_PENDING => 'under_review',
            AccountOpening::STATUS_ACCOUNT_OPENED => 'account_opened',
            AccountOpening::STATUS_LOAN_ELIGIBLE => 'completed',
            AccountOpening::STATUS_REJECTED => 'rejected',
            default => 'under_review',
        };
    }

    /**
     * Calculate progress for account opening applications
     */
    private function calculateAccountOpeningProgress(string $status): int
    {
        return match ($status) {
            AccountOpening::STATUS_PENDING => 40,
            AccountOpening::STATUS_ACCOUNT_OPENED => 80,
            AccountOpening::STATUS_LOAN_ELIGIBLE => 100,
            AccountOpening::STATUS_REJECTED => 0,
            default => 25,
        };
    }

    /**
     * Get next action for account opening applications
     */
    private function getAccountOpeningNextAction(string $status): ?string
    {
        return match ($status) {
            AccountOpening::STATUS_PENDING => 'Your account opening application is being reviewed',
            AccountOpening::STATUS_ACCOUNT_OPENED => 'Visit your nearest ZB Bank branch to complete the process',
            AccountOpening::STATUS_LOAN_ELIGIBLE => 'You can now apply for loans through our platform',
            AccountOpening::STATUS_REJECTED => 'You may submit a new application',
            default => 'Awaiting update',
        };
    }


    /**
     * Update application status (for admin use)
     */
    public function updateStatus(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,under_review,approved,rejected',
            'reason' => 'required_if:status,rejected',
            'approval_details' => 'required_if:status,approved|array',
            'approval_details.amount' => 'required_if:status,approved|numeric|min:0',
            'approval_details.disbursement_date' => 'required_if:status,approved|date',
        ]);

        $application = ApplicationState::where('session_id', $sessionId)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $oldStatus = $metadata['status'] ?? 'pending';
        $newStatus = $request->status;

        $metadata['status'] = $newStatus;
        $metadata['status_updated_at'] = now()->toIso8601String();
        $metadata['status_updated_by'] = auth()->id() ?? 'system';

        // Add status history
        $metadata['status_history'] = $metadata['status_history'] ?? [];
        $metadata['status_history'][] = [
            'status' => $newStatus,
            'timestamp' => now()->toIso8601String(),
            'updated_by' => auth()->id() ?? 'system',
            'reason' => $request->reason,
        ];

        if ($newStatus === 'rejected') {
            $metadata['rejection_reason'] = $request->reason;
        }

        if ($newStatus === 'approved') {
            $metadata['approval_details'] = [
                'amount' => $request->approval_details['amount'],
                'approved_at' => now()->toIso8601String(),
                'disbursement_date' => $request->approval_details['disbursement_date'],
            ];
        }

        $application->status = $newStatus;
        $application->metadata = $metadata;
        $application->save();

        // Send notification if status has changed
        if ($oldStatus !== $newStatus) {
            $this->notificationService->sendStatusUpdateNotification($application, $oldStatus, $newStatus);
        }

        return response()->json([
            'message' => 'Application status updated successfully',
            'status' => $newStatus,
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function markNotificationsAsRead(Request $request, string $reference): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'array',
            'notification_ids.*' => 'string',
        ]);

        // Look up application by session ID or reference code (national ID)
        $application = null;

        // Sanitize the reference
        $reference = strtoupper(trim($reference));

        // Try to find by reference code (national ID)
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        // If not found by reference code, try by session ID
        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $notificationIds = $request->input('notification_ids', []);
        $success = $this->notificationService->markNotificationsAsRead($application, $notificationIds);

        if ($success) {
            return response()->json(['message' => 'Notifications marked as read']);
        } else {
            return response()->json(['error' => 'Failed to mark notifications as read'], 500);
        }
    }

    /**
     * Determine the current status of the application
     */
    private function determineApplicationStatus(ApplicationState $application): string
    {
        $metadata = $application->metadata ?? [];

        // Check for explicit status in database column
        if (!empty($application->status)) {
            return $application->status;
        }

        // Check for explicit status in metadata
        if (isset($metadata['status'])) {
            return $metadata['status'];
        }

        // Determine based on application state
        if ($application->current_step === 'completed') {
            // Check if it's been reviewed
            if (isset($metadata['reviewed_at'])) {
                return isset($metadata['approved']) && $metadata['approved'] ? 'approved' : 'rejected';
            }
            return 'under_review';
        }

        return 'pending';
    }

    /**
     * Build the application timeline
     */
    private function buildApplicationTimeline(ApplicationState $application, string $currentStatus): array
    {
        $timeline = [];
        $metadata = $application->metadata ?? [];

        // Application Started
        $timeline[] = [
            'id' => 1,
            'title' => 'Application Started',
            'description' => 'You began your loan application',
            'timestamp' => $application->created_at->format('F j, Y g:i A'),
            'status' => 'completed',
            'details' => 'Application initiated and basic information collected',
            'actionRequired' => false,
        ];

        // Application Submitted
        if ($application->current_step === 'completed') {
            $completedAt = $metadata['completed_at'] ?? $application->updated_at;
            $timeline[] = [
                'id' => 2,
                'title' => 'Application Submitted',
                'description' => 'Your application was successfully submitted for review',
                'timestamp' => Carbon::parse($completedAt)->format('F j, Y g:i A'),
                'status' => 'completed',
                'details' => 'All required information and documents have been submitted',
                'actionRequired' => false,
            ];
        } else {
            $timeline[] = [
                'id' => 2,
                'title' => 'Application Submission',
                'description' => 'Complete and submit your application',
                'timestamp' => 'Pending',
                'status' => 'pending',
                'details' => 'Please complete all required fields and upload necessary documents',
                'actionRequired' => true,
            ];
        }

        // Document Verification (if applicable)
        if (isset($metadata['documents_verified']) || in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed'])) {
            $timeline[] = [
                'id' => 3,
                'title' => 'Document Verification',
                'description' => isset($metadata['documents_verified'])
                    ? 'Documents have been verified'
                    : 'Documents are being verified',
                'timestamp' => isset($metadata['documents_verified_at'])
                    ? Carbon::parse($metadata['documents_verified_at'])->format('F j, Y g:i A')
                    : (in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed']) ? 'In Progress' : 'Pending'),
                'status' => isset($metadata['documents_verified'])
                    ? 'completed'
                    : (in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed']) ? 'current' : 'pending'),
                'details' => 'Verification of identity documents, income proof, and other required documents',
                'actionRequired' => false,
            ];
        }

        // Credit Assessment (if applicable)
        if (isset($metadata['credit_check_completed']) || in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed'])) {
            $timeline[] = [
                'id' => 4,
                'title' => 'Credit Assessment',
                'description' => isset($metadata['credit_check_completed'])
                    ? 'Credit assessment completed'
                    : 'Credit assessment in progress',
                'timestamp' => isset($metadata['credit_check_completed_at'])
                    ? Carbon::parse($metadata['credit_check_completed_at'])->format('F j, Y g:i A')
                    : (in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed']) ? 'In Progress' : 'Pending'),
                'status' => isset($metadata['credit_check_completed'])
                    ? 'completed'
                    : (in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed']) ? 'current' : 'pending'),
                'details' => 'Evaluation of creditworthiness and financial capacity',
                'actionRequired' => false,
            ];
        }

        // Under Review / Committee Review
        if (in_array($currentStatus, ['under_review', 'approved', 'rejected', 'completed'])) {
            $timeline[] = [
                'id' => 5,
                'title' => isset($metadata['committee_review_started']) ? 'Committee Review' : 'Under Review',
                'description' => isset($metadata['committee_review_started'])
                    ? 'Application is being reviewed by approval committee'
                    : 'Your application is being reviewed by our team',
                'timestamp' => isset($metadata['committee_review_started_at'])
                    ? Carbon::parse($metadata['committee_review_started_at'])->format('F j, Y g:i A')
                    : (isset($metadata['review_started_at'])
                        ? Carbon::parse($metadata['review_started_at'])->format('F j, Y g:i A')
                        : 'In Progress'),
                'status' => $currentStatus === 'under_review' ? 'current' : 'completed',
                'details' => 'Comprehensive review of application, documents, and creditworthiness',
                'actionRequired' => false,
            ];
        } else {
            $timeline[] = [
                'id' => 5,
                'title' => 'Review Process',
                'description' => 'Your application will be reviewed once submitted',
                'timestamp' => 'Pending',
                'status' => 'pending',
                'details' => 'Application will undergo thorough review process',
                'actionRequired' => false,
            ];
        }

        // Decision Made
        if (in_array($currentStatus, ['approved', 'rejected', 'completed'])) {
            $decisionTitle = $currentStatus === 'approved' ? 'Application Approved' : 'Application Decision';
            $decisionDescription = $currentStatus === 'approved'
                ? 'Your loan application has been approved'
                : ($currentStatus === 'rejected'
                    ? 'Application requires additional review'
                    : 'A decision has been made on your application');

            $timeline[] = [
                'id' => 6,
                'title' => $decisionTitle,
                'description' => $decisionDescription,
                'timestamp' => isset($metadata['status_updated_at'])
                    ? Carbon::parse($metadata['status_updated_at'])->format('F j, Y g:i A')
                    : 'Completed',
                'status' => 'completed',
                'details' => $currentStatus === 'approved'
                    ? 'Congratulations! Your application has been approved for processing'
                    : ($currentStatus === 'rejected'
                        ? 'Please review the feedback and consider reapplying'
                        : 'Decision has been communicated'),
                'actionRequired' => $currentStatus === 'rejected',
            ];
        } else {
            $timeline[] = [
                'id' => 6,
                'title' => 'Application Decision',
                'description' => 'Decision pending on your application',
                'timestamp' => 'Pending',
                'status' => 'pending',
                'details' => 'Final decision will be communicated once review is complete',
                'actionRequired' => false,
            ];
        }

        // Disbursement (for approved applications)
        if ($currentStatus === 'approved' || $currentStatus === 'completed') {
            $disbursementStatus = $currentStatus === 'completed' ? 'completed' : 'current';
            $timeline[] = [
                'id' => 7,
                'title' => 'Loan Disbursement',
                'description' => $disbursementStatus === 'completed'
                    ? 'Funds have been disbursed'
                    : 'Funds will be disbursed as per approval',
                'timestamp' => isset($metadata['disbursed_at'])
                    ? Carbon::parse($metadata['disbursed_at'])->format('F j, Y g:i A')
                    : (isset($metadata['approval_details']['disbursement_date'])
                        ? 'Expected: ' . Carbon::parse($metadata['approval_details']['disbursement_date'])->format('F j, Y')
                        : 'Processing'),
                'status' => $disbursementStatus,
                'details' => $disbursementStatus === 'completed'
                    ? 'Loan amount has been successfully transferred to your account'
                    : 'Disbursement process is being prepared',
                'actionRequired' => false,
            ];
        }

        // Product Delivery (for completed applications)
        if ($currentStatus === 'completed') {
            $timeline[] = [
                'id' => 8,
                'title' => 'Product Delivery',
                'description' => 'Track your product delivery',
                'timestamp' => isset($metadata['delivery_started_at'])
                    ? Carbon::parse($metadata['delivery_started_at'])->format('F j, Y g:i A')
                    : 'In Progress',
                'status' => isset($metadata['delivered_at']) ? 'completed' : 'current',
                'details' => isset($metadata['delivered_at'])
                    ? 'Product has been successfully delivered'
                    : 'Your product is being prepared for delivery',
                'actionRequired' => false,
            ];
        }

        return $timeline;
    }

    /**
     * Calculate progress percentage based on status and timeline
     */
    private function calculateProgressPercentage(string $status, array $timeline): int
    {
        $completedSteps = count(array_filter($timeline, fn($event) => $event['status'] === 'completed'));
        $totalSteps = count($timeline);

        switch ($status) {
            case 'pending':
                return max(20, intval(($completedSteps / $totalSteps) * 100));
            case 'under_review':
                return max(40, intval(($completedSteps / $totalSteps) * 100));
            case 'approved':
                return max(80, intval(($completedSteps / $totalSteps) * 100));
            case 'completed':
                return 100;
            case 'rejected':
                return intval(($completedSteps / $totalSteps) * 100);
            default:
                return 0;
        }
    }

    /**
     * Get estimated completion date based on status
     */
    private function getEstimatedCompletionDate(string $status): ?string
    {
        $now = Carbon::now();

        switch ($status) {
            case 'pending':
                return $now->addDays(7)->format('F j, Y');
            case 'under_review':
                return $now->addDays(3)->format('F j, Y');
            case 'approved':
                return $now->addDays(1)->format('F j, Y');
            default:
                return null;
        }
    }

    /**
     * Get next action description based on status
     */
    private function getNextAction(string $status): ?string
    {
        switch ($status) {
            case 'pending':
                return 'Your application is in queue for review';
            case 'under_review':
                return 'Our team is currently reviewing your application';
            case 'approved':
                return 'Prepare for loan disbursement';
            case 'rejected':
                return 'You may submit a new application';
            case 'completed':
                return 'Track your product delivery';
            default:
                return null;
        }
    }

    /**
     * Get real-time status updates (Server-Sent Events endpoint)
     */
    public function getStatusUpdates(string $reference): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Look up application by session ID or reference code (national ID)
        $application = null;

        // Sanitize the reference
        $reference = strtoupper(trim($reference));

        // Try to find by reference code (national ID)
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        // If not found by reference code, try by session ID
        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        return response()->stream(function () use ($application) {
            // Set headers for Server-Sent Events
            echo "data: " . json_encode([
                'type' => 'connected',
                'session_id' => $application->session_id,
                'timestamp' => now()->toIso8601String()
            ]) . "\n\n";

            // Send initial status
            $status = $this->determineApplicationStatus($application);
            echo "data: " . json_encode([
                'type' => 'status_update',
                'status' => $status,
                'progress' => $this->calculateProgressPercentage($status, $this->buildApplicationTimeline($application, $status)),
                'timestamp' => now()->toIso8601String()
            ]) . "\n\n";

            // Keep connection alive and check for updates
            $lastUpdate = $application->updated_at;
            while (true) {
                // Check if application has been updated
                $application->refresh();
                if ($application->updated_at > $lastUpdate) {
                    $newStatus = $this->determineApplicationStatus($application);
                    echo "data: " . json_encode([
                        'type' => 'status_update',
                        'status' => $newStatus,
                        'progress' => $this->calculateProgressPercentage($newStatus, $this->buildApplicationTimeline($application, $newStatus)),
                        'timestamp' => $application->updated_at->toIso8601String()
                    ]) . "\n\n";
                    $lastUpdate = $application->updated_at;
                }

                // Send heartbeat every 30 seconds
                echo "data: " . json_encode([
                    'type' => 'heartbeat',
                    'timestamp' => now()->toIso8601String()
                ]) . "\n\n";

                ob_flush();
                flush();
                sleep(30);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Get detailed progress information
     */
    public function getProgressDetails(string $reference): JsonResponse
    {
        // Look up application by session ID or reference code (national ID)
        $application = null;

        // Sanitize the reference
        $reference = strtoupper(trim($reference));

        // Try to find by reference code (national ID)
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        // If not found by reference code, try by session ID
        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $status = $this->determineApplicationStatus($application);

        // Build detailed progress information
        $progressDetails = [
            'currentStage' => $this->getCurrentStage($status, $metadata),
            'completedMilestones' => $this->getCompletedMilestones($application, $metadata),
            'upcomingMilestones' => $this->getUpcomingMilestones($status, $metadata),
            'estimatedTimeRemaining' => $this->getEstimatedTimeRemaining($status, $metadata),
            'actionItems' => $this->getActionItems($application, $status, $metadata),
        ];

        return response()->json($progressDetails);
    }

    /**
     * Get current stage information
     */
    private function getCurrentStage(string $status, array $metadata): array
    {
        $stages = [
            'pending' => [
                'name' => 'Initial Review',
                'description' => 'Your application is being prepared for review',
                'icon' => 'clock',
                'color' => 'yellow'
            ],
            'under_review' => [
                'name' => 'Under Review',
                'description' => 'Our team is evaluating your application',
                'icon' => 'search',
                'color' => 'blue'
            ],
            'approved' => [
                'name' => 'Approved',
                'description' => 'Your application has been approved',
                'icon' => 'check-circle',
                'color' => 'green'
            ],
            'rejected' => [
                'name' => 'Requires Review',
                'description' => 'Your application needs additional attention',
                'icon' => 'alert-circle',
                'color' => 'red'
            ],
            'completed' => [
                'name' => 'Completed',
                'description' => 'Your loan has been processed and disbursed',
                'icon' => 'package',
                'color' => 'emerald'
            ]
        ];

        return $stages[$status] ?? $stages['pending'];
    }

    /**
     * Get completed milestones
     */
    private function getCompletedMilestones(ApplicationState $application, array $metadata): array
    {
        $milestones = [];

        // Application submitted
        if ($application->current_step === 'completed') {
            $milestones[] = [
                'name' => 'Application Submitted',
                'completedAt' => $metadata['completed_at'] ?? $application->updated_at->toIso8601String(),
                'description' => 'All required information provided'
            ];
        }

        // Documents verified
        if (isset($metadata['documents_verified'])) {
            $milestones[] = [
                'name' => 'Documents Verified',
                'completedAt' => $metadata['documents_verified_at'],
                'description' => 'All documents have been verified'
            ];
        }

        // Credit check completed
        if (isset($metadata['credit_check_completed'])) {
            $milestones[] = [
                'name' => 'Credit Assessment',
                'completedAt' => $metadata['credit_check_completed_at'],
                'description' => 'Credit evaluation completed'
            ];
        }

        return $milestones;
    }

    /**
     * Get upcoming milestones
     */
    private function getUpcomingMilestones(string $status, array $metadata): array
    {
        $upcoming = [];

        switch ($status) {
            case 'pending':
                $upcoming[] = [
                    'name' => 'Document Verification',
                    'estimatedDate' => Carbon::now()->addDays(1)->format('Y-m-d'),
                    'description' => 'Verification of submitted documents'
                ];
                $upcoming[] = [
                    'name' => 'Credit Assessment',
                    'estimatedDate' => Carbon::now()->addDays(2)->format('Y-m-d'),
                    'description' => 'Evaluation of creditworthiness'
                ];
                break;

            case 'under_review':
                if (!isset($metadata['credit_check_completed'])) {
                    $upcoming[] = [
                        'name' => 'Credit Assessment',
                        'estimatedDate' => Carbon::now()->addDays(1)->format('Y-m-d'),
                        'description' => 'Evaluation of creditworthiness'
                    ];
                }
                $upcoming[] = [
                    'name' => 'Final Decision',
                    'estimatedDate' => Carbon::now()->addDays(2)->format('Y-m-d'),
                    'description' => 'Approval committee decision'
                ];
                break;

            case 'approved':
                $upcoming[] = [
                    'name' => 'Loan Disbursement',
                    'estimatedDate' => isset($metadata['approval_details']['disbursement_date'])
                        ? $metadata['approval_details']['disbursement_date']
                        : Carbon::now()->addDays(1)->format('Y-m-d'),
                    'description' => 'Transfer of approved loan amount'
                ];
                break;
        }

        return $upcoming;
    }

    /**
     * Get estimated time remaining
     */
    private function getEstimatedTimeRemaining(string $status, array $metadata): ?array
    {
        switch ($status) {
            case 'pending':
                return [
                    'days' => 5,
                    'description' => 'Estimated time to decision'
                ];
            case 'under_review':
                return [
                    'days' => 3,
                    'description' => 'Estimated time to decision'
                ];
            case 'approved':
                return [
                    'days' => 1,
                    'description' => 'Estimated time to disbursement'
                ];
            default:
                return null;
        }
    }

    /**
     * Get action items for the applicant
     */
    private function getActionItems(ApplicationState $application, string $status, array $metadata): array
    {
        $actionItems = [];

        // Check if application is incomplete
        if ($application->current_step !== 'completed') {
            $actionItems[] = [
                'type' => 'required',
                'title' => 'Complete Application',
                'description' => 'Please complete all required sections of your application',
                'priority' => 'high',
                'dueDate' => Carbon::now()->addDays(7)->format('Y-m-d')
            ];
        }

        // Check for missing documents
        if (!isset($metadata['documents_verified']) && $status !== 'rejected') {
            $actionItems[] = [
                'type' => 'optional',
                'title' => 'Document Verification',
                'description' => 'Ensure all required documents are uploaded and clear',
                'priority' => 'medium',
                'dueDate' => null
            ];
        }

        // Rejection follow-up
        if ($status === 'rejected') {
            $actionItems[] = [
                'type' => 'suggested',
                'title' => 'Review Feedback',
                'description' => 'Review the rejection reason and consider reapplying with additional information',
                'priority' => 'medium',
                'dueDate' => null
            ];
        }

        return $actionItems;
    }

    /**
     * Get application analytics and insights
     */
    public function getApplicationInsights(string $reference): JsonResponse
    {
        // Look up application by session ID or reference code (national ID)
        $application = null;

        // Sanitize the reference
        $reference = strtoupper(trim($reference));

        // Try to find by reference code (national ID)
        if (ctype_alnum($reference)) {
            $application = $this->referenceCodeService->getStateByReferenceCode($reference);
        }

        // If not found by reference code, try by session ID
        if (!$application) {
            $application = ApplicationState::where('session_id', $reference)->first();
        }

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $status = $this->determineApplicationStatus($application);

        // Calculate processing time insights
        $processingInsights = $this->calculateProcessingInsights($application, $metadata);

        // Get comparison with similar applications
        $benchmarkData = $this->getBenchmarkData($application);

        // Get risk assessment insights
        $riskInsights = $this->getRiskInsights($application, $metadata);

        return response()->json([
            'processing_insights' => $processingInsights,
            'benchmark_data' => $benchmarkData,
            'risk_insights' => $riskInsights,
            'recommendations' => $this->getRecommendations($application, $status, $metadata),
            'satisfaction_metrics' => $this->getSatisfactionMetrics($application),
        ]);
    }

    /**
     * Calculate processing time insights
     */
    private function calculateProcessingInsights(ApplicationState $application, array $metadata): array
    {
        $insights = [];
        $now = Carbon::now();
        $submittedAt = $application->created_at;

        // Total processing time
        $totalProcessingTime = $submittedAt->diffInHours($now);
        $insights['total_processing_hours'] = $totalProcessingTime;
        $insights['total_processing_days'] = round($totalProcessingTime / 24, 1);

        // Time spent in each stage
        $stageTimings = [];

        if (isset($metadata['documents_verified_at'])) {
            $docVerificationTime = $submittedAt->diffInHours(Carbon::parse($metadata['documents_verified_at']));
            $stageTimings['document_verification'] = $docVerificationTime;
        }

        if (isset($metadata['credit_check_completed_at'])) {
            $creditCheckTime = isset($metadata['documents_verified_at'])
                ? Carbon::parse($metadata['documents_verified_at'])->diffInHours(Carbon::parse($metadata['credit_check_completed_at']))
                : $submittedAt->diffInHours(Carbon::parse($metadata['credit_check_completed_at']));
            $stageTimings['credit_assessment'] = $creditCheckTime;
        }

        if (isset($metadata['status_updated_at'])) {
            $decisionTime = isset($metadata['credit_check_completed_at'])
                ? Carbon::parse($metadata['credit_check_completed_at'])->diffInHours(Carbon::parse($metadata['status_updated_at']))
                : $submittedAt->diffInHours(Carbon::parse($metadata['status_updated_at']));
            $stageTimings['decision_making'] = $decisionTime;
        }

        $insights['stage_timings'] = $stageTimings;

        // Performance indicators
        $insights['performance'] = [
            'is_fast_track' => $totalProcessingTime < 48, // Less than 2 days
            'is_standard' => $totalProcessingTime >= 48 && $totalProcessingTime <= 120, // 2-5 days
            'is_extended' => $totalProcessingTime > 120, // More than 5 days
            'efficiency_score' => min(100, max(0, 100 - ($totalProcessingTime / 24) * 10)), // Score out of 100
        ];

        return $insights;
    }

    /**
     * Get benchmark data compared to similar applications
     */
    private function getBenchmarkData(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $loanAmount = $formData['amount'] ?? 0;
        $business = $formData['business'] ?? '';

        // In a real implementation, this would query the database for similar applications
        // For now, we'll return mock benchmark data
        return [
            'similar_applications' => [
                'count' => 150,
                'average_processing_time' => 72, // hours
                'approval_rate' => 78.5, // percentage
                'average_loan_amount' => 45000,
            ],
            'your_application' => [
                'processing_time_percentile' => 65, // Your app is faster than 65% of similar apps
                'loan_amount_percentile' => 80, // Your loan amount is higher than 80% of similar apps
            ],
            'industry_benchmarks' => [
                'average_approval_time' => 96, // hours
                'industry_approval_rate' => 72.3, // percentage
                'customer_satisfaction' => 4.2, // out of 5
            ],
        ];
    }

    /**
     * Get risk assessment insights
     */
    private function getRiskInsights(ApplicationState $application, array $metadata): array
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];

        // Calculate risk factors (mock implementation)
        $riskFactors = [];

        // Credit history factor
        if (isset($metadata['credit_score'])) {
            $creditScore = $metadata['credit_score'];
            $riskFactors['credit_history'] = [
                'score' => $creditScore,
                'risk_level' => $creditScore > 700 ? 'low' : ($creditScore > 600 ? 'medium' : 'high'),
                'impact' => 'high',
            ];
        }

        // Income stability factor
        $monthlyIncome = $formResponses['monthlyIncome'] ?? 0;
        $loanAmount = $formData['amount'] ?? 0;
        $debtToIncomeRatio = $monthlyIncome > 0 ? ($loanAmount / 12) / $monthlyIncome : 0;

        $riskFactors['debt_to_income'] = [
            'ratio' => round($debtToIncomeRatio * 100, 2),
            'risk_level' => $debtToIncomeRatio < 0.3 ? 'low' : ($debtToIncomeRatio < 0.5 ? 'medium' : 'high'),
            'impact' => 'high',
        ];

        // Business type factor
        $businessType = $formData['business'] ?? '';
        $highRiskBusinesses = ['restaurant', 'retail', 'construction'];
        $isHighRiskBusiness = false;
        foreach ($highRiskBusinesses as $riskBusiness) {
            if (stripos($businessType, $riskBusiness) !== false) {
                $isHighRiskBusiness = true;
                break;
            }
        }

        $riskFactors['business_type'] = [
            'type' => $businessType,
            'risk_level' => $isHighRiskBusiness ? 'high' : 'medium',
            'impact' => 'medium',
        ];

        // Overall risk assessment
        $highRiskCount = count(array_filter($riskFactors, fn($factor) => $factor['risk_level'] === 'high'));
        $mediumRiskCount = count(array_filter($riskFactors, fn($factor) => $factor['risk_level'] === 'medium'));

        $overallRisk = 'low';
        if ($highRiskCount > 1) {
            $overallRisk = 'high';
        } elseif ($highRiskCount === 1 || $mediumRiskCount > 2) {
            $overallRisk = 'medium';
        }

        return [
            'overall_risk' => $overallRisk,
            'risk_score' => $this->calculateRiskScore($riskFactors),
            'risk_factors' => $riskFactors,
            'mitigation_suggestions' => $this->getRiskMitigationSuggestions($riskFactors),
        ];
    }

    /**
     * Calculate overall risk score
     */
    private function calculateRiskScore(array $riskFactors): int
    {
        $score = 100; // Start with perfect score

        foreach ($riskFactors as $factor) {
            $impact = $factor['impact'] === 'high' ? 30 : ($factor['impact'] === 'medium' ? 20 : 10);
            $riskLevel = $factor['risk_level'] === 'high' ? 1.0 : ($factor['risk_level'] === 'medium' ? 0.5 : 0.1);

            $score -= ($impact * $riskLevel);
        }

        return max(0, min(100, intval($score)));
    }

    /**
     * Get risk mitigation suggestions
     */
    private function getRiskMitigationSuggestions(array $riskFactors): array
    {
        $suggestions = [];

        foreach ($riskFactors as $key => $factor) {
            if ($factor['risk_level'] === 'high') {
                switch ($key) {
                    case 'credit_history':
                        $suggestions[] = 'Consider providing additional collateral or a co-signer to offset credit risk';
                        break;
                    case 'debt_to_income':
                        $suggestions[] = 'Consider reducing loan amount or extending repayment term to improve debt-to-income ratio';
                        break;
                    case 'business_type':
                        $suggestions[] = 'Provide detailed business plan and cash flow projections to demonstrate stability';
                        break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get recommendations for the applicant
     */
    private function getRecommendations(ApplicationState $application, string $status, array $metadata): array
    {
        $recommendations = [];

        switch ($status) {
            case 'pending':
                $recommendations[] = [
                    'type' => 'action',
                    'title' => 'Complete Your Application',
                    'description' => 'Ensure all required fields are filled and documents are uploaded',
                    'priority' => 'high',
                ];
                break;

            case 'under_review':
                $recommendations[] = [
                    'type' => 'info',
                    'title' => 'Stay Available',
                    'description' => 'Our team may contact you for additional information during review',
                    'priority' => 'medium',
                ];
                break;

            case 'approved':
                $recommendations[] = [
                    'type' => 'action',
                    'title' => 'Prepare for Disbursement',
                    'description' => 'Ensure your bank account details are correct for loan disbursement',
                    'priority' => 'high',
                ];
                break;

            case 'rejected':
                $recommendations[] = [
                    'type' => 'action',
                    'title' => 'Review Feedback',
                    'description' => 'Consider addressing the rejection reasons before reapplying',
                    'priority' => 'high',
                ];
                break;
        }

        // Add general recommendations
        if (!isset($metadata['documents_verified'])) {
            $recommendations[] = [
                'type' => 'tip',
                'title' => 'Document Quality',
                'description' => 'Ensure all uploaded documents are clear and legible',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    /**
     * Get satisfaction metrics
     */
    private function getSatisfactionMetrics(ApplicationState $application): array
    {
        // In a real implementation, this would be based on user feedback
        // For now, we'll return mock data
        return [
            'process_rating' => 4.3,
            'communication_rating' => 4.1,
            'speed_rating' => 3.9,
            'overall_rating' => 4.1,
            'feedback_count' => 1247,
            'would_recommend' => 87.5, // percentage
        ];
    }

    /**
     * Generate notifications based on application status
     */
    private function generateNotifications(ApplicationState $application, string $status): array
    {
        $notifications = [];
        $metadata = $application->metadata ?? [];

        // Get stored notifications from metadata first
        $storedNotifications = $metadata['notifications'] ?? [];

        // Add stored notifications
        foreach ($storedNotifications as $notification) {
            $notifications[] = $notification;
        }

        // Add status-based notifications if not already present
        $existingIds = array_column($notifications, 'id');

        if ($status === 'approved' && !in_array('approval', $existingIds)) {
            $approvalDetails = $metadata['approval_details'] ?? [];
            $notifications[] = [
                'id' => 'approval',
                'type' => 'success',
                'title' => 'Application Approved!',
                'message' => 'Your loan application has been approved for $' . ($approvalDetails['amount'] ?? 'N/A'),
                'timestamp' => $metadata['status_updated_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'high'
            ];
        }

        if ($status === 'under_review' && !in_array('review', $existingIds)) {
            $notifications[] = [
                'id' => 'review',
                'type' => 'info',
                'title' => 'Application Under Review',
                'message' => 'Our team is currently reviewing your application. We may contact you if additional information is needed.',
                'timestamp' => $metadata['status_updated_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'medium'
            ];
        }

        if ($status === 'rejected' && !in_array('rejection', $existingIds)) {
            $notifications[] = [
                'id' => 'rejection',
                'type' => 'error',
                'title' => 'Application Decision',
                'message' => 'Unfortunately, your application was not approved. ' . ($metadata['rejection_reason'] ?? 'Please contact us for more details.'),
                'timestamp' => $metadata['status_updated_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'high'
            ];
        }

        if ($status === 'completed' && !in_array('completion', $existingIds)) {
            $notifications[] = [
                'id' => 'completion',
                'type' => 'success',
                'title' => 'Loan Disbursed!',
                'message' => 'Your loan has been successfully disbursed. You can now track product delivery.',
                'timestamp' => $metadata['status_updated_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'high'
            ];
        }

        // Add progress milestone notifications
        $this->addProgressMilestoneNotifications($notifications, $application, $status);

        // Sort notifications by timestamp (newest first) and priority
        usort($notifications, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $aPriority = $priorityOrder[$a['priority'] ?? 'low'];
            $bPriority = $priorityOrder[$b['priority'] ?? 'low'];

            if ($aPriority !== $bPriority) {
                return $bPriority - $aPriority; // Higher priority first
            }

            return strtotime($b['timestamp']) - strtotime($a['timestamp']); // Newer first
        });

        return array_slice($notifications, 0, 10); // Limit to 10 most recent/important
    }

    /**
     * Add progress milestone notifications
     */
    private function addProgressMilestoneNotifications(array &$notifications, ApplicationState $application, string $status): void
    {
        $metadata = $application->metadata ?? [];
        $existingIds = array_column($notifications, 'id');

        // Document verification milestone
        if (isset($metadata['documents_verified']) && !in_array('docs_verified', $existingIds)) {
            $notifications[] = [
                'id' => 'docs_verified',
                'type' => 'success',
                'title' => 'Documents Verified',
                'message' => 'All your submitted documents have been verified successfully.',
                'timestamp' => $metadata['documents_verified_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'medium'
            ];
        }

        // Credit check milestone
        if (isset($metadata['credit_check_completed']) && !in_array('credit_check', $existingIds)) {
            $notifications[] = [
                'id' => 'credit_check',
                'type' => 'info',
                'title' => 'Credit Check Completed',
                'message' => 'Your credit assessment has been completed as part of the review process.',
                'timestamp' => $metadata['credit_check_completed_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'medium'
            ];
        }

        // Approval committee review
        if (isset($metadata['committee_review_started']) && !in_array('committee_review', $existingIds)) {
            $notifications[] = [
                'id' => 'committee_review',
                'type' => 'info',
                'title' => 'Committee Review',
                'message' => 'Your application is now being reviewed by our approval committee.',
                'timestamp' => $metadata['committee_review_started_at'] ?? $application->updated_at->toIso8601String(),
                'read' => false,
                'priority' => 'medium'
            ];
        }
    }
}

