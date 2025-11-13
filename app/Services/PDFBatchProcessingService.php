<?php

namespace App\Services;

use App\Exceptions\PDF\PDFException;
use App\Exceptions\PDF\PDFGenerationException;
use App\Models\ApplicationState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling batch processing of PDFs
 */
class PDFBatchProcessingService
{
    /**
     * The PDF generator service instance
     *
     * @var PDFGeneratorService
     */
    protected PDFGeneratorService $pdfGenerator;
    
    /**
     * The PDF logging service instance
     *
     * @var PDFLoggingService
     */
    protected PDFLoggingService $logger;
    
    /**
     * Create a new PDF batch processing service instance
     *
     * @param PDFGeneratorService $pdfGenerator The PDF generator service
     * @param PDFLoggingService $logger The PDF logging service
     */
    public function __construct(PDFGeneratorService $pdfGenerator, PDFLoggingService $logger)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->logger = $logger;
    }
    
    /**
     * Process a batch of session IDs for PDF generation
     *
     * @param array $sessionIds Array of session IDs to process
     * @param string $batchId Unique batch identifier
     * @return array Results of processing each session ID
     */
    public function processBatch(array $sessionIds, string $batchId): array
    {
        $results = [];
        $progressKey = "pdf_batch_{$batchId}_progress";
        
        // Initialize progress tracking
        $this->initializeProgressTracking($progressKey, count($sessionIds));
        
        // Process each session ID
        foreach ($sessionIds as $index => $sessionId) {
            try {
                // Find the application state
                $state = ApplicationState::where('session_id', $sessionId)->first();
                
                if (!$state) {
                    throw new PDFException(
                        "Application not found",
                        "APPLICATION_NOT_FOUND",
                        ['session_id' => $sessionId]
                    );
                }
                
                // Allow PDF generation for applications that have enough data
                $allowedSteps = ['completed', 'in_review', 'summary', 'documents'];
                if (!in_array($state->current_step, $allowedSteps)) {
                    throw new PDFException(
                        "Application incomplete",
                        "APPLICATION_INCOMPLETE",
                        ['session_id' => $sessionId, 'current_step' => $state->current_step]
                    );
                }
                
                // Generate PDF
                $pdfPath = $this->pdfGenerator->generateApplicationPDF($state);
                
                // Log successful generation
                $this->logger->logInfo('Batch PDF generated successfully', [
                    'batch_id' => $batchId,
                    'session_id' => $sessionId,
                    'pdf_path' => $pdfPath,
                    'index' => $index + 1,
                    'total' => count($sessionIds)
                ]);
                
                // Add to results
                $results[] = [
                    'status' => 'success',
                    'session_id' => $sessionId,
                    'path' => $pdfPath,
                    'filename' => basename($pdfPath),
                    'applicant_name' => $this->getApplicantName($state),
                    'application_type' => $this->getApplicationType($state)
                ];
                
                // Update progress
                $this->updateProgress($progressKey, $index + 1, 'success');
            } catch (PDFException $e) {
                // Log error
                $this->logger->logError('Batch PDF generation failed for session', [
                    'batch_id' => $batchId,
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'index' => $index + 1,
                    'total' => count($sessionIds)
                ], $e);
                
                // Add to results
                $results[] = [
                    'status' => 'error',
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'message' => $e->getMessage()
                ];
                
                // Update progress
                $this->updateProgress($progressKey, $index + 1, 'error');
            } catch (\Exception $e) {
                // Log unexpected error
                $this->logger->logError('Unexpected error during batch PDF generation', [
                    'batch_id' => $batchId,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'index' => $index + 1,
                    'total' => count($sessionIds)
                ], $e);
                
                // Add to results
                $results[] = [
                    'status' => 'error',
                    'session_id' => $sessionId,
                    'error_code' => 'UNEXPECTED_ERROR',
                    'message' => 'An unexpected error occurred: ' . $e->getMessage()
                ];
                
                // Update progress
                $this->updateProgress($progressKey, $index + 1, 'error');
            }
        }
        
        // Mark batch as completed
        $this->completeProgress($progressKey);
        
        return $results;
    }
    
    /**
     * Initialize progress tracking for a batch
     *
     * @param string $progressKey Cache key for progress tracking
     * @param int $total Total number of items to process
     * @return void
     */
    private function initializeProgressTracking(string $progressKey, int $total): void
    {
        Cache::put($progressKey, [
            'total' => $total,
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'status' => 'processing',
            'started_at' => now()->toISOString(),
        ], 3600); // Cache for 1 hour
    }
    
    /**
     * Update progress tracking for a batch
     *
     * @param string $progressKey Cache key for progress tracking
     * @param int $processed Number of items processed
     * @param string $status Status of the last processed item ('success' or 'error')
     * @return void
     */
    private function updateProgress(string $progressKey, int $processed, string $status): void
    {
        $progress = Cache::get($progressKey, [
            'total' => 0,
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'status' => 'processing',
            'started_at' => now()->toISOString(),
        ]);
        
        $progress['processed'] = $processed;
        
        if ($status === 'success') {
            $progress['successful']++;
        } else {
            $progress['failed']++;
        }
        
        Cache::put($progressKey, $progress, 3600);
    }
    
    /**
     * Mark a batch as completed
     *
     * @param string $progressKey Cache key for progress tracking
     * @return void
     */
    private function completeProgress(string $progressKey): void
    {
        $progress = Cache::get($progressKey);
        
        if ($progress) {
            $progress['status'] = 'completed';
            $progress['completed_at'] = now()->toISOString();
            Cache::put($progressKey, $progress, 3600);
        }
    }
    
    /**
     * Get applicant name from application state
     *
     * @param ApplicationState $state The application state
     * @return string Applicant name
     */
    private function getApplicantName(ApplicationState $state): string
    {
        $formData = $state->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];
        
        $firstName = $responses['firstName'] ?? $responses['first_name'] ?? '';
        $lastName = $responses['lastName'] ?? $responses['surname'] ?? $responses['last_name'] ?? '';
        
        $name = trim("{$firstName} {$lastName}");
        
        return $name ?: 'Unknown';
    }
    
    /**
     * Get application type from application state
     *
     * @param ApplicationState $state The application state
     * @return string Application type
     */
    private function getApplicationType(ApplicationState $state): string
    {
        $formData = $state->form_data ?? [];
        $formId = $formData['formId'] ?? '';
        
        $types = [
            'account_holder_loan_application.json' => 'Account Holder Loan',
            'ssb_account_opening_form.json' => 'SSB Loan',
            'individual_account_opening.json' => 'ZB Account Opening',
            'smes_business_account_opening.json' => 'SME Business Account',
        ];
        
        return $types[$formId] ?? 'Application';
    }
}