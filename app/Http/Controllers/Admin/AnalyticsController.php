<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationState;
use App\Repositories\ApplicationStateRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    private ApplicationStateRepository $repository;

    public function __construct(ApplicationStateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request): Response
    {
        $dateRange = $this->getDateRange($request);

        return Inertia::render('Admin/Analytics/Index', [
            'overview' => $this->getOverviewMetrics($dateRange),
            'channelPerformance' => $this->getChannelPerformance($dateRange),
            'conversionFunnel' => $this->getConversionFunnel($dateRange),
            'timeSeriesData' => $this->getTimeSeriesData($dateRange),
            'employerBreakdown' => $this->getEmployerBreakdown($dateRange),
            'geographicData' => $this->getGeographicData($dateRange),
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Get channel performance data
     */
    public function channelPerformance(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        $data = $this->getChannelPerformance($dateRange);

        return response()->json($data);
    }

    /**
     * Get conversion funnel data
     */
    public function conversionFunnel(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        $data = $this->getConversionFunnel($dateRange);

        return response()->json($data);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,pdf',
            'type' => 'required|in:overview,detailed,channel_performance',
        ]);

        $dateRange = $this->getDateRange($request);

        switch ($request->type) {
            case 'overview':
                return $this->exportOverview($dateRange, $request->format);
            case 'detailed':
                return $this->exportDetailed($dateRange, $request->format);
            case 'channel_performance':
                return $this->exportChannelPerformance($dateRange, $request->format);
            default:
                abort(400, 'Invalid export type');
        }
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics(array $dateRange): array
    {
        $query = ApplicationState::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'total_applications' => $query->count(),
            'completed_applications' => $query->where('current_step', 'completed')->count(),
            'conversion_rate' => $this->calculateConversionRate($query),
            'average_completion_time' => $this->calculateAverageCompletionTime($query),
            'bounce_rate' => $this->calculateBounceRate($query),
            'top_exit_points' => $this->getTopExitPoints($query),
        ];
    }

    /**
     * Get channel performance data
     */
    private function getChannelPerformance(array $dateRange): array
    {
        $channels = ['web', 'whatsapp', 'ussd', 'mobile_app'];
        $performance = [];

        foreach ($channels as $channel) {
            $query = ApplicationState::where('channel', $channel)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

            $total = $query->count();
            $completed = $query->where('current_step', 'completed')->count();
            $conversionRate = $total > 0 ? ($completed / $total) * 100 : 0;

            $performance[$channel] = [
                'total_applications' => $total,
                'completed_applications' => $completed,
                'conversion_rate' => round($conversionRate, 2),
                'average_time_to_complete' => $this->getAverageCompletionTimeByChannel($channel, $dateRange),
                'bounce_rate' => $this->getBounceRateByChannel($channel, $dateRange),
            ];
        }

        return $performance;
    }

    /**
     * Get conversion funnel data
     */
    private function getConversionFunnel(array $dateRange): array
    {
        $steps = ['language', 'intent', 'employer', 'account_check', 'form', 'documents', 'completed'];
        $funnel = [];

        $totalStarted = ApplicationState::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

        foreach ($steps as $step) {
            $count = ApplicationState::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->where(function ($query) use ($step) {
                    $query->where('current_step', $step)
                        ->orWhere(function ($q) use ($step) {
                            // Include users who have passed this step
                            $stepOrder = ['language' => 1, 'intent' => 2, 'employer' => 3, 'account_check' => 4, 'form' => 5, 'documents' => 6, 'completed' => 7];
                            $currentStepOrder = $stepOrder[$step] ?? 0;

                            foreach ($stepOrder as $s => $order) {
                                if ($order > $currentStepOrder) {
                                    $q->orWhere('current_step', $s);
                                }
                            }
                        });
                })
                ->count();

            $percentage = $totalStarted > 0 ? ($count / $totalStarted) * 100 : 0;

            $funnel[] = [
                'step' => $step,
                'count' => $count,
                'percentage' => round($percentage, 2),
                'drop_off' => $step !== 'language' ? round(100 - $percentage, 2) : 0,
            ];
        }

        return $funnel;
    }

    /**
     * Get time series data
     */
    private function getTimeSeriesData(array $dateRange): array
    {
        $data = ApplicationState::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(function ($item) {
            return [
                'date' => $item->date,
                'applications' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get employer breakdown
     */
    private function getEmployerBreakdown(array $dateRange): array
    {
        $data = ApplicationState::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) as employer, COUNT(*) as count")
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('form_data')
            ->groupBy('employer')
            ->orderBy('count', 'desc')
            ->get();

        return $data->map(function ($item) {
            return [
                'employer' => $item->employer ?: 'Unknown',
                'count' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get geographic data (if available)
     */
    private function getGeographicData(array $dateRange): array
    {
        // This would require IP geolocation or user-provided location data
        // For now, return placeholder data
        return [
            'Harare' => 45,
            'Bulawayo' => 25,
            'Chitungwiza' => 15,
            'Mutare' => 10,
            'Gweru' => 5,
        ];
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($query): float
    {
        $total = $query->count();
        $completed = $query->where('current_step', 'completed')->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Calculate average completion time
     */
    private function calculateAverageCompletionTime($query): float
    {
        $completedApps = $query->where('current_step', 'completed')
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
     * Calculate bounce rate
     */
    private function calculateBounceRate($query): float
    {
        $total = $query->count();
        $bounced = $query->where('current_step', 'language')->count();

        return $total > 0 ? round(($bounced / $total) * 100, 2) : 0;
    }

    /**
     * Get top exit points
     */
    private function getTopExitPoints($query): array
    {
        $exitPoints = $query->selectRaw('current_step, COUNT(*) as count')
            ->where('current_step', '!=', 'completed')
            ->groupBy('current_step')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return $exitPoints->map(function ($item) {
            return [
                'step' => $item->current_step,
                'count' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get average completion time by channel
     */
    private function getAverageCompletionTimeByChannel(string $channel, array $dateRange): float
    {
        $completedApps = ApplicationState::where('channel', $channel)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('current_step', 'completed')
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
     * Get bounce rate by channel
     */
    private function getBounceRateByChannel(string $channel, array $dateRange): float
    {
        $total = ApplicationState::where('channel', $channel)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $bounced = ApplicationState::where('channel', $channel)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('current_step', 'language')
            ->count();

        return $total > 0 ? round(($bounced / $total) * 100, 2) : 0;
    }

    /**
     * Get date range from request
     */
    private function getDateRange(Request $request): array
    {
        $start = $request->input('start_date', Carbon::now()->subDays(30)->startOfDay());
        $end = $request->input('end_date', Carbon::now()->endOfDay());

        return [
            'start' => Carbon::parse($start),
            'end' => Carbon::parse($end),
        ];
    }

    /**
     * Export overview data
     */
    private function exportOverview(array $dateRange, string $format)
    {
        // Implementation would depend on your export library
        // For now, return a simple response
        return response()->json(['message' => 'Export functionality not implemented yet']);
    }

    /**
     * Export detailed data
     */
    private function exportDetailed(array $dateRange, string $format)
    {
        // Implementation would depend on your export library
        return response()->json(['message' => 'Export functionality not implemented yet']);
    }

    /**
     * Export channel performance data
     */
    private function exportChannelPerformance(array $dateRange, string $format)
    {
        // Implementation would depend on your export library
        return response()->json(['message' => 'Export functionality not implemented yet']);
    }
}
