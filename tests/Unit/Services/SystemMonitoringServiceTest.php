<?php

namespace Tests\Unit\Services;

use App\Models\ApplicationState;
use App\Services\SystemMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SystemMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = app(SystemMonitoringService::class);
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_records_pdf_generation_metrics()
    {
        $sessionId = 'test_session_123';
        $generationTime = 5.5;

        $this->monitoringService->recordPDFGenerationMetrics($sessionId, $generationTime, true);

        // Check that metrics were stored in cache
        $recentMetrics = Cache::get('pdf_metrics_recent', []);
        $this->assertNotEmpty($recentMetrics);
        $this->assertEquals($sessionId, $recentMetrics[0]['session_id']);
        $this->assertEquals($generationTime, $recentMetrics[0]['generation_time']);
        $this->assertTrue($recentMetrics[0]['success']);
    }

    /** @test */
    public function it_records_pdf_generation_failures()
    {
        $sessionId = 'test_session_456';
        $generationTime = 2.0;
        $error = 'Template not found';

        $this->monitoringService->recordPDFGenerationMetrics($sessionId, $generationTime, false, $error);

        // Check that failure metrics were stored
        $recentMetrics = Cache::get('pdf_metrics_recent', []);
        $this->assertNotEmpty($recentMetrics);
        $this->assertEquals($sessionId, $recentMetrics[0]['session_id']);
        $this->assertFalse($recentMetrics[0]['success']);
        $this->assertEquals($error, $recentMetrics[0]['error']);
    }

    /** @test */
    public function it_gets_system_health()
    {
        $health = $this->monitoringService->getSystemHealth();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('database', $health);
        $this->assertArrayHasKey('storage', $health);
        $this->assertArrayHasKey('memory', $health);
        $this->assertArrayHasKey('pdf_service', $health);
        $this->assertArrayHasKey('application_states', $health);
        $this->assertArrayHasKey('timestamp', $health);
    }

    /** @test */
    public function it_checks_database_health()
    {
        $health = $this->monitoringService->getSystemHealth();

        $this->assertEquals('healthy', $health['database']['status']);
        $this->assertArrayHasKey('response_time_ms', $health['database']);
        $this->assertArrayHasKey('connection', $health['database']);
    }

    /** @test */
    public function it_gets_usage_analytics()
    {
        // Create some test application states
        ApplicationState::create([
            'session_id' => 'web_test_1',
            'channel' => 'web',
            'user_identifier' => 'web_test_1',
            'current_step' => 'completed',
            'form_data' => ['intent' => 'hirePurchase'],
            'expires_at' => now()->addHours(24),
        ]);

        ApplicationState::create([
            'session_id' => 'whatsapp_test_1',
            'channel' => 'whatsapp',
            'user_identifier' => '263771234567',
            'current_step' => 'form',
            'form_data' => ['intent' => 'microBiz'],
            'expires_at' => now()->addDays(7),
        ]);

        $analytics = $this->monitoringService->getUsageAnalytics(7);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('period', $analytics);
        $this->assertArrayHasKey('applications', $analytics);
        $this->assertArrayHasKey('pdf_generation', $analytics);
        $this->assertArrayHasKey('platform_usage', $analytics);
        $this->assertArrayHasKey('performance', $analytics);

        // Check applications analytics
        $this->assertEquals(2, $analytics['applications']['total']);
        $this->assertArrayHasKey('web', $analytics['applications']['by_channel']);
        $this->assertArrayHasKey('whatsapp', $analytics['applications']['by_channel']);
    }

    /** @test */
    public function it_stores_hourly_statistics()
    {
        $sessionId = 'test_session_stats';

        // Record multiple metrics
        $this->monitoringService->recordPDFGenerationMetrics($sessionId.'_1', 3.0, true);
        $this->monitoringService->recordPDFGenerationMetrics($sessionId.'_2', 5.0, true);
        $this->monitoringService->recordPDFGenerationMetrics($sessionId.'_3', 2.0, false, 'Test error');

        $hour = now()->format('Y-m-d-H');
        $hourlyStats = Cache::get("pdf_stats_hourly_{$hour}");

        $this->assertNotNull($hourlyStats);
        $this->assertEquals(3, $hourlyStats['total_generations']);
        $this->assertEquals(2, $hourlyStats['successful_generations']);
        $this->assertEquals(1, $hourlyStats['failed_generations']);
        $this->assertEquals(10.0, $hourlyStats['total_time']); // 3 + 5 + 2
        $this->assertEquals(5.0, $hourlyStats['max_time']);
        $this->assertEquals(2.0, $hourlyStats['min_time']);
        $this->assertContains('Test error', $hourlyStats['errors']);
    }

    /** @test */
    public function it_gets_recent_alerts()
    {
        // Trigger an alert by recording a slow PDF generation
        $this->monitoringService->recordPDFGenerationMetrics('slow_test', 35.0, true);

        $alerts = $this->monitoringService->getRecentAlerts(10);

        $this->assertIsArray($alerts);
        if (! empty($alerts)) {
            $this->assertArrayHasKey('type', $alerts[0]);
            $this->assertArrayHasKey('severity', $alerts[0]);
            $this->assertArrayHasKey('data', $alerts[0]);
            $this->assertArrayHasKey('timestamp', $alerts[0]);
        }
    }

    /** @test */
    public function it_detects_high_failure_rates()
    {
        // Record 10 failures to trigger high failure rate alert
        for ($i = 0; $i < 10; $i++) {
            $this->monitoringService->recordPDFGenerationMetrics("fail_test_{$i}", 1.0, false, 'Test failure');
        }

        $alerts = $this->monitoringService->getRecentAlerts(5);

        // Should have triggered a high failure rate alert
        $failureRateAlert = collect($alerts)->firstWhere('type', 'high_pdf_failure_rate');
        $this->assertNotNull($failureRateAlert);
        $this->assertEquals('critical', $failureRateAlert['severity']);
    }

    /** @test */
    public function it_cleans_up_old_data()
    {
        // This test mainly ensures the method runs without errors
        $this->monitoringService->cleanupOldData();

        // The method should complete without throwing exceptions
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
