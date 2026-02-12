<?php

namespace App\Jobs;

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\Performance\PerformanceMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeneratePDFJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ApplicationState $applicationState;
    public array $options;
    public string $notificationChannel;
    public ?string $callbackUrl;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        ApplicationState $applicationState,
        array $options = [],
        string $notificationChannel = 'database',
        ?string $callbackUrl = null
    ) {
        $this->applicationState = $applicationState;
        $this->options = $options;
        $this->notificationChannel = $notificationChannel;
        $this->callbackUrl = $callbackUrl;
        
        // Set queue based on priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(PDFGeneratorService $pdfGenerator, PerformanceMonitor $monitor): void
    {
        $sessionId = $this->applicationState->session_id;
        
        try {
            // Check if job was cancelled while waiting in queue
            $cacheKey = "pdf_job_status:{$sessionId}";
            $cachedStatus = Cache::get($cacheKey);
            if ($cachedStatus && ($cachedStatus['status'] ?? '') === 'cancelled') {
                Log::info('PDF generation job skipped - was cancelled', [
                    'session_id' => $sessionId,
                ]);
                $this->delete(); // Remove from queue
                return;
            }

            // Set job status to processing
            $this->updateJobStatus('processing');
            
            Log::info('Starting PDF generation job', [
                'session_id' => $sessionId,
                'job_id' => $this->job->getJobId(),
                'options' => $this->options,
            ]);

            // Monitor performance
            $result = $monitor->monitorRequest(function () use ($pdfGenerator) {
                return $pdfGenerator->generatePDF($this->applicationState, $this->options);
            }, 'pdf_generation_job');

            // Update job status to completed
            $this->updateJobStatus('completed', [
                'pdf_info' => $result,
                'performance' => $monitor->getSummary(),
            ]);

            // Send notification
            $this->sendNotification('success', $result);

            // Call webhook if provided
            if ($this->callbackUrl) {
                $this->callWebhook('success', $result);
            }

            Log::info('PDF generation job completed successfully', [
                'session_id' => $sessionId,
                'job_id' => $this->job->getJobId(),
                'pdf_info' => $result,
            ]);

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $sessionId = $this->applicationState->session_id;
        
        Log::error('PDF generation job failed', [
            'session_id' => $sessionId,
            'job_id' => $this->job?->getJobId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // Update job status to failed
        $this->updateJobStatus('failed', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Send failure notification
        $this->sendNotification('failed', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Call webhook if provided
        if ($this->callbackUrl) {
            $this->callWebhook('failed', [
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);
        }
    }

    /**
     * Handle job failure during execution
     */
    private function handleJobFailure(\Exception $e): void
    {
        $sessionId = $this->applicationState->session_id;
        
        Log::error('PDF generation job encountered an error', [
            'session_id' => $sessionId,
            'job_id' => $this->job->getJobId(),
            'error' => $e->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // If this is the last attempt, mark as failed
        if ($this->attempts() >= $this->tries) {
            $this->updateJobStatus('failed', [
                'error' => $e->getMessage(),
                'final_attempt' => true,
            ]);
        } else {
            // Mark as retrying
            $this->updateJobStatus('retrying', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'next_retry' => now()->addMinutes(pow(2, $this->attempts()))->toISOString(),
            ]);
        }

        throw $e; // Re-throw to trigger Laravel's retry mechanism
    }

    /**
     * Update job status in cache
     */
    private function updateJobStatus(string $status, array $data = []): void
    {
        $cacheKey = "pdf_job_status:{$this->applicationState->session_id}";
        
        $statusData = [
            'status' => $status,
            'session_id' => $this->applicationState->session_id,
            'job_id' => $this->job?->getJobId(),
            'updated_at' => now()->toISOString(),
            'data' => $data,
        ];

        Cache::put($cacheKey, $statusData, 3600); // Cache for 1 hour
    }

    /**
     * Send notification based on channel
     */
    private function sendNotification(string $type, array $data): void
    {
        try {
            switch ($this->notificationChannel) {
                case 'email':
                    $this->sendEmailNotification($type, $data);
                    break;
                case 'sms':
                    $this->sendSMSNotification($type, $data);
                    break;
                case 'whatsapp':
                    $this->sendWhatsAppNotification($type, $data);
                    break;
                case 'database':
                default:
                    $this->sendDatabaseNotification($type, $data);
                    break;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send PDF generation notification', [
                'session_id' => $this->applicationState->session_id,
                'channel' => $this->notificationChannel,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send database notification
     */
    private function sendDatabaseNotification(string $type, array $data): void
    {
        // Store notification in database
        // This would typically use a notifications table
        Log::info('PDF generation notification', [
            'session_id' => $this->applicationState->session_id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(string $type, array $data): void
    {
        // Implementation would depend on your email service
        // For now, just log
        Log::info('Email notification for PDF generation', [
            'session_id' => $this->applicationState->session_id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Send SMS notification
     */
    private function sendSMSNotification(string $type, array $data): void
    {
        // Implementation would depend on your SMS service
        Log::info('SMS notification for PDF generation', [
            'session_id' => $this->applicationState->session_id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Send WhatsApp notification
     */
    private function sendWhatsAppNotification(string $type, array $data): void
    {
        // Implementation would use your WhatsApp service
        Log::info('WhatsApp notification for PDF generation', [
            'session_id' => $this->applicationState->session_id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Call webhook URL
     */
    private function callWebhook(string $status, array $data): void
    {
        try {
            $payload = [
                'session_id' => $this->applicationState->session_id,
                'status' => $status,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ];

            // Make HTTP request to callback URL
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post($this->callbackUrl, $payload);

            if (!$response->successful()) {
                Log::warning('Webhook call failed', [
                    'url' => $this->callbackUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Webhook call exception', [
                'url' => $this->callbackUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine which queue to use based on priority
     */
    private function determineQueue(): string
    {
        // High priority for completed applications
        if ($this->applicationState->current_step === 'completed') {
            return 'high';
        }

        // Medium priority for in-review applications
        if ($this->applicationState->current_step === 'in_review') {
            return 'medium';
        }

        // Default queue for others
        return 'default';
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'pdf-generation',
            'session:' . $this->applicationState->session_id,
            'channel:' . $this->applicationState->channel,
            'step:' . $this->applicationState->current_step,
        ];
    }
}
