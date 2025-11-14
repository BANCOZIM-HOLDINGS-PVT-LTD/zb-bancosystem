<?php

namespace App\Contracts;

use App\Models\ApplicationState;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicationStateRepositoryInterface
{
    /**
     * Find application state by session ID
     */
    public function findBySessionId(string $sessionId): ?ApplicationState;

    /**
     * Find application state by reference code
     */
    public function findByReferenceCode(string $referenceCode): ?ApplicationState;

    /**
     * Create new application state
     */
    public function create(array $data): ApplicationState;

    /**
     * Update application state
     */
    public function update(ApplicationState $applicationState, array $data): bool;

    /**
     * Delete application state
     */
    public function delete(ApplicationState $applicationState): bool;

    /**
     * Get paginated application states with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get statistics
     */
    public function getStatistics(): array;

    /**
     * Cleanup expired sessions
     */
    public function cleanupExpired(): int;
}
