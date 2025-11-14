<?php

namespace Tests\Performance;

use App\Models\ApplicationState;
use App\Repositories\ApplicationStateRepository;
use App\Services\Database\JsonQueryOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ApplicationStateRepository::class);

        // Create test data
        $this->seedTestData();
    }

    public function test_application_state_queries_are_optimized()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Perform common operations
        $states = $this->repository->getByChannel('web', 50);
        $this->assertCount(50, $states);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(0.5, $duration, 'Query took too long: '.$duration.'s');
        $this->assertLessThan(5, $queryCount, 'Too many queries executed: '.$queryCount);
    }

    public function test_json_field_queries_are_optimized()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test JSON field queries
        $states = $this->repository->getByEmployer('goz-ssb');
        $this->assertGreaterThan(0, $states->count());

        $emailStates = $this->repository->getByEmail('test@example.com');
        $this->assertGreaterThan(0, $emailStates->count());

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(1.0, $duration, 'JSON queries took too long: '.$duration.'s');
        $this->assertLessThan(10, $queryCount, 'Too many queries for JSON operations: '.$queryCount);
    }

    public function test_pagination_performance()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test pagination
        $page1 = $this->repository->getPaginatedOptimized([], 15);
        $this->assertEquals(15, $page1->count());

        $page2 = $this->repository->getPaginatedOptimized([], 15);
        $this->assertEquals(15, $page2->count());

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(0.3, $duration, 'Pagination took too long: '.$duration.'s');
        $this->assertLessThan(6, $queryCount, 'Too many queries for pagination: '.$queryCount);
    }

    public function test_complex_filter_performance()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test complex filtering
        $filters = [
            'channel' => 'web',
            'current_step' => 'completed',
            'employer' => 'goz-ssb',
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'min_amount' => 1000,
            'max_amount' => 50000,
        ];

        $results = $this->repository->getPaginatedOptimized($filters, 20);
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $results);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(1.0, $duration, 'Complex filtering took too long: '.$duration.'s');
        $this->assertLessThan(8, $queryCount, 'Too many queries for complex filtering: '.$queryCount);
    }

    public function test_search_performance()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test search functionality
        $searchResults = $this->repository->getPaginatedOptimized([
            'search' => 'John',
        ], 10);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $searchResults);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(0.8, $duration, 'Search took too long: '.$duration.'s');
        $this->assertLessThan(6, $queryCount, 'Too many queries for search: '.$queryCount);
    }

    public function test_bulk_operations_performance()
    {
        $startTime = microtime(true);

        // Test bulk creation
        $bulkData = [];
        for ($i = 0; $i < 100; $i++) {
            $bulkData[] = [
                'session_id' => 'bulk-session-'.$i,
                'channel' => 'web',
                'user_identifier' => 'bulk-user-'.$i.'@example.com',
                'current_step' => 'form',
                'form_data' => json_encode(['test' => 'data']),
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('application_states')->insert($bulkData);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(2.0, $duration, 'Bulk insert took too long: '.$duration.'s');
    }

    public function test_concurrent_access_performance()
    {
        $startTime = microtime(true);
        $operations = [];

        // Simulate concurrent operations
        for ($i = 0; $i < 10; $i++) {
            $operations[] = function () {
                return $this->repository->getByChannel('web', 10);
            };
        }

        // Execute operations
        $results = [];
        foreach ($operations as $operation) {
            $results[] = $operation();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Verify all operations completed
        $this->assertCount(10, $results);
        foreach ($results as $result) {
            $this->assertCount(10, $result);
        }

        // Performance assertions
        $this->assertLessThan(3.0, $duration, 'Concurrent operations took too long: '.$duration.'s');
    }

    public function test_memory_usage_during_large_queries()
    {
        $memoryBefore = memory_get_usage(true);

        // Process large dataset in chunks
        $processedCount = 0;
        $this->repository->processInChunks(function ($states) use (&$processedCount) {
            $processedCount += $states->count();
            // Simulate processing
            foreach ($states as $state) {
                $data = $state->form_data;
            }
        }, 50);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory assertions
        $this->assertGreaterThan(0, $processedCount);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage too high: '.($memoryUsed / 1024 / 1024).'MB');
    }

    public function test_index_effectiveness()
    {
        // Test that indexes are being used
        $query = ApplicationState::where('channel', 'web')
            ->where('current_step', 'completed')
            ->where('created_at', '>=', now()->subDays(7));

        $explainResult = DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());

        // Verify index usage (this is database-specific)
        if (config('database.default') === 'mysql') {
            $this->assertStringContainsString('index', strtolower(json_encode($explainResult)));
        }
    }

    public function test_json_query_optimizer_performance()
    {
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test optimized JSON queries
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'employer', 'goz-ssb');
        $results = $query->get();

        $this->assertGreaterThan(0, $results->count());

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(0.5, $duration, 'Optimized JSON query took too long: '.$duration.'s');
        $this->assertEquals(1, $queryCount, 'Should execute exactly one query');
    }

    public function test_cache_performance_impact()
    {
        // Test without cache
        $startTime = microtime(true);
        $result1 = $this->repository->getByChannel('web', 20);
        $durationWithoutCache = microtime(true) - $startTime;

        // Test with cache (second call should be faster)
        $startTime = microtime(true);
        $result2 = $this->repository->getByChannel('web', 20);
        $durationWithCache = microtime(true) - $startTime;

        // Verify results are the same
        $this->assertEquals($result1->count(), $result2->count());

        // Cache should improve performance significantly
        $this->assertLessThan($durationWithoutCache * 0.5, $durationWithCache,
            'Cache did not improve performance significantly');
    }

    /**
     * Seed test data for performance testing
     */
    private function seedTestData(): void
    {
        $employers = ['goz-ssb', 'large-corporate', 'entrepreneur', 'parastatal'];
        $channels = ['web', 'whatsapp', 'mobile_app'];
        $steps = ['language', 'intent', 'employer', 'form', 'documents', 'completed'];

        for ($i = 0; $i < 1000; $i++) {
            ApplicationState::factory()->create([
                'channel' => $channels[array_rand($channels)],
                'current_step' => $steps[array_rand($steps)],
                'form_data' => [
                    'employer' => $employers[array_rand($employers)],
                    'formResponses' => [
                        'firstName' => 'John'.$i,
                        'lastName' => 'Doe'.$i,
                        'emailAddress' => 'test'.$i.'@example.com',
                        'mobile' => '+26377'.str_pad($i, 7, '0', STR_PAD_LEFT),
                        'loanAmount' => rand(1000, 100000),
                    ],
                ],
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
