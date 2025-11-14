<?php

namespace App\Services;

use App\Models\ApplicationState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemMonitoringService
{
    /**
     * Monitor PDF generation performance
     */
    public function recordPDFGenerationMetrics(string $sessionId, float $generationTime, bool $success = true, ?string $error = null): void
    {
        $metrics = [
            'session_id' => $sessionId,
            'generation_time' => $generationTime,
            'success' => $success,
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
            'memory_usage' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];

        // Store in cache for real-time monitoring
        $this->storePDFMetrics($metrics);

        // Log for persistent storage
        Log::channel('pdf_monitoring')->info('PDF Generation Metrics', $metrics);

        // Check for performance issues
        $this->checkPDFPerformanceAlerts($generationTime, $success, $error);
    }

    /**
     * Store PDF metrics in cache for dashboard
     */
    private function storePDFMetrics(array $metrics): void
    {
        // Store recent metrics (last 100 generations)
        $recentMetrics = Cache::get('pdf_metrics_recent', []);
        array_unshift($recentMetrics, $metrics);
        $recentMetrics = array_slice($recentMetrics, 0, 100);
        Cache::put('pdf_metrics_recent', $recentMetrics, now()->addHours(24));

        // Update hourly statistics
        $hour = now()->format('Y-m-d-H');
        $hourlyStats = Cache::get("pdf_stats_hourly_{$hour}", [
            'total_generations' => 0,
            'successful_generations' => 0,
            'failed_generations' => 0,
            'total_time' => 0,
            'max_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
            'errors' => [],
        ]);

        $hourlyStats['total_generations']++;
        if ($metrics['success']) {
            $hourlyStats['successful_generations']++;
        } else {
            $hourlyStats['failed_generations']++;
            if ($metrics['error']) {
                $hourlyStats['errors'][] = $metrics['error'];
            }
        }

        $hourlyStats['total_time'] += $metrics['generation_time'];
        $hourlyStats['max_time'] = max($hourlyStats['max_time'], $metrics['generation_time']);
        $hourlyStats['min_time'] = min($hourlyStats['min_time'], $metrics['generation_time']);

        Cache::put("pdf_stats_hourly_{$hour}", $hourlyStats, now()->addHours(25));
    }

    /**
     * Check for performance alerts
     */
    private function checkPDFPerformanceAlerts(float $generationTime, bool $success, ?string $error): void
    {
        // Alert if generation time exceeds threshold
        if ($generationTime > 30) { // 30 seconds threshold
            $this->triggerAlert('pdf_generation_slow', [
                'generation_time' => $generationTime,
                'threshold' => 30,
                'message' => "PDF generation took {$generationTime} seconds, exceeding 30s threshold",
            ]);
        }

        // Alert on failures
        if (! $success) {
            $this->triggerAlert('pdf_generation_failed', [
                'error' => $error,
                'message' => "PDF generation failed: {$error}",
            ]);
        }

        // Check failure rate
        $recentMetrics = Cache::get('pdf_metrics_recent', []);
        if (count($recentMetrics) >= 10) {
            $recentFailures = array_filter(array_slice($recentMetrics, 0, 10), fn ($m) => ! $m['success']);
            $failureRate = count($recentFailures) / 10;

            if ($failureRate > 0.3) { // 30% failure rate threshold
                $this->triggerAlert('high_pdf_failure_rate', [
                    'failure_rate' => $failureRate,
                    'threshold' => 0.3,
                    'message' => "PDF generation failure rate is {$failureRate}%, exceeding 30% threshold",
                ]);
            }
        }
    }

    /**
     * Monitor system health
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryHealth(),
            'pdf_service' => $this->checkPDFServiceHealth(),
            'application_states' => $this->checkApplicationStatesHealth(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connection' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => 'failed',
            ];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = (($totalSpace - $diskSpace) / $totalSpace) * 100;

            $status = 'healthy';
            if ($usedPercentage > 90) {
                $status = 'critical';
            } elseif ($usedPercentage > 80) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'free_space_gb' => round($diskSpace / (1024 ** 3), 2),
                'total_space_gb' => round($totalSpace / (1024 ** 3), 2),
                'used_percentage' => round($usedPercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory health
     */
    private function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usagePercentage = ($memoryUsage / $memoryLimit) * 100;

        $status = 'healthy';
        if ($usagePercentage > 90) {
            $status = 'critical';
        } elseif ($usagePercentage > 80) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'usage_mb' => round($memoryUsage / (1024 ** 2), 2),
            'limit_mb' => round($memoryLimit / (1024 ** 2), 2),
            'usage_percentage' => round($usagePercentage, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / (1024 ** 2), 2),
        ];
    }

    /**
     * Check PDF service health
     */
    private function checkPDFServiceHealth(): array
    {
        $recentMetrics = Cache::get('pdf_metrics_recent', []);

        if (empty($recentMetrics)) {
            return [
                'status' => 'unknown',
                'message' => 'No recent PDF generation data available',
            ];
        }

        $recentSuccessful = array_filter(array_slice($recentMetrics, 0, 10), fn ($m) => $m['success']);
        $successRate = count($recentSuccessful) / min(count($recentMetrics), 10);
        $avgGenerationTime = array_sum(array_column(array_slice($recentMetrics, 0, 10), 'generation_time')) / min(count($recentMetrics), 10);

        $status = 'healthy';
        if ($successRate < 0.7) {
            $status = 'critical';
        } elseif ($successRate < 0.9 || $avgGenerationTime > 15) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'success_rate' => round($successRate * 100, 2),
            'avg_generation_time' => round($avgGenerationTime, 2),
            'recent_generations' => count($recentMetrics),
        ];
    }

    /**
     * Check application states health
     */
    private function checkApplicationStatesHealth(): array
    {
        try {
            $totalStates = ApplicationState::count();
            $activeStates = ApplicationState::where('expires_at', '>', now())->count();
            $expiredStates = $totalStates - $activeStates;
            $completedStates = ApplicationState::where('current_step', 'completed')->count();

            return [
                'status' => 'healthy',
                'total_states' => $totalStates,
                'active_states' => $activeStates,
                'expired_states' => $expiredStates,
                'completed_states' => $completedStates,
                'completion_rate' => $totalStates > 0 ? round(($completedStates / $totalStates) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get usage analytics
     */
    public function getUsageAnalytics(int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'applications' => $this->getApplicationAnalytics($startDate, $endDate),
            'pdf_generation' => $this->getPDFAnalytics($days),
            'platform_usage' => $this->getPlatformUsageAnalytics($startDate, $endDate),
            'performance' => $this->getPerformanceAnalytics($days),
        ];
    }

    /**
     * Get application analytics
     */
    private function getApplicationAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = ApplicationState::whereBetween('created_at', [$startDate, $endDate])->get();

        $byChannel = $applications->groupBy('channel')->map->count();
        $byStep = $applications->groupBy('current_step')->map->count();
        $byIntent = $applications->map(fn ($app) => $app->form_data['intent'] ?? 'unknown')->countBy();

        return [
            'total' => $applications->count(),
            'by_channel' => $byChannel->toArray(),
            'by_step' => $byStep->toArray(),
            'by_intent' => $byIntent->toArray(),
            'completion_rate' => $applications->count() > 0
                ? round(($byStep['completed'] ?? 0) / $applications->count() * 100, 2)
                : 0,
        ];
    }

    /**
     * Get PDF analytics from cache
     */
    private function getPDFAnalytics(int $days): array
    {
        $stats = [];
        $totalGenerations = 0;
        $totalSuccessful = 0;
        $totalTime = 0;

        for ($i = 0; $i < $days * 24; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyStats = Cache::get("pdf_stats_hourly_{$hour}", []);

            if (! empty($hourlyStats)) {
                $totalGenerations += $hourlyStats['total_generations'] ?? 0;
                $totalSuccessful += $hourlyStats['successful_generations'] ?? 0;
                $totalTime += $hourlyStats['total_time'] ?? 0;
            }
        }

        return [
            'total_generations' => $totalGenerations,
            'successful_generations' => $totalSuccessful,
            'failed_generations' => $totalGenerations - $totalSuccessful,
            'success_rate' => $totalGenerations > 0 ? round(($totalSuccessful / $totalGenerations) * 100, 2) : 0,
            'avg_generation_time' => $totalGenerations > 0 ? round($totalTime / $totalGenerations, 2) : 0,
        ];
    }

    /**
     * Get platform usage analytics
     */
    private function getPlatformUsageAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = ApplicationState::whereBetween('created_at', [$startDate, $endDate])->get();

        $crossPlatform = $applications->filter(function ($app) {
            return isset($app->metadata['linked_whatsapp_session']) ||
                   isset($app->metadata['linked_web_session']);
        });

        return [
            'web_only' => $applications->where('channel', 'web')->whereNotIn('session_id', $crossPlatform->pluck('session_id'))->count(),
            'whatsapp_only' => $applications->where('channel', 'whatsapp')->whereNotIn('session_id', $crossPlatform->pluck('session_id'))->count(),
            'cross_platform' => $crossPlatform->count(),
            'platform_switching_rate' => $applications->count() > 0
                ? round(($crossPlatform->count() / $applications->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get performance analytics
     */
    private function getPerformanceAnalytics(int $days): array
    {
        $recentMetrics = Cache::get('pdf_metrics_recent', []);

        if (empty($recentMetrics)) {
            return [
                'avg_response_time' => 0,
                'p95_response_time' => 0,
                'error_rate' => 0,
            ];
        }

        $times = array_column($recentMetrics, 'generation_time');
        sort($times);

        $p95Index = (int) ceil(0.95 * count($times)) - 1;
        $errors = array_filter($recentMetrics, fn ($m) => ! $m['success']);

        return [
            'avg_response_time' => round(array_sum($times) / count($times), 2),
            'p95_response_time' => round($times[$p95Index] ?? 0, 2),
            'error_rate' => round((count($errors) / count($recentMetrics)) * 100, 2),
        ];
    }

    /**
     * Trigger system alert
     */
    private function triggerAlert(string $type, array $data): void
    {
        $alert = [
            'type' => $type,
            'severity' => $this->getAlertSeverity($type),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // Store alert in cache
        $alerts = Cache::get('system_alerts', []);
        array_unshift($alerts, $alert);
        $alerts = array_slice($alerts, 0, 50); // Keep last 50 alerts
        Cache::put('system_alerts', $alerts, now()->addDays(7));

        // Log alert
        Log::channel('monitoring')->warning("System Alert: {$type}", $alert);

        // Send notifications for critical alerts
        if ($alert['severity'] === 'critical') {
            $this->sendCriticalAlert($alert);
        }
    }

    /**
     * Get alert severity
     */
    private function getAlertSeverity(string $type): string
    {
        $criticalAlerts = ['pdf_generation_failed', 'high_pdf_failure_rate'];

        return in_array($type, $criticalAlerts) ? 'critical' : 'warning';
    }

    /**
     * Send critical alert notification
     */
    private function sendCriticalAlert(array $alert): void
    {
        // In a real implementation, this would send emails, Slack messages, etc.
        Log::critical('CRITICAL SYSTEM ALERT', $alert);
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get recent alerts
     */
    public function getRecentAlerts(int $limit = 20): array
    {
        $alerts = Cache::get('system_alerts', []);

        return array_slice($alerts, 0, $limit);
    }

    /**
     * Clear old metrics and alerts
     */
    public function cleanupOldData(): void
    {
        // Clean up hourly stats older than 7 days
        for ($i = 168; $i < 336; $i++) { // 7-14 days ago
            $hour = now()->subHours($i)->format('Y-m-d-H');
            Cache::forget("pdf_stats_hourly_{$hour}");
        }

        Log::info('System monitoring data cleanup completed');
    }
}
