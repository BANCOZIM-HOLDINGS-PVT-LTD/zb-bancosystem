<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use Carbon\Carbon;

class CleanupDeliveredApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-delivered-applications {--dry-run : Run without actually deleting records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cleanup (soft delete) applications 90 days after delivery completion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of delivered applications...');
        $cutoffDate = Carbon::now()->subDays(90);
        $this->info("Cutoff date: {$cutoffDate->toDateString()} (90 days ago)");

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted');
        }

        // Cleanup ApplicationState records
        $this->cleanupApplicationStates($cutoffDate, $dryRun);

        $this->info('Cleanup completed!');
    }

    /**
     * Cleanup ApplicationState records with delivered status older than cutoff date
     */
    private function cleanupApplicationStates(Carbon $cutoffDate, bool $dryRun): void
    {
        $this->info("\n--- Processing Loan Applications ---");

        // Find all delivery trackings that were delivered before the cutoff date
        $deliveredTrackings = DeliveryTracking::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoffDate)
            ->with('applicationState')
            ->get();

        $this->info("Found {$deliveredTrackings->count()} delivered applications to check");

        $deletedCount = 0;
        $exemptCount = 0;
        $alreadyDeletedCount = 0;

        foreach ($deliveredTrackings as $delivery) {
            $application = $delivery->applicationState;

            if (!$application) {
                continue;
            }

            // Skip if already soft deleted
            if ($application->trashed()) {
                $alreadyDeletedCount++;
                continue;
            }

            // Skip if exempt from auto deletion
            if ($application->exempt_from_auto_deletion) {
                $exemptCount++;
                $this->line("  - Skipped (exempt): {$application->session_id} (delivered: {$delivery->delivered_at->toDateString()})");
                continue;
            }

            // Delete the record
            if (!$dryRun) {
                $application->delete();
            }
            $deletedCount++;
            $this->line("  - Deleted: {$application->session_id} (delivered: {$delivery->delivered_at->toDateString()})");
        }

        $this->info("Loan Applications Summary:");
        $this->info("  - Deleted: {$deletedCount}");
        $this->info("  - Exempt: {$exemptCount}");
        $this->info("  - Already deleted: {$alreadyDeletedCount}");
    }
}

