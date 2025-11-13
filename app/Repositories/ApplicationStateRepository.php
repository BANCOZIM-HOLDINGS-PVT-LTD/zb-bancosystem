<?php

namespace App\Repositories;

use App\Models\ApplicationState;
use App\Services\Database\JsonQueryOptimizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApplicationStateRepository
{
    /**
     * Find application state by session ID
     */
    public function findBySessionId(string $sessionId): ?ApplicationState
    {
        return ApplicationState::where('session_id', $sessionId)->first();
    }

    /**
     * Find application state by reference code
     */
    public function findByReferenceCode(string $referenceCode): ?ApplicationState
    {
        return ApplicationState::where('reference_code', $referenceCode)->first();
    }

    /**
     * Find application state by national ID
     */
    public function findByNationalId(string $nationalId): ?ApplicationState
    {
        return ApplicationState::where('national_id', $nationalId)->first();
    }

    /**
     * Find application states by user identifier
     */
    public function findByUserIdentifier(string $userIdentifier, string $channel = null): Collection
    {
        $query = ApplicationState::where('user_identifier', $userIdentifier);

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create new application state
     */
    public function create(array $data): ApplicationState
    {
        return ApplicationState::create($data);
    }

    /**
     * Update application state
     */
    public function update(ApplicationState $applicationState, array $data): bool
    {
        return $applicationState->update($data);
    }

    /**
     * Delete application state (soft delete)
     */
    public function delete(ApplicationState $applicationState): bool
    {
        return $applicationState->delete();
    }

    /**
     * Get application states by current step
     */
    public function getByCurrentStep(string $step, int $limit = null): Collection
    {
        $query = ApplicationState::where('current_step', $step)
            ->orderBy('updated_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get expired application states
     */
    public function getExpired(): Collection
    {
        return ApplicationState::where('expires_at', '<', now())
            ->whereNotNull('expires_at')
            ->get();
    }

    /**
     * Get application states by channel
     */
    public function getByChannel(string $channel, int $limit = null): Collection
    {
        $query = ApplicationState::where('channel', $channel)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get application states created within date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return ApplicationState::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get paginated application states with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ApplicationState::query();

        // Apply filters
        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['current_step'])) {
            $query->where('current_step', $filters['current_step']);
        }

        if (isset($filters['user_identifier'])) {
            $query->where('user_identifier', 'like', '%' . $filters['user_identifier'] . '%');
        }

        if (isset($filters['reference_code'])) {
            $query->where('reference_code', 'like', '%' . $filters['reference_code'] . '%');
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhere('user_identifier', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%")
                  ->orWhereJsonContains('form_data->formResponses->firstName', $search)
                  ->orWhereJsonContains('form_data->formResponses->lastName', $search)
                  ->orWhereJsonContains('form_data->formResponses->surname', $search);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $total = ApplicationState::count();
        $byChannel = ApplicationState::selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();

        $byStep = ApplicationState::selectRaw('current_step, count(*) as count')
            ->groupBy('current_step')
            ->pluck('count', 'current_step')
            ->toArray();

        $recentCount = ApplicationState::where('created_at', '>=', now()->subDays(7))->count();
        $completedCount = ApplicationState::where('current_step', 'completed')->count();

        return [
            'total' => $total,
            'by_channel' => $byChannel,
            'by_step' => $byStep,
            'recent_week' => $recentCount,
            'completed' => $completedCount,
            'completion_rate' => $total > 0 ? round(($completedCount / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Cleanup expired sessions
     */
    public function cleanupExpired(): int
    {
        $expired = $this->getExpired();
        $count = $expired->count();

        foreach ($expired as $applicationState) {
            $applicationState->delete();
        }

        return $count;
    }

    /**
     * Get application states that need reference codes
     */
    public function getNeedingReferenceCodes(): Collection
    {
        return ApplicationState::whereNull('reference_code')
            ->where('current_step', '!=', 'language')
            ->where('current_step', '!=', 'intent')
            ->get();
    }

    /**
     * Bulk update application states
     */
    public function bulkUpdate(array $sessionIds, array $data): int
    {
        return ApplicationState::whereIn('session_id', $sessionIds)->update($data);
    }

    /**
     * Get application states with transitions
     */
    public function getWithTransitions(string $sessionId): ?ApplicationState
    {
        return ApplicationState::with('transitions')
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * Search application states by form data (optimized)
     */
    public function searchByFormData(string $field, string $value): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', "formResponses.{$field}", $value);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get application states by metadata (optimized)
     */
    public function getByMetadata(string $key, string $value): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'metadata', $key, $value);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Count application states by criteria
     */
    public function countByCriteria(array $criteria): int
    {
        $query = ApplicationState::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * Get application states by employer (optimized)
     */
    public function getByEmployer(string $employer): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'employer', $employer);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get application states by loan amount range (optimized)
     */
    public function getByAmountRange(float $minAmount, float $maxAmount): Collection
    {
        $query = ApplicationState::query();

        $query->where(function ($q) use ($minAmount, $maxAmount) {
            JsonQueryOptimizer::optimizeJsonQuery($q, 'form_data', 'amount', $minAmount, '>=');
            JsonQueryOptimizer::optimizeJsonQuery($q, 'form_data', 'amount', $maxAmount, '<=');
        });

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get application states by email (optimized)
     */
    public function getByEmail(string $email): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formResponses.emailAddress', $email);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get application states by mobile number (optimized)
     */
    public function getByMobile(string $mobile): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formResponses.mobile', $mobile);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get application states by national ID (optimized)
     */
    public function getByNationalId(string $nationalId): Collection
    {
        $query = ApplicationState::query();
        JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formResponses.nationalIdNumber', $nationalId);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get statistics by employer (optimized)
     */
    public function getStatisticsByEmployer(): array
    {
        $query = ApplicationState::query();

        // Use optimized grouping
        JsonQueryOptimizer::groupByJson($query, 'form_data', 'employer');

        $results = $query->selectRaw('COUNT(*) as count')
            ->get()
            ->pluck('count', 'employer')
            ->toArray();

        return $results;
    }

    /**
     * Get applications with eager loading for performance
     */
    public function getWithEagerLoading(array $relations = ['transitions']): Collection
    {
        return ApplicationState::with($relations)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get applications using chunking for large datasets
     */
    public function processInChunks(callable $callback, int $chunkSize = 1000): void
    {
        ApplicationState::orderBy('id')
            ->chunk($chunkSize, $callback);
    }

    /**
     * Get applications with optimized pagination
     */
    public function getPaginatedOptimized(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ApplicationState::query();

        // Apply optimized filters
        $this->applyOptimizedFilters($query, $filters);

        // Use cursor pagination for better performance on large datasets
        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // Secondary sort for consistency
            ->paginate($perPage);
    }

    /**
     * Apply optimized filters to query
     */
    private function applyOptimizedFilters(Builder $query, array $filters): void
    {
        // Standard field filters
        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['current_step'])) {
            $query->where('current_step', $filters['current_step']);
        }

        if (isset($filters['user_identifier'])) {
            $query->where('user_identifier', 'like', '%' . $filters['user_identifier'] . '%');
        }

        if (isset($filters['reference_code'])) {
            $query->where('reference_code', 'like', '%' . $filters['reference_code'] . '%');
        }

        // Date range filters with indexes
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Optimized JSON field filters
        if (isset($filters['employer'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'employer', $filters['employer']);
        }

        if (isset($filters['form_id'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formId', $filters['form_id']);
        }

        if (isset($filters['has_account'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'hasAccount', $filters['has_account']);
        }

        if (isset($filters['email'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formResponses.emailAddress', $filters['email'], 'like');
        }

        if (isset($filters['mobile'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'formResponses.mobile', $filters['mobile'], 'like');
        }

        // Amount range filter
        if (isset($filters['min_amount'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'amount', $filters['min_amount'], '>=');
        }

        if (isset($filters['max_amount'])) {
            JsonQueryOptimizer::optimizeJsonQuery($query, 'form_data', 'amount', $filters['max_amount'], '<=');
        }

        // Full-text search optimization
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhere('user_identifier', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%");

                // Add optimized JSON searches
                JsonQueryOptimizer::optimizeJsonQuery($q, 'form_data', 'formResponses.firstName', $search, 'like');
                JsonQueryOptimizer::optimizeJsonQuery($q, 'form_data', 'formResponses.lastName', $search, 'like');
                JsonQueryOptimizer::optimizeJsonQuery($q, 'form_data', 'formResponses.surname', $search, 'like');
            });
        }
    }
}
