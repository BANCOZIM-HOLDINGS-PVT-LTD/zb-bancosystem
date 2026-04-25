<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SME Application Workflow Service
 * 
 * Handles the specific workflow for SME (Business Booster) applications.
 * SME applications follow a different pathway:
 *   Submitted → Document Review → Credit Assessment → Committee Review → Approved/Rejected
 * 
 * This extends the standard approval logic with SME-specific validations,
 * status transitions, and business-type awareness.
 */
class SMEApplicationWorkflowService
{
    protected ApplicationWorkflowService $workflowService;

    /**
     * SME-specific approval stages
     */
    const STAGE_SUBMITTED = 'submitted';
    const STAGE_DOCUMENT_REVIEW = 'sme_document_review';
    const STAGE_CREDIT_ASSESSMENT = 'sme_credit_assessment';
    const STAGE_COMMITTEE_REVIEW = 'sme_committee_review';
    const STAGE_APPROVED = 'approved';
    const STAGE_REJECTED = 'rejected';
    const STAGE_ADDITIONAL_DOCS = 'sme_pending_documents';

    /**
     * Valid SME company types (matching frontend CompanyTypeSelection.tsx)
     */
    const VALID_COMPANY_TYPES = [
        'sole_trader',
        'partnership',
        'private_limited',
        'cooperative',
        'trust',
    ];

    /**
     * Valid status transitions for SME applications
     */
    const STATUS_TRANSITIONS = [
        self::STAGE_SUBMITTED => [self::STAGE_DOCUMENT_REVIEW, self::STAGE_REJECTED],
        self::STAGE_DOCUMENT_REVIEW => [self::STAGE_CREDIT_ASSESSMENT, self::STAGE_ADDITIONAL_DOCS, self::STAGE_REJECTED],
        self::STAGE_CREDIT_ASSESSMENT => [self::STAGE_COMMITTEE_REVIEW, self::STAGE_REJECTED],
        self::STAGE_COMMITTEE_REVIEW => [self::STAGE_APPROVED, self::STAGE_REJECTED],
        self::STAGE_ADDITIONAL_DOCS => [self::STAGE_DOCUMENT_REVIEW, self::STAGE_REJECTED],
    ];

