<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ApplicationMonitor
{
    private array $metrics = [];

    private array $alerts = [];

    /**
     * Collect system health metrics
     */
    public function collectHealthMetrics(): array
    {
        $metrics = [
            'timestamp' => now()->toISOString(),
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryUsage(),
            'disk' => $this->checkDiskUsage(),
            'queue' => $this->checkQueueHealth(),
            'application' => $this->checkApplicationHealth(),
        ];

        $this->metrics = $metrics;
        $this->evaluateAlerts($metrics);

        return $metrics;
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabaseHealth(): array
    {
        $startTime = microtime(true);

        try {
            // Test basic connectivity
            DB::connection()->getPdo();

            // Test query performance
            $queryStart = microtime(true);
            $result = DB::select('SELECT 1 as test');
            $queryTime = (microtime(true) - $queryStart) * 1000;

            // Get connection info
            $connectionName = DB::getDefaultConnection();
            $driverName = DB::connection()->getDriverName();

            // Check active connections (MySQL specific)
            $activeConnections = 0;
            if ($driverName === 'mysql') {
                try {
                    $processlist = DB::select('SHOW PROCESSLIST');
                    $activeConnections = count($processlist);
                } catch (\Exception $e) {
                    // Ignore if no permission
                }
            }

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'healthy',
                'connection' => $connectionName,
                'driver' => $driverName,
                'response_time_ms' => round($responseTime, 2),
                'query_time_ms' => round($queryTime, 2),
                'active_connections' => $activeConnections,
                'last_check' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
                'last_check' => now()->toISOString(),
            ];
        }
    }

    /**
     * Check cache system health
     */
    private function checkCacheHealth(): array
    {
        $startTime = microtime(true);

        try {
            $testKey = 'health_check_'.time();
            $testValue = 'test_value_'.random_int(1000, 9999);

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrieved = Cache::get($testKey);

            // Test cache delete
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            $status = ($retrieved === $testValue) ? 'healthy' : 'degraded';

            $result = [
                'status' => $status,
                'driver' => config('cache.default'),
                'response_time_ms' => round($responseTime, 2),
                'last_check' => now()->toISOString(),
            ];

            // Redis specific metrics
            if (config('cache.default') === 'redis') {
                try {
                    $redis = Redis::connection();
                    $info = $redis->info();

                    $result['redis_info'] = [
                        'connected_clients' => $info['connected_clients'] ?? 0,
                        'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                        'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                        'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                    ];
                } catch (\Exception $e) {
                    $result['redis_error'] = $e->getMessage();
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Cache health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
                'last_check' => now()->toISOString(),
            ];
        }
    }

    /**
     * Check storage system health
     */
    private function checkStorageHealth(): array
    {
        $results = [];
        $disks = ['public', 'local'];

        foreach ($disks as $disk) {
            $startTime = microtime(true);

            try {
                $testFile = 'health_check_'.time().'.txt';
                $testContent = 'Health check test content';

                // Test write
                Storage::disk($disk)->put($testFile, $testContent);

                // Test read
                $retrieved = Storage::disk($disk)->get($testFile);

                // Test delete
                Storage::disk($disk)->delete($testFile);

                $responseTime = (microtime(true) - $startTime) * 1000;
                $status = ($retrieved === $testContent) ? 'healthy' : 'degraded';

                $results[$disk] = [
                    'status' => $status,
                    'response_time_ms' => round($responseTime, 2),
                    'driver' => config("filesystems.disks.{$disk}.driver"),
                ];

            } catch (\Exception $e) {
                $results[$disk] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'response_time_ms' => (microtime(true) - $startTime) * 1000,
                ];
            }
        }

        return $results;
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $usagePercentage = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

        return [
            'current_usage_bytes' => $memoryUsage,
            'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_usage_bytes' => $peakMemory,
            'peak_usage_mb' => round($peakMemory / 1024 / 1024, 2),
            'limit_bytes' => $memoryLimit,
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percentage' => round($usagePercentage, 2),
            'status' => $usagePercentage > 90 ? 'critical' : ($usagePercentage > 75 ? 'warning' : 'healthy'),
        ];
    }

    /**
     * Check disk usage
     */
    private function checkDiskUsage(): array
    {
        $path = base_path();
        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes - $freeBytes;
        $usagePercentage = ($usedBytes / $totalBytes) * 100;

        return [
            'total_bytes' => $totalBytes,
            'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
            'used_bytes' => $usedBytes,
            'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
            'free_bytes' => $freeBytes,
            'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round($usagePercentage, 2),
            'status' => $usagePercentage > 95 ? 'critical' : ($usagePercentage > 85 ? 'warning' : 'healthy'),
        ];
    }

    /**
     * Check queue system health
     */
    private function checkQueueHealth(): array
    {
        try {
            // This would depend on your queue driver
            $queueDriver = config('queue.default');

            $result = [
                'driver' => $queueDriver,
                'status' => 'healthy',
                'last_check' => now()->toISOString(),
            ];

            // Add driver-specific checks
            if ($queueDriver === 'redis') {
                try {
                    $redis = Redis::connection();
                    $queueLength = $redis->llen('queues:default');
                    $result['queue_length'] = $queueLength;
                    $result['status'] = $queueLength > 1000 ? 'warning' : 'healthy';
                } catch (\Exception $e) {
                    $result['status'] = 'unhealthy';
                    $result['error'] = $e->getMessage();
                }
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    /**
     * Check application-specific health
     */
    private function checkApplicationHealth(): array
    {
        $checks = [
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        // Check critical services
        $criticalServices = [
            'pdf_generation' => $this->checkPDFService(),
            'file_uploads' => $this->checkFileUploadService(),
            'email_service' => $this->checkEmailService(),
        ];

        return array_merge($checks, ['services' => $criticalServices]);
    }

    /**
     * Check PDF generation service
     */
    private function checkPDFService(): array
    {
        try {
            // Test if DomPDF is available
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return ['status' => 'available'];
            }

            return ['status' => 'unavailable', 'error' => 'DomPDF not found'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check file upload service
     */
    private function checkFileUploadService(): array
    {
        $maxFileSize = ini_get('upload_max_filesize');
        $maxPostSize = ini_get('post_max_size');

        return [
            'status' => 'available',
            'max_file_size' => $maxFileSize,
            'max_post_size' => $maxPostSize,
        ];
    }

    /**
     * Check email service
     */
    private function checkEmailService(): array
    {
        $driver = config('mail.default');

        return [
            'status' => 'configured',
            'driver' => $driver,
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // Unlimited
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }

    /**
     * Evaluate metrics and generate alerts
     */
    private function evaluateAlerts(array $metrics): void
    {
        $this->alerts = [];

        // Database alerts
        if ($metrics['database']['status'] !== 'healthy') {
            $this->alerts[] = [
                'type' => 'critical',
                'service' => 'database',
                'message' => 'Database is unhealthy',
                'details' => $metrics['database'],
            ];
        }

        // Memory alerts
        if ($metrics['memory']['usage_percentage'] > 90) {
            $this->alerts[] = [
                'type' => 'critical',
                'service' => 'memory',
                'message' => 'High memory usage: '.$metrics['memory']['usage_percentage'].'%',
                'details' => $metrics['memory'],
            ];
        }

        // Disk alerts
        if ($metrics['disk']['usage_percentage'] > 95) {
            $this->alerts[] = [
                'type' => 'critical',
                'service' => 'disk',
                'message' => 'Critical disk usage: '.$metrics['disk']['usage_percentage'].'%',
                'details' => $metrics['disk'],
            ];
        }

        // Cache alerts
        if ($metrics['cache']['status'] !== 'healthy') {
            $this->alerts[] = [
                'type' => 'warning',
                'service' => 'cache',
                'message' => 'Cache system is degraded',
                'details' => $metrics['cache'],
            ];
        }

        // Log alerts
        foreach ($this->alerts as $alert) {
            Log::channel('monitoring')->{$alert['type']}($alert['message'], $alert['details']);
        }
    }

    /**
     * Get current alerts
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }

    /**
     * Get metrics summary
     */
    public function getMetricsSummary(): array
    {
        if (empty($this->metrics)) {
            $this->collectHealthMetrics();
        }

        return [
            'overall_status' => $this->calculateOverallStatus(),
            'services_count' => $this->countServicesByStatus(),
            'alerts_count' => count($this->alerts),
            'last_check' => $this->metrics['timestamp'] ?? null,
        ];
    }

    /**
     * Calculate overall system status
     */
    private function calculateOverallStatus(): string
    {
        if (empty($this->metrics)) {
            return 'unknown';
        }

        $criticalAlerts = array_filter($this->alerts, fn ($alert) => $alert['type'] === 'critical');

        if (! empty($criticalAlerts)) {
            return 'critical';
        }

        $warningAlerts = array_filter($this->alerts, fn ($alert) => $alert['type'] === 'warning');

        if (! empty($warningAlerts)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Count services by status
     */
    private function countServicesByStatus(): array
    {
        $counts = ['healthy' => 0, 'warning' => 0, 'critical' => 0, 'unknown' => 0];

        foreach ($this->metrics as $service => $data) {
            if (is_array($data) && isset($data['status'])) {
                $status = $data['status'];
                if (isset($counts[$status])) {
                    $counts[$status]++;
                } else {
                    $counts['unknown']++;
                }
            }
        }

        return $counts;
    }
}
