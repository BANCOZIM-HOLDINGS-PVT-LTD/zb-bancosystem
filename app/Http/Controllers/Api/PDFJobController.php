<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePDFJob;
use App\Models\ApplicationState;
use App\Repositories\ApplicationStateRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PDFJobController extends Controller
{
    private ApplicationStateRepository $repository;

    public function __construct(ApplicationStateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Queue a PDF generation job
     */
    public function queuePDFGeneration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
            'options' => 'nullable|array',
            'notification_channel' => 'nullable|string|in:database,email,sms,whatsapp',
            'callback_url' => 'nullable|url',
            'priority' => 'nullable|string|in:low,normal,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Find application state
        $applicationState = $this->repository->findBySessionId($validated['session_id']);
        
        if (!$applicationState) {
            return response()->json([
                'success' => false,
                'message' => 'Application state not found',
            ], 404);
        }

        // Check if job is already queued
        $existingJobStatus = $this->getJobStatus($validated['session_id']);
        if ($existingJobStatus && in_array($existingJobStatus['status'], ['queued', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'PDF generation job already in progress',
                'job_status' => $existingJobStatus,
            ], 409);
        }

        try {
            // Queue the job
            $job = new GeneratePDFJob(
                $applicationState,
                $validated['options'] ?? [],
                $validated['notification_channel'] ?? 'database',
                $validated['callback_url'] ?? null
            );

            // Set priority queue
            if (isset($validated['priority'])) {
                $job->onQueue($validated['priority']);
            }

            dispatch($job);

            // Set initial job status
            $this->setJobStatus($validated['session_id'], 'queued', [
                'queued_at' => now()->toISOString(),
                'options' => $validated['options'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF generation job queued successfully',
                'session_id' => $validated['session_id'],
                'job_status' => 'queued',
                'status_url' => route('api.pdf.status', ['session_id' => $validated['session_id']]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue PDF generation job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get PDF generation job status
     */
    public function getJobStatus(Request $request, string $sessionId): JsonResponse
    {
        $status = $this->getJobStatus($sessionId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'No PDF generation job found for this session',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'job_status' => $status,
        ]);
    }

    /**
     * Cancel a PDF generation job
     */
    public function cancelJob(Request $request, string $sessionId): JsonResponse
    {
        $status = $this->getJobStatus($sessionId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'No PDF generation job found for this session',
            ], 404);
        }

        if (!in_array($status['status'], ['queued', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Job cannot be cancelled in current status: ' . $status['status'],
            ], 400);
        }

        try {
            // Update status to cancelled
            $this->setJobStatus($sessionId, 'cancelled', [
                'cancelled_at' => now()->toISOString(),
                'cancelled_by' => $request->user()?->id ?? 'system',
            ]);

            // TODO: Implement actual job cancellation in queue
            // This would require additional queue management

            return response()->json([
                'success' => true,
                'message' => 'PDF generation job cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel PDF generation job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get job status from cache
     */
    private function getJobStatus(string $sessionId): ?array
    {
        $cacheKey = "pdf_job_status:{$sessionId}";
        return Cache::get($cacheKey);
    }

    /**
     * Set job status in cache
     */
    private function setJobStatus(string $sessionId, string $status, array $data = []): void
    {
        $cacheKey = "pdf_job_status:{$sessionId}";
        
        $statusData = [
            'status' => $status,
            'session_id' => $sessionId,
            'updated_at' => now()->toISOString(),
            'data' => $data,
        ];

        Cache::put($cacheKey, $statusData, 3600); // Cache for 1 hour
    }

    /**
     * Get all job statuses (admin only)
     */
    public function getAllJobStatuses(Request $request): JsonResponse
    {
        // This would typically require admin authentication
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:queued,processing,completed,failed,cancelled',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $limit = $validated['limit'] ?? 50;

        // This is a simplified implementation
        // In a real application, you'd store job statuses in a database table
        $allStatuses = [];
        
        // Get recent application states and check their job statuses
        $recentApplications = $this->repository->getByDateRange(
            now()->subDays(7),
            now()
        )->take($limit);

        foreach ($recentApplications as $application) {
            $status = $this->getJobStatus($application->session_id);
            if ($status && (!isset($validated['status']) || $status['status'] === $validated['status'])) {
                $allStatuses[] = $status;
            }
        }

        return response()->json([
            'success' => true,
            'job_statuses' => $allStatuses,
            'total' => count($allStatuses),
        ]);
    }

    /**
     * Get job statistics
     */
    public function getJobStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $days = $request->input('days', 7);

        // Get recent applications
        $recentApplications = $this->repository->getByDateRange(
            now()->subDays($days),
            now()
        );

        $statistics = [
            'total_applications' => $recentApplications->count(),
            'jobs_queued' => 0,
            'jobs_processing' => 0,
            'jobs_completed' => 0,
            'jobs_failed' => 0,
            'jobs_cancelled' => 0,
            'success_rate' => 0,
        ];

        // Check job statuses
        foreach ($recentApplications as $application) {
            $status = $this->getJobStatus($application->session_id);
            if ($status) {
                switch ($status['status']) {
                    case 'queued':
                        $statistics['jobs_queued']++;
                        break;
                    case 'processing':
                        $statistics['jobs_processing']++;
                        break;
                    case 'completed':
                        $statistics['jobs_completed']++;
                        break;
                    case 'failed':
                        $statistics['jobs_failed']++;
                        break;
                    case 'cancelled':
                        $statistics['jobs_cancelled']++;
                        break;
                }
            }
        }

        // Calculate success rate
        $totalJobs = $statistics['jobs_completed'] + $statistics['jobs_failed'];
        if ($totalJobs > 0) {
            $statistics['success_rate'] = round(($statistics['jobs_completed'] / $totalJobs) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'period_days' => $days,
        ]);
    }
}
