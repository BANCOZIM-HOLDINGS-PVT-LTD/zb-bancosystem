<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationState;
use App\Services\SSBApiService;
use App\Services\SSBStatusService;
use App\Enums\SSBLoanStatus;
use Illuminate\Support\Facades\Log;

class SyncSSBStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssb:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize loan application statuses with the Salary Deductions Gateway API';

    /**
     * Execute the console command.
     */
    public function handle(SSBApiService $apiService, SSBStatusService $statusService)
    {
        $this->info('Starting SSB Status Sync...');

        // Find applications awaiting SSB approval
        $applications = ApplicationState::whereNotNull('reference_code')
            ->where(function($query) {
                $query->where('status', SSBLoanStatus::AWAITING_SSB_APPROVAL->value)
                      ->orWhere('status', 'awaiting_ssb_approval');
            })
            ->get();

        if ($applications->isEmpty()) {
            $this->info('No applications found awaiting SSB approval.');
            return 0;
        }

        $this->info('Found ' . $applications->count() . ' applications to check.');

        foreach ($applications as $application) {
            $this->comment("Checking status for {$application->reference_code}...");

            $result = $apiService->checkStatus($application->reference_code);

            if ($result['success']) {
                $apiStatus = $result['status'];
                $this->info("API Status for {$application->reference_code}: {$apiStatus}");

                $statusService->processSSBApiStatus($application, $apiStatus);
            } else {
                $this->error("Failed to fetch status for {$application->reference_code}: " . ($result['message'] ?? 'Unknown error'));
            }
        }

        $this->info('SSB Status Sync completed.');
        return 0;
    }
}