    public function __construct(ApplicationWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Initialize a new SME application with the correct type and metadata.
     * Called when an smeBiz application is submitted.
     */
    public function initializeSMEApplication(ApplicationState $application): void
    {
        $formData = $application->form_data ?? [];
        $companyType = $formData['companyType'] ?? null;
        $companyTypeName = $formData['companyTypeName'] ?? null;

        // Set application type explicitly
        $application->update([
            'application_type' => 'sme',
        ]);

        // Enrich metadata with SME-specific fields
        $metadata = $application->metadata ?? [];
        $metadata['workflow_type'] = 'sme';
        $metadata['company_type'] = $companyType;
        $metadata['company_type_name'] = $companyTypeName;
        $metadata['sme_stage'] = self::STAGE_SUBMITTED;
        $metadata['sme_submitted_at'] = now()->toIso8601String();

        $application->update(['metadata' => $metadata]);

        Log::info('SME application initialized', [
            'session_id' => $application->session_id,
            'reference_code' => $application->reference_code,
            'company_type' => $companyType,
            'application_type' => 'sme',
        ]);
    }

    /**
     * Advance an SME application to the next stage.
     */
    public function advanceToStage(ApplicationState $application, string $targetStage, array $options = []): bool
    {
        $metadata = $application->metadata ?? [];
        $currentStage = $metadata['sme_stage'] ?? self::STAGE_SUBMITTED;

        // Validate transition
        if (!$this->isValidTransition($currentStage, $targetStage)) {
            Log::warning('Invalid SME stage transition attempted', [
                'application_id' => $application->id,
                'current_stage' => $currentStage,
                'target_stage' => $targetStage,
            ]);
            return false;
        }

        return DB::transaction(function () use ($application, $currentStage, $targetStage, $options) {
            // Update metadata with new stage
            $metadata = $application->metadata ?? [];
            $metadata['sme_stage'] = $targetStage;
            $metadata["sme_{$targetStage}_at"] = now()->toIso8601String();
            $metadata["sme_{$targetStage}_by"] = auth()->user()?->name ?? 'System';

            if (!empty($options['notes'])) {
                $metadata["sme_{$targetStage}_notes"] = $options['notes'];
            }

            $application->update(['metadata' => $metadata]);

            // Create state transition
            $application->transitions()->create([
                'from_step' => $currentStage,
                'to_step' => $targetStage,
                'channel' => 'admin',
                'transition_data' => array_merge($options, [
                    'workflow_type' => 'sme',
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()?->name ?? 'System',
                    'timestamp' => now()->toIso8601String(),
                ]),
            ]);

            // Handle terminal states
            if ($targetStage === self::STAGE_APPROVED) {
                return $this->workflowService->approveApplication($application, $options);
            }

            if ($targetStage === self::STAGE_REJECTED) {
                return $this->workflowService->rejectApplication(
                    $application,
                    $options['reason'] ?? 'Application did not meet SME criteria',
                    $options
                );
            }

            Log::info('SME application advanced', [
                'application_id' => $application->id,
                'reference_code' => $application->reference_code,
                'from_stage' => $currentStage,
                'to_stage' => $targetStage,
            ]);

            return true;
        });
    }

    /**
     * Request additional documents for SME application.
     */
    public function requestAdditionalDocuments(ApplicationState $application, array $documentRequests): bool
    {
        return $this->advanceToStage($application, self::STAGE_ADDITIONAL_DOCS, [
            'document_requests' => $documentRequests,
        ]);
    }

    /**
     * Validate an SME application has all required fields.
     */
    public function validateSMEApplication(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $errors = [];

        // Validate company type was selected
        $companyType = $formData['companyType'] ?? null;
        if (!$companyType || !in_array($companyType, self::VALID_COMPANY_TYPES)) {
            $errors[] = 'A valid company type is required for SME applications.';
        }

        // Validate essential business info fields
        $requiredBusinessFields = [
            'businessName' => 'Business Name',
            'businessRegistrationNumber' => 'Business Registration Number',
            'businessAddress' => 'Business Address',
        ];

        foreach ($requiredBusinessFields as $field => $label) {
            if (empty($formResponses[$field])) {
                $errors[] = "{$label} is required.";
            }
        }

        // Validate personal details (from SME form)
        $requiredPersonalFields = [
            'firstName' => 'First Name',
            'surname' => 'Surname',
            'nationalIdNumber' => 'National ID',
            'mobile' => 'Mobile Number',
        ];

        foreach ($requiredPersonalFields as $field => $label) {
            if (empty($formResponses[$field])) {
                $errors[] = "{$label} is required.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get the current SME stage for display.
     */
    public function getCurrentStageLabel(ApplicationState $application): string
    {
        $metadata = $application->metadata ?? [];
        $stage = $metadata['sme_stage'] ?? self::STAGE_SUBMITTED;

        return match ($stage) {
            self::STAGE_SUBMITTED => 'Submitted',
            self::STAGE_DOCUMENT_REVIEW => 'Document Review',
            self::STAGE_CREDIT_ASSESSMENT => 'Credit Assessment',
            self::STAGE_COMMITTEE_REVIEW => 'Committee Review',
            self::STAGE_APPROVED => 'Approved',
            self::STAGE_REJECTED => 'Rejected',
            self::STAGE_ADDITIONAL_DOCS => 'Pending Additional Documents',
            default => ucfirst(str_replace('_', ' ', $stage)),
        };
    }

    /**
     * Get the SME approval timeline for client-facing tracking.
     */
    public function getApprovalTimeline(ApplicationState $application): array
    {
        $metadata = $application->metadata ?? [];
        $currentStage = $metadata['sme_stage'] ?? self::STAGE_SUBMITTED;

        $stages = [
            self::STAGE_SUBMITTED => ['label' => 'Submitted', 'icon' => 'document'],
            self::STAGE_DOCUMENT_REVIEW => ['label' => 'Document Review', 'icon' => 'search'],
            self::STAGE_CREDIT_ASSESSMENT => ['label' => 'Credit Assessment', 'icon' => 'calculator'],
            self::STAGE_COMMITTEE_REVIEW => ['label' => 'Committee Review', 'icon' => 'users'],
            self::STAGE_APPROVED => ['label' => 'Approved', 'icon' => 'check-circle'],
        ];

        $timeline = [];
        $currentReached = false;

        foreach ($stages as $stageKey => $stageInfo) {
            $completedAt = $metadata["sme_{$stageKey}_at"] ?? null;

            $status = 'pending';
            if ($stageKey === $currentStage) {
                $status = 'current';
                $currentReached = true;
            } elseif ($completedAt && !$currentReached) {
                $status = 'completed';
            }

            if ($currentStage === self::STAGE_REJECTED && $stageKey !== self::STAGE_APPROVED) {
                // If rejected, mark current and prior as completed/rejected
                if ($stageKey === $currentStage) {
                    $status = 'rejected';
                }
            }

            $timeline[] = [
                'stage' => $stageKey,
                'label' => $stageInfo['label'],
                'icon' => $stageInfo['icon'],
                'status' => $status,
                'completed_at' => $completedAt,
                'completed_by' => $metadata["sme_{$stageKey}_by"] ?? null,
                'notes' => $metadata["sme_{$stageKey}_notes"] ?? null,
            ];
        }

        return $timeline;
    }

    /**
     * Check if a status transition is valid.
     */
    private function isValidTransition(string $fromStage, string $toStage): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$fromStage] ?? [];
        return in_array($toStage, $allowedTransitions);
    }
}
