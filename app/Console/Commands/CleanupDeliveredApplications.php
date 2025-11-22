<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationState;
use App\Models\CashPurchase;
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
    protected $description = 'Automatically cleanup (soft delete) applications and cash purchases 90 days after delivery completion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of delivered applications and cash purchases...');
        $cutoffDate = Carbon::now()->subDays(90);
        $this->info("Cutoff date: {$cutoffDate->toDateString()} (90 days ago)");

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted');
        }

        // Cleanup ApplicationState records
        $this->cleanupApplicationStates($cutoffDate, $dryRun);

        // Cleanup CashPurchase records
        $this->cleanupCashPurchases($cutoffDate, $dryRun);

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

    /**
     * Cleanup CashPurchase records with delivered status older than cutoff date
     */
    private function cleanupCashPurchases(Carbon $cutoffDate, bool $dryRun): void
    {
        $this->info("\n--- Processing Cash Purchases ---");

        // Find all cash purchases with delivered status before the cutoff date
        $cashPurchases = CashPurchase::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoffDate)
            ->get();

        $this->info("Found {$cashPurchases->count()} delivered cash purchases to check");

        $deletedCount = 0;
        $exemptCount = 0;
        $alreadyDeletedCount = 0;

        foreach ($cashPurchases as $purchase) {
            // Skip if already soft deleted
            if ($purchase->trashed()) {
                $alreadyDeletedCount++;
                continue;
            }

            // Skip if exempt from auto deletion
            if ($purchase->exempt_from_auto_deletion) {
                $exemptCount++;
                $this->line("  - Skipped (exempt): {$purchase->purchase_number} (delivered: {$purchase->delivered_at->toDateString()})");
                continue;
            }

            // Delete the record
            if (!$dryRun) {
                $purchase->delete();
            }
            $deletedCount++;
            $this->line("  - Deleted: {$purchase->purchase_number} (delivered: {$purchase->delivered_at->toDateString()})");
        }

        $this->info("Cash Purchases Summary:");
        $this->info("  - Deleted: {$deletedCount}");
        $this->info("  - Exempt: {$exemptCount}");
        $this->info("  - Already deleted: {$alreadyDeletedCount}");
    }
}
