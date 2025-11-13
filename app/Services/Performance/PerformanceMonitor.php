<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceMonitor
{
    private array $timers = [];
    private array $metrics = [];
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = config('app.debug') || env('PERFORMANCE_MONITORING_ENABLED', false);
    }

    /**
     * Start a performance timer
     */
    public function startTimer(string $name): void
    {
        if (!$this->enabled) return;

        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * End a performance timer and record metrics
     */
    public function endTimer(string $name, array $context = []): float
    {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return 0.0;
        }

        $timer = $this->timers[$name];
        $duration = microtime(true) - $timer['start'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];

        $this->metrics[$name] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        // Log slow operations
        if ($duration > 1.0) { // More than 1 second
            Log::warning("Slow operation detected: {$name}", [
                'duration' => $duration,
                'memory_used' => $memoryUsed,
                'context' => $context,
            ]);
        }

        unset($this->timers[$name]);
        return $duration;
    }

    /**
     * Record a custom metric
     */
    public function recordMetric(string $name, $value, array $context = []): void
    {
        if (!$this->enabled) return;

        $this->metrics[$name] = [
            'value' => $value,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Monitor database query performance
     */
    public function monitorDatabaseQueries(callable $callback, string $operation = 'database_operation')
    {
        if (!$this->enabled) {
            return $callback();
        }

        $queryCount = 0;
        $queryTime = 0;

        DB::listen(function ($query) use (&$queryCount, &$queryTime) {
            $queryCount++;
            $queryTime += $query->time;
        });

        $this->startTimer($operation);
        $result = $callback();
        $duration = $this->endTimer($operation, [
            'query_count' => $queryCount,
            'query_time' => $queryTime,
        ]);

        // Log if too many queries
        if ($queryCount > 10) {
            Log::warning("High query count detected in {$operation}", [
                'query_count' => $queryCount,
                'total_time' => $duration,
                'query_time' => $queryTime,
            ]);
        }

        return $result;
    }

    /**
     * Monitor memory usage
     */
    public function monitorMemoryUsage(callable $callback, string $operation = 'memory_operation')
    {
        if (!$this->enabled) {
            return $callback();
        }

        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        $result = $callback();

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $this->recordMetric($operation . '_memory', [
            'memory_used' => $memoryAfter - $memoryBefore,
            'peak_memory' => $peakAfter - $peakBefore,
            'final_memory' => $memoryAfter,
            'final_peak' => $peakAfter,
        ]);

        // Log high memory usage
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;
        if ($memoryUsedMB > 50) { // More than 50MB
            Log::warning("High memory usage detected in {$operation}", [
                'memory_used_mb' => $memoryUsedMB,
                'peak_memory_mb' => ($peakAfter - $peakBefore) / 1024 / 1024,
            ]);
        }

        return $result;
    }

    /**
     * Monitor cache performance
     */
    public function monitorCacheOperation(string $key, callable $callback, string $operation = 'cache_operation')
    {
        if (!$this->enabled) {
            return $callback();
        }

        $this->startTimer($operation);
        
        $hit = Cache::has($key);
        $result = $callback();
        
        $duration = $this->endTimer($operation, [
            'cache_key' => $key,
            'cache_hit' => $hit,
        ]);

        $this->recordMetric('cache_performance', [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration' => $duration,
        ]);

        return $result;
    }

    /**
     * Get all recorded metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics for a specific operation
     */
    public function getMetric(string $name): ?array
    {
        return $this->metrics[$name] ?? null;
    }

    /**
     * Clear all metrics
     */
    public function clearMetrics(): void
    {
        $this->metrics = [];
        $this->timers = [];
    }

    /**
     * Get performance summary
     */
    public function getSummary(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        $totalDuration = 0;
        $totalMemory = 0;
        $operationCount = 0;

        foreach ($this->metrics as $name => $metric) {
            if (isset($metric['duration'])) {
                $totalDuration += $metric['duration'];
                $operationCount++;
            }
            if (isset($metric['memory_used'])) {
                $totalMemory += $metric['memory_used'];
            }
        }

        return [
            'enabled' => true,
            'total_operations' => $operationCount,
            'total_duration' => $totalDuration,
            'average_duration' => $operationCount > 0 ? $totalDuration / $operationCount : 0,
            'total_memory_used' => $totalMemory,
            'current_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'metrics_count' => count($this->metrics),
        ];
    }

    /**
     * Log performance summary
     */
    public function logSummary(string $context = 'request'): void
    {
        if (!$this->enabled) return;

        $summary = $this->getSummary();
        
        Log::info("Performance summary for {$context}", $summary);

        // Log individual slow operations
        foreach ($this->metrics as $name => $metric) {
            if (isset($metric['duration']) && $metric['duration'] > 0.5) {
                Log::info("Slow operation: {$name}", [
                    'duration' => $metric['duration'],
                    'memory_used' => $metric['memory_used'] ?? 0,
                    'context' => $metric['context'] ?? [],
                ]);
            }
        }
    }

    /**
     * Monitor HTTP request performance
     */
    public function monitorRequest(callable $callback, string $route = 'unknown')
    {
        if (!$this->enabled) {
            return $callback();
        }

        $this->startTimer('http_request');
        
        $result = $this->monitorDatabaseQueries(function () use ($callback) {
            return $this->monitorMemoryUsage($callback, 'request_memory');
        }, 'request_queries');
        
        $this->endTimer('http_request', [
            'route' => $route,
            'method' => request()->method(),
            'url' => request()->url(),
        ]);

        return $result;
    }

    /**
     * Get database performance statistics
     */
    public function getDatabaseStats(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        $stats = [
            'enabled' => true,
            'total_queries' => 0,
            'total_time' => 0,
            'slow_queries' => 0,
            'operations' => [],
        ];

        foreach ($this->metrics as $name => $metric) {
            if (isset($metric['context']['query_count'])) {
                $stats['total_queries'] += $metric['context']['query_count'];
                $stats['total_time'] += $metric['context']['query_time'] ?? 0;
                
                if (($metric['context']['query_time'] ?? 0) > 100) { // More than 100ms
                    $stats['slow_queries']++;
                }
                
                $stats['operations'][$name] = [
                    'queries' => $metric['context']['query_count'],
                    'time' => $metric['context']['query_time'] ?? 0,
                    'duration' => $metric['duration'] ?? 0,
                ];
            }
        }

        return $stats;
    }
}
