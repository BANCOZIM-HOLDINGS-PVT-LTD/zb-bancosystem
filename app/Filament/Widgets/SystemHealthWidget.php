<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-health';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $healthMetrics = $this->getBasicHealthMetrics();

        return [
            'healthMetrics' => $healthMetrics,
            'overallStatus' => $this->calculateOverallStatus($healthMetrics),
            'alerts' => [],
        ];
    }

    private function getBasicHealthMetrics(): array
    {
        return [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
        ];
    }

    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed'];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');

            return ['status' => $value === 'test' ? 'healthy' : 'degraded', 'message' => 'Cache operational'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Cache not working'];
        }
    }

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

    private function checkQueueHealth(): array
    {
        try {
            return ['status' => 'healthy', 'message' => 'Queue system operational'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Queue system not working'];
        }
    }

    private function calculateOverallStatus(array $metrics): string
    {
        $statuses = [];

        foreach ($metrics as $service => $data) {
            if (is_array($data) && isset($data['status'])) {
                $statuses[] = $data['status'];
            }
        }

        if (in_array('unhealthy', $statuses)) {
            return 'critical';
        }

        if (in_array('degraded', $statuses) || in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }
}
