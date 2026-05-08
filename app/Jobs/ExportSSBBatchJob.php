<?php

namespace App\Jobs;

use App\Enums\SSBLoanStatus;
use App\Models\ApplicationState;
use App\Models\SSBBatchLog;
use App\Services\SSBApiService;
use App\Services\SSBStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportSSBBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SSBStatusService $statusService, SSBApiService $apiService): void
    {
        $batch = SSBBatchLog::create([
            'batch_reference' => 'SSB-EXP-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6)),
            'batch_type' => 'export',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $applications = $this->exportableApplications()->get();

            if ($applications->isEmpty()) {
                $batch->update([
                    'status' => 'success',
                    'total_records' => 0,
                    'completed_at' => now(),
                    'metadata' => ['message' => 'No applications awaiting SSB export.'],
                ]);

                return;
            }

            $relativePath = 'ssb/exports/' . $batch->batch_reference . '.csv';
            $csv = $this->buildCsv($applications);
            Storage::disk('local')->put($relativePath, $csv);

            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            $results = [];

            foreach ($applications as $application) {
                try {
                    $apiResult = $apiService->submitLoan($application);

                    $results[] = [
                        'application_id' => $application->id,
                        'reference_code' => $application->reference_code,
                        'success' => (bool) ($apiResult['success'] ?? false),
                        'message' => $apiResult['message'] ?? null,
                        'api_response' => $apiResult['api_response'] ?? null,
                    ];

                    if (($apiResult['success'] ?? false) && $statusService->markAsExported($application)) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "{$application->reference_code}: " . ($apiResult['message'] ?? $apiResult['error'] ?? 'SSB API submission failed');
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $errors[] = "{$application->reference_code}: {$e->getMessage()}";
                }
            }

            $batch->update([
                'status' => $failedCount > 0 ? 'partial' : 'success',
                'file_path' => $relativePath,
                'total_records' => $applications->count(),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
                'metadata' => [
                    'transport' => 'api',
                    'audit_file_path' => $relativePath,
                    'results' => $results,
                ],
                'completed_at' => now(),
            ]);

            Log::info('SSB batch export completed', [
                'batch_reference' => $batch->batch_reference,
                'total' => $applications->count(),
                'success' => $successCount,
                'failed' => $failedCount,
                'file_path' => $relativePath,
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            Log::error('SSB batch export failed', [
                'batch_reference' => $batch->batch_reference,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function exportableApplications()
    {
        $driver = DB::connection()->getDriverName();

        return ApplicationState::query()
            ->where(function ($query) use ($driver) {
                $query->where('current_step', SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value);

                if ($driver === 'pgsql') {
                    $query->orWhereRaw("metadata->>'ssb_status' = ?", [SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value]);
                } elseif ($driver === 'sqlite') {
                    $query->orWhereRaw("json_extract(metadata, '$.ssb_status') = ?", [SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value]);
                } else {
                    $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.ssb_status')) = ?", [SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value]);
                }
            })
            ->orderBy('updated_at');
    }

    private function buildCsv($applications): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'reference_code',
            'application_number',
            'branch',
            'surname',
            'first_name',
            'ec_number',
            'national_id',
            'product',
            'loan_amount',
            'monthly_installment',
            'period',
            'mobile',
            'address',
            'next_of_kin',
        ]);

        foreach ($applications as $application) {
            fputcsv($handle, $this->mapApplication($application));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function mapApplication(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];

        return [
            $application->reference_code,
            $application->application_number,
            $application->assignedBranch?->name ?? 'Unassigned',
            $responses['surname'] ?? $responses['lastName'] ?? '',
            $responses['firstName'] ?? '',
            $responses['employmentNumber'] ?? $responses['ecNumber'] ?? '',
            $responses['nationalIdNumber'] ?? $responses['idNumber'] ?? '',
            $formData['productName'] ?? $formData['business'] ?? $formData['selectedBusiness']['name'] ?? '',
            $formData['loanAmount'] ?? $formData['grossLoan'] ?? $formData['finalPrice'] ?? '',
            $formData['monthlyPayment'] ?? $formData['monthlyInstallment'] ?? '',
            $formData['creditTerm'] ?? $formData['loanTenure'] ?? '',
            $responses['mobile'] ?? $responses['phoneNumber'] ?? $responses['phone'] ?? '',
            $this->address($responses),
            $this->nextOfKin($responses),
        ];
    }

    private function address(array $responses): string
    {
        $address = $responses['residentialAddress'] ?? $responses['address'] ?? '';

        if (is_array($address)) {
            return $address['addressLine'] ?? implode(', ', array_filter($address));
        }

        if (is_string($address) && (str_starts_with($address, '{') || str_starts_with($address, '['))) {
            $decoded = json_decode($address, true);
            return is_array($decoded) ? ($decoded['addressLine'] ?? implode(', ', array_filter($decoded))) : $address;
        }

        return (string) $address;
    }

    private function nextOfKin(array $responses): string
    {
        $spouseDetails = $responses['spouseDetails'] ?? [];

        if (is_string($spouseDetails)) {
            $spouseDetails = json_decode($spouseDetails, true) ?: [];
        }

        if (is_array($spouseDetails)) {
            foreach ($spouseDetails as $kin) {
                if (!empty($kin['fullName'])) {
                    return $kin['fullName'];
                }
            }
        }

        return $responses['nextOfKinName'] ?? '';
    }
}
