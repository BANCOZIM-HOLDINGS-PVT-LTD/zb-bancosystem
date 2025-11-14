<?php

namespace App\Http\Controllers;

use App\Services\SystemMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    private SystemMonitoringService $monitoringService;

    public function __construct(SystemMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();

        return response()->json([
            'success' => true,
            'data' => $health,
        ]);
    }

    /**
     * Get usage analytics
     */
    public function getUsageAnalytics(Request $request): JsonResponse
    {
        $days = $request->input('days', 7);
        $analytics = $this->monitoringService->getUsageAnalytics($days);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get recent alerts
     */
    public function getRecentAlerts(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $alerts = $this->monitoringService->getRecentAlerts($limit);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get monitoring dashboard data
     */
    public function getDashboardData(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();
        $analytics = $this->monitoringService->getUsageAnalytics(1); // Last 24 hours
        $alerts = $this->monitoringService->getRecentAlerts(10);

        return response()->json([
            'success' => true,
            'data' => [
                'health' => $health,
                'analytics' => $analytics,
                'alerts' => $alerts,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Trigger manual system health check
     */
    public function triggerHealthCheck(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();

        // Log the manual health check
        \Log::info('Manual system health check triggered', $health);

        return response()->json([
            'success' => true,
            'message' => 'Health check completed',
            'data' => $health,
        ]);
    }

    /**
     * Clean up old monitoring data
     */
    public function cleanupOldData(): JsonResponse
    {
        $this->monitoringService->cleanupOldData();

        return response()->json([
            'success' => true,
            'message' => 'Old monitoring data cleaned up successfully',
        ]);
    }
}
