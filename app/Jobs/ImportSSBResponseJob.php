<?php

namespace App\Jobs;

use App\Enums\SSBLoanStatus;
use App\Models\ApplicationState;
use App\Models\SSBBatchLog;
use App\Services\NotificationService;
use App\Services\SSBApiService;
use App\Services\SSBStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportSSBResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        SSBApiService $apiService,
        SSBStatusService $statusService,
        NotificationService $notificationService
    ): void {
        $batch = SSBBatchLog::create([
            'batch_reference' => 'SSB-IMP-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6)),
            'batch_type' => 'import',
            'status' => 'running',
            'started_at' => now(),
            'metadata' => ['transport' => 'api'],
        ]);

        $processed = 0;
        $failed = 0;
        $unchanged = 0;
        $errors = [];
        $results = [];

        try {
            $applications = $this->importableApplications()->get();

            foreach ($applications as $application) {
                try {
                    $oldStatus = $application->status ?? data_get($application->metadata, 'ssb_status');
                    $apiResult = $apiService->checkStatus($application->reference_code);

                    $results[] = [
                        'application_id' => $application->id,
                        'reference_code' => $application->reference_code,
                        'success' => (bool) ($apiResult['success'] ?? false),
                        'status' => $apiResult['status'] ?? null,
                        'message' => $apiResult['message'] ?? null,
                    ];

                    if (!($apiResult['success'] ?? false)) {
                        $failed++;
                        $errors[] = "{$application->reference_code}: " . ($apiResult['message'] ?? 'Unable to fetch SSB status');
                        continue;
                    }

                    $apiStatus = strtoupper((string) ($apiResult['status'] ?? 'PENDING'));
                    if ($apiStatus === 'PENDING') {
                        $unchanged++;
                        continue;
                    }

                    if ($statusService->processSSBApiStatus($application, $apiStatus)) {
                        $application->refresh();
                        $newStatus = $application->status ?? data_get($application->metadata, 'ssb_status');

                        if ($oldStatus !== $newStatus) {
                            $notificationService->sendStatusUpdateNotification($application, (string) $oldStatus, (string) $newStatus);
                        }

                        $processed++;
                    } else {
                        $failed++;
                        $errors[] = "Failed to process SSB status for {$application->reference_code}";
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "{$application->reference_code}: {$e->getMessage()}";
                }
            }

            $batch->update([
                'status' => $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success',
                'total_records' => $applications->count(),
                'success_count' => $processed,
                'failed_count' => $failed,
                'errors' => $errors,
                'metadata' => [
                    'transport' => 'api',
                    'unchanged_count' => $unchanged,
                    'results' => $results,
                ],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            Log::error('SSB response import failed', [
                'batch_reference' => $batch->batch_reference,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function importableApplications()
    {
        $driver = DB::connection()->getDriverName();
        $statuses = [
            SSBLoanStatus::AWAITING_SSB_APPROVAL->value,
            SSBLoanStatus::PERIOD_ADJUSTED_RESUBMITTED->value,
            SSBLoanStatus::ID_CORRECTED_RESUBMITTED->value,
            SSBLoanStatus::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED->value,
        ];

        return ApplicationState::query()
            ->whereNotNull('reference_code')
            ->where(function ($query) use ($driver, $statuses) {
                $query->whereIn('current_step', $statuses);

                if ($driver === 'pgsql') {
                    $query->orWhereIn(DB::raw("metadata->>'ssb_status'"), $statuses);
                } elseif ($driver === 'sqlite') {
                    $query->orWhereRaw("json_extract(metadata, '$.ssb_status') in (" . implode(',', array_fill(0, count($statuses), '?')) . ")", $statuses);
                } else {
                    $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.ssb_status')) in (" . implode(',', array_fill(0, count($statuses), '?')) . ")", $statuses);
                }
            })
            ->orderBy('updated_at');
    }
}
