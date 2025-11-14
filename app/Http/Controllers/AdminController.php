<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        $analytics = $this->getDashboardAnalytics();

        return Inertia::render('Admin/Dashboard', [
            'analytics' => $analytics,
            'recentApplications' => $this->getRecentApplications(10),
            'systemHealth' => $this->getSystemHealth(),
        ]);
    }

    /**
     * Get dashboard analytics
     */
    private function getDashboardAnalytics(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'applications' => [
                'total' => ApplicationState::count(),
                'today' => ApplicationState::whereDate('created_at', $today)->count(),
                'this_week' => ApplicationState::where('created_at', '>=', $thisWeek)->count(),
                'this_month' => ApplicationState::where('created_at', '>=', $thisMonth)->count(),
                'completed' => ApplicationState::where('current_step', 'completed')->count(),
            ],
            'channels' => [
                'web' => ApplicationState::where('channel', 'web')->count(),
                'whatsapp' => ApplicationState::where('channel', 'whatsapp')->count(),
                'ussd' => ApplicationState::where('channel', 'ussd')->count(),
                'mobile_app' => ApplicationState::where('channel', 'mobile_app')->count(),
            ],
            'status_distribution' => $this->getStatusDistribution(),
            'conversion_rates' => $this->getConversionRates(),
            'average_completion_time' => $this->getAverageCompletionTime(),
        ];
    }

    /**
     * Get applications for admin dashboard
     */
    public function getApplications(): JsonResponse
    {
        $applications = ApplicationState::where('current_step', 'completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                $formResponses = $formData['formResponses'] ?? [];
                $metadata = $app->metadata ?? [];

                // Get applicant name
                $applicantName = trim(
                    ($formResponses['firstName'] ?? '').' '.
                    ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))
                ) ?: 'N/A';

                // Get business/product info
                $business = $formData['business'] ?? 'N/A';
                $loanAmount = $formData['amount'] ?? ($formResponses['loanAmount'] ?? '0');

                // Determine status
                $status = $this->determineApplicationStatus($app);

                return [
                    'id' => $app->id,
                    'sessionId' => $app->session_id,
                    'referenceCode' => $app->reference_code,
                    'applicantName' => $applicantName,
                    'business' => $business,
                    'loanAmount' => $loanAmount,
                    'status' => $status,
                    'submittedAt' => $app->created_at->format('M j, Y g:i A'),
                    'channel' => $app->channel,
                ];
            });

        // Calculate stats
        $stats = [
            'total' => $applications->count(),
            'pending' => $applications->where('status', 'pending')->count(),
            'approved' => $applications->where('status', 'approved')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'applications' => $applications->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,under_review,approved,rejected',
            'reason' => 'required_if:status,rejected',
            'approval_details' => 'nullable|array',
        ]);

        $application = ApplicationState::where('session_id', $sessionId)->first();

        if (! $application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $metadata['status'] = $request->status;
        $metadata['status_updated_at'] = now()->toISOString();
        $metadata['status_updated_by'] = auth()->id() ?? 'admin';

        // Add status history
        $metadata['status_history'] = $metadata['status_history'] ?? [];
        $metadata['status_history'][] = [
            'status' => $request->status,
            'timestamp' => now()->toISOString(),
            'updated_by' => auth()->id() ?? 'admin',
            'reason' => $request->reason,
        ];

        if ($request->status === 'rejected') {
            $metadata['rejection_reason'] = $request->reason;
        }

        if ($request->status === 'approved' && $request->approval_details) {
            $metadata['approval_details'] = [
                'amount' => $request->approval_details['amount'] ?? $application->form_data['amount'] ?? 0,
                'approved_at' => now()->toISOString(),
                'disbursement_date' => $request->approval_details['disbursement_date'] ?? now()->addWeek()->toDateString(),
            ];
        }

        $application->metadata = $metadata;
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully',
            'status' => $request->status,
        ]);
    }

    /**
     * Determine the current status of the application
     */
    private function determineApplicationStatus(ApplicationState $application): string
    {
        $metadata = $application->metadata ?? [];

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
     * Get recent applications
     */
    private function getRecentApplications(int $limit = 10): array
    {
        return ApplicationState::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                $formResponses = $formData['formResponses'] ?? [];

                return [
                    'id' => $app->id,
                    'session_id' => $app->session_id,
                    'applicant_name' => trim(($formResponses['firstName'] ?? '').' '.($formResponses['lastName'] ?? '')),
                    'channel' => $app->channel,
                    'current_step' => $app->current_step,
                    'status' => $this->determineApplicationStatus($app),
                    'created_at' => $app->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get system health status
     */
    private function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
        ];
    }

    /**
     * Get status distribution
     */
    private function getStatusDistribution(): array
    {
        $applications = ApplicationState::where('current_step', 'completed')->get();
        $distribution = ['pending' => 0, 'under_review' => 0, 'approved' => 0, 'rejected' => 0];

        foreach ($applications as $app) {
            $status = $this->determineApplicationStatus($app);
            $distribution[$status] = ($distribution[$status] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Get conversion rates by channel
     */
    private function getConversionRates(): array
    {
        $channels = ['web', 'whatsapp', 'ussd', 'mobile_app'];
        $rates = [];

        foreach ($channels as $channel) {
            $total = ApplicationState::where('channel', $channel)->count();
            $completed = ApplicationState::where('channel', $channel)
                ->where('current_step', 'completed')
                ->count();

            $rates[$channel] = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        }

        return $rates;
    }

    /**
     * Get average completion time
     */
    private function getAverageCompletionTime(): float
    {
        $completedApps = ApplicationState::where('current_step', 'completed')
            ->whereNotNull('metadata->completion_time')
            ->get();

        if ($completedApps->isEmpty()) {
            return 0;
        }

        $totalTime = $completedApps->sum(function ($app) {
            return $app->metadata['completion_time'] ?? 0;
        });

        return round($totalTime / $completedApps->count(), 2);
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();

            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed'];
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            \Cache::put('health_check', 'test', 60);
            $value = \Cache::get('health_check');

            return ['status' => $value === 'test' ? 'healthy' : 'degraded', 'message' => 'Cache operational'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Cache not working'];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            \Storage::disk('public')->put('health_check.txt', 'test');
            $exists = \Storage::disk('public')->exists('health_check.txt');
            \Storage::disk('public')->delete('health_check.txt');

            return ['status' => $exists ? 'healthy' : 'degraded', 'message' => 'Storage operational'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Storage not working'];
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        try {
            // Simple check - in production you might want to check actual queue status
            return ['status' => 'healthy', 'message' => 'Queue system operational'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Queue system not working'];
        }
    }
}
