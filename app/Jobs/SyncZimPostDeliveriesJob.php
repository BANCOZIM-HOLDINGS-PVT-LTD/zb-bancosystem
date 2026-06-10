<?php

namespace App\Jobs;

use App\Models\DeliveryTracking;
use App\Services\ZimPost\Exceptions\ZimPostApiException;
use App\Services\ZimPost\ZimPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncZimPostDeliveriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Map ZimPost partner-API status values to our local DeliveryTracking status values.
     * Local statuses live on DeliveryTracking::status (see model getStatusLabelAttribute).
     */
    protected const STATUS_MAP = [
        'pending' => 'pending',
        'assigned' => 'dispatched',
        'picked_up' => 'in_transit',
        'in_transit' => 'in_transit',
        'delivered' => 'delivered',
        'cancelled' => 'failed',
    ];

    public function handle(ZimPostService $zimpost): void
    {
        DeliveryTracking::query()
            ->whereNotNull('zimpost_delivery_id')
            ->whereNotIn('status', ['delivered', 'failed', 'returned'])
            ->chunkById(50, function ($rows) use ($zimpost) {
                foreach ($rows as $tracking) {
                    $this->syncOne($tracking, $zimpost);
                }
            });
    }

    protected function syncOne(DeliveryTracking $tracking, ZimPostService $zimpost): void
    {
        try {
            $detail = $zimpost->getDelivery($tracking->zimpost_delivery_id);
        } catch (ZimPostApiException $e) {
            Log::warning('ZimPost sync: detail fetch failed', [
                'tracking_id' => $tracking->id,
                'zimpost_id' => $tracking->zimpost_delivery_id,
                'error_code' => $e->errorCode,
            ]);
            return;
        }

        $tracking->forceFill([
            'zimpost_tracking_number' => $detail['tracking_number'] ?? $tracking->zimpost_tracking_number,
            'zimpost_last_synced_at' => now(),
            'zimpost_snapshot' => $detail,
        ])->save();

        $remoteStatus = $detail['status'] ?? null;
        if (! $remoteStatus) {
            return;
        }

        $localTarget = self::STATUS_MAP[$remoteStatus] ?? null;
        if ($localTarget && $tracking->status !== $localTarget) {
            // Goes through addStatusUpdate so the existing SMS / archival hooks in
            // DeliveryTracking::boot() fire as if updated by a human.
            $tracking->addStatusUpdate(
                $localTarget,
                "Synced from ZimPost ({$remoteStatus})",
                ['source' => 'zimpost', 'remote_status' => $remoteStatus]
            );
        }
    }
}
