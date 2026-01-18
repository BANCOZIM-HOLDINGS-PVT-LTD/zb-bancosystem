<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\DeliveryTracking;
use App\Services\NotificationService;
use App\Services\PDFGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApplicationWorkflowService
{
    protected NotificationService $notificationService;
    protected PDFGeneratorService $pdfService;

    public function __construct(
        NotificationService $notificationService,
        PDFGeneratorService $pdfService
    ) {
        $this->notificationService = $notificationService;
        $this->pdfService = $pdfService;
    }

    /**
     * Process application approval with full workflow
     */
    public function approveApplication(ApplicationState $application, array $options = []): bool
    {
        return DB::transaction(function () use ($application, $options) {
            $oldStatus = $application->current_step;
            
            // Update application status
            $application->update([
                'current_step' => 'approved',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
            ]);

            // Create state transition record
            $this->createStateTransition($application, $oldStatus, 'approved', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name ?? 'System',
                'approval_notes' => $options['notes'] ?? null,
                'auto_approved' => $options['auto_approved'] ?? false,
            ]);

            // Generate PDF if not already generated
            if (!$application->pdf_path) {
                $this->generateApplicationPDF($application);
            }

            // Calculate and create commission for referring agent
            $this->processAgentCommission($application);

            // Create delivery tracking record
            $this->createDeliveryTracking($application);

            // Send notifications
            $this->notificationService->sendStatusUpdateNotification($application, $oldStatus, 'approved');

            // Log the approval
            Log::info('Application approved', [
                'session_id' => $application->session_id,
                'reference_code' => $application->reference_code,
                'admin_id' => auth()->id(),
                'auto_approved' => $options['auto_approved'] ?? false,
            ]);

            return true;
        });
    }

    /**
     * Process application rejection with full workflow
     */
    public function rejectApplication(ApplicationState $application, string $reason, array $options = []): bool
    {
        return DB::transaction(function () use ($application, $reason, $options) {
            $oldStatus = $application->current_step;
            
            // Update application status
            $application->update([
                'current_step' => 'rejected',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
            ]);

            // Create state transition record
            $this->createStateTransition($application, $oldStatus, 'rejected', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name ?? 'System',
                'rejection_reason' => $reason,
                'rejection_category' => $options['category'] ?? 'other',
                'auto_rejected' => $options['auto_rejected'] ?? false,
            ]);

            // Send notifications
            $this->notificationService->sendStatusUpdateNotification($application, $oldStatus, 'rejected');

            // Log the rejection
            Log::info('Application rejected', [
                'session_id' => $application->session_id,
                'reference_code' => $application->reference_code,
                'admin_id' => auth()->id(),
                'rejection_reason' => $reason,
                'auto_rejected' => $options['auto_rejected'] ?? false,
            ]);

            return true;
        });
    }

    /**
     * Request additional documents from applicant
     */
    public function requestDocuments(ApplicationState $application, array $documentRequests): bool
    {
        return DB::transaction(function () use ($application, $documentRequests) {
            $oldStatus = $application->current_step;
            
            // Update application status
            $application->update([
                'current_step' => 'pending_documents',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
            ]);

            // Create state transition record
            $this->createStateTransition($application, $oldStatus, 'pending_documents', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name ?? 'System',
                'document_requests' => $documentRequests,
                'request_date' => now()->toISOString(),
            ]);

            // Send notifications
            $this->notificationService->sendDocumentRequestNotification($application, $documentRequests);

            // Log the document request
            Log::info('Documents requested', [
                'session_id' => $application->session_id,
                'reference_code' => $application->reference_code,
                'admin_id' => auth()->id(),
                'documents_requested' => $documentRequests,
            ]);

            return true;
        });
    }

    /**
     * Calculate application score based on various factors
     */
    public function calculateApplicationScore(ApplicationState $application): array
    {
        $score = 0;
        $factors = [];

        // Basic information completeness (0-30 points)
        $completeness = $this->calculateCompletenessScore($application);
        $score += $completeness['score'];
        $factors['completeness'] = $completeness;

        // Financial factors (0-40 points)
        $financial = $this->calculateFinancialScore($application);
        $score += $financial['score'];
        $factors['financial'] = $financial;

        // Agent referral bonus (0-10 points)
        $referral = $this->calculateReferralScore($application);
        $score += $referral['score'];
        $factors['referral'] = $referral;

        // Risk factors (0-20 points)
        $risk = $this->calculateRiskScore($application);
        $score += $risk['score'];
        $factors['risk'] = $risk;

        return [
            'total_score' => min($score, 100), // Cap at 100
            'grade' => $this->getScoreGrade($score),
            'recommendation' => $this->getScoreRecommendation($score),
            'factors' => $factors,
        ];
    }

    /**
     * Process bulk applications with specified action
     */
    public function processBulkApplications(array $applicationIds, string $action, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($applicationIds),
        ];

        foreach ($applicationIds as $id) {
            try {
                $application = ApplicationState::findOrFail($id);
                
                switch ($action) {
                    case 'approve':
                        $this->approveApplication($application, $options);
                        break;
                    case 'reject':
                        $this->rejectApplication($application, $options['reason'] ?? 'Bulk rejection', $options);
                        break;
                    case 'request_documents':
                        $this->requestDocuments($application, $options['documents'] ?? []);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unknown action: {$action}");
                }
                
                $results['success'][] = $id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Bulk application processing failed', [
                    'application_id' => $id,
                    'action' => $action,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Create state transition record
     */
    private function createStateTransition(ApplicationState $application, string $fromStep, string $toStep, array $data = []): void
    {
        $application->transitions()->create([
            'from_step' => $fromStep,
            'to_step' => $toStep,
            'channel' => 'admin',
            'transition_data' => array_merge($data, [
                'ip_address' => request()->ip(),
                'timestamp' => now()->toIso8601String(),
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Generate PDF for application
     */
    private function generateApplicationPDF(ApplicationState $application): void
    {
        try {
            $pdfPath = $this->pdfService->generateApplicationPDF($application);
            $application->update(['pdf_path' => $pdfPath]);
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'session_id' => $application->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process agent commission for approved application
     */
    private function processAgentCommission(ApplicationState $application): void
    {
        // Check if application has agent referral
        $agentId = $application->form_data['agentId'] ?? null;
        if (!$agentId) {
            return;
        }

        $agent = Agent::find($agentId);
        if (!$agent || !$agent->isActive()) {
            return;
        }

        // Calculate commission amount
        $loanAmount = $application->form_data['finalPrice'] ?? 0;
        $commissionRate = $agent->commission_rate ?? 0;
        $commissionAmount = ($loanAmount * $commissionRate) / 100;

        if ($commissionAmount > 0) {
            Commission::create([
                'agent_id' => $agent->id,
                'application_id' => $application->id,
                'amount' => $commissionAmount,
                'rate' => $commissionRate,
                'base_amount' => $loanAmount,
                'status' => 'pending',
                'earned_date' => now(),
                'reference_code' => $application->reference_code,
            ]);
        }
    }

    /**
     * Create delivery tracking record for approved application
     */
    private function createDeliveryTracking(ApplicationState $application): void
    {
        try {
            // Check if delivery tracking already exists
            if ($application->delivery()->exists()) {
                return;
            }

            $formData = $application->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            $deliverySelection = $formData['deliverySelection'] ?? [];

            // Extract client information
            $clientName = trim(
                ($formResponses['firstName'] ?? '') . ' ' .
                ($formResponses['surname'] ?? '')
            );
            $clientPhone = $formResponses['mobile'] ?? $formResponses['cellNumber'] ?? '';
            $clientNationalId = $formResponses['nationalIdNumber'] ?? $formResponses['idNumber'] ?? '';

            // Extract product information
            $product = $formData['business'] ?? $formData['category'] ?? 'N/A';

            // Extract delivery information
            $depot = '';
            if (!empty($deliverySelection['city'])) {
                $depot = $deliverySelection['city'] . ' (' . ($deliverySelection['agent'] ?? 'Zim Post Office') . ')';
            } elseif (!empty($deliverySelection['depot'])) {
                $depot = $deliverySelection['depot'];
            }

            // Determine courier type from delivery selection
            $courierType = $deliverySelection['agent'] ?? 'Zim Post Office';

            // Create delivery tracking record
            DeliveryTracking::create([
                'application_state_id' => $application->id,
                'recipient_name' => $clientName,
                'recipient_phone' => $clientPhone,
                'client_national_id' => $clientNationalId,
                'product_type' => $product,
                'delivery_depot' => $depot,
                'courier_type' => $courierType,
                'status' => 'pending',
                'status_history' => json_encode([
                    [
                        'status' => 'pending',
                        'notes' => 'Delivery tracking created automatically upon application approval',
                        'updated_at' => now()->toIso8601String(),
                        'updated_by' => auth()->user()->name ?? 'System',
                    ]
                ]),
            ]);

            Log::info('Delivery tracking created', [
                'application_id' => $application->id,
                'reference_code' => $application->reference_code,
            ]);

        } catch (\Exception $e) {
            Log::error('Delivery tracking creation failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Additional helper methods for scoring would go here...
    private function calculateCompletenessScore(ApplicationState $application): array
    {
        // Implementation for completeness scoring
        return ['score' => 25, 'details' => 'Application is mostly complete'];
    }

    private function calculateFinancialScore(ApplicationState $application): array
    {
        // Implementation for financial scoring
        return ['score' => 30, 'details' => 'Good financial profile'];
    }

    private function calculateReferralScore(ApplicationState $application): array
    {
        // Implementation for referral scoring
        return ['score' => 5, 'details' => 'Agent referral bonus'];
    }

    private function calculateRiskScore(ApplicationState $application): array
    {
        // Implementation for risk scoring
        return ['score' => 15, 'details' => 'Low risk profile'];
    }

    private function getScoreGrade(int $score): string
    {
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    private function getScoreRecommendation(int $score): string
    {
        if ($score >= 80) return 'Highly recommended for approval';
        if ($score >= 70) return 'Recommended for approval';
        if ($score >= 60) return 'Consider for approval with conditions';
        if ($score >= 50) return 'Requires careful review';
        return 'Not recommended for approval';
    }
}
