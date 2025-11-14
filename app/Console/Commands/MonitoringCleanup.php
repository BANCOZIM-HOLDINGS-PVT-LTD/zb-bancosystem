<?php

namespace App\Console\Commands;

use App\Services\SystemMonitoringService;
use Illuminate\Console\Command;

class MonitoringCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:cleanup
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old monitoring data and metrics';

    /**
     * The system monitoring service instance
     */
    protected SystemMonitoringService $monitoringService;

    /**
     * Create a new command instance.
     */
    public function __construct(SystemMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting monitoring data cleanup...');

        if (! $this->option('force')) {
            if (! $this->confirm('This will remove old monitoring data. Do you want to continue?')) {
                $this->info('Cleanup cancelled.');

                return 0;
            }
        }

        try {
            $this->monitoringService->cleanupOldData();
            $this->info('✅ Monitoring data cleanup completed successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error during cleanup: '.$e->getMessage());

            return 1;
        }
    }
}
