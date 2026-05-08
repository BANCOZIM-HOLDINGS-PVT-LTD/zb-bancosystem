<?php

namespace Tests\Feature;

use App\Enums\SSBLoanStatus;
use App\Jobs\ExportSSBBatchJob;
use App\Models\ApplicationState;
use App\Models\SSBBatchLog;
use App\Services\SSBApiService;
use App\Services\SSBStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportSSBBatchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_successful_empty_export_when_no_applications_are_waiting(): void
    {
        Storage::fake('local');

        app(ExportSSBBatchJob::class)->handle(app(SSBStatusService::class), app(SSBApiService::class));

        $batch = SSBBatchLog::first();

        $this->assertNotNull($batch);
        $this->assertSame('export', $batch->batch_type);
        $this->assertSame('success', $batch->status);
        $this->assertSame(0, $batch->total_records);
        $this->assertSame(0, $batch->success_count);
        $this->assertSame(0, $batch->failed_count);
        $this->assertNull($batch->file_path);
    }

    public function test_it_exports_waiting_applications_and_marks_them_as_exported(): void
    {
        Storage::fake('local');

        $application = ApplicationState::create([
            'session_id' => 'ssb-session-001',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value,
            'reference_code' => 'SSBTEST001',
            'form_data' => [
                'employer' => 'government-ssb',
                'productName' => 'Business Equipment',
                'loanAmount' => '1000.00',
                'monthlyPayment' => '100.00',
                'creditTerm' => '12',
                'formResponses' => [
                    'firstName' => 'Tapiwa',
                    'surname' => 'Moyo',
                    'employmentNumber' => 'EC12345',
                    'nationalIdNumber' => '63-123456-A-12',
                    'mobile' => '+263771234567',
                    'residentialAddress' => '10 First Street, Harare',
                    'nextOfKinName' => 'Rudo Moyo',
                ],
            ],
            'metadata' => [
                'ssb_status' => SSBLoanStatus::AWAITING_SSB_CSV_EXPORT->value,
            ],
        ]);

        $this->mock(SSBApiService::class, function ($mock) {
            $mock->shouldReceive('submitLoan')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Accepted',
                    'api_response' => ['status' => 'PENDING'],
                ]);
        });

        app(ExportSSBBatchJob::class)->handle(app(SSBStatusService::class), app(SSBApiService::class));

        $batch = SSBBatchLog::first();
        $application->refresh();

        $this->assertSame('success', $batch->status);
        $this->assertSame(1, $batch->total_records);
        $this->assertSame(1, $batch->success_count);
        $this->assertSame(0, $batch->failed_count);
        $this->assertNotNull($batch->file_path);
        $this->assertSame('api', $batch->metadata['transport']);
        Storage::disk('local')->assertExists($batch->file_path);

        $csv = Storage::disk('local')->get($batch->file_path);
        $this->assertStringContainsString('reference_code,application_number,branch', $csv);
        $this->assertStringContainsString('SSBTEST001', $csv);

        $this->assertSame(SSBLoanStatus::AWAITING_SSB_APPROVAL->value, $application->current_step);
        $this->assertSame(SSBLoanStatus::AWAITING_SSB_APPROVAL->value, $application->metadata['ssb_status']);
    }
}
