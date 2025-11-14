<?php

namespace App\Services\Cache;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApplicationCacheManager
{
    private const CACHE_TTL = 3600; // 1 hour default

    private const CACHE_PREFIX = 'bancozim:';

    /**
     * Cache application state
     */
    public function cacheApplicationState(ApplicationState $applicationState, ?int $ttl = null): void
    {
        $ttl = $ttl ?? self::CACHE_TTL;

        $cacheKey = $this->getApplicationStateKey($applicationState->session_id);

        $cacheData = [
            'id' => $applicationState->id,
            'session_id' => $applicationState->session_id,
            'channel' => $applicationState->channel,
            'user_identifier' => $applicationState->user_identifier,
            'current_step' => $applicationState->current_step,
            'form_data' => $applicationState->form_data,
            'metadata' => $applicationState->metadata,
            'expires_at' => $applicationState->expires_at?->toISOString(),
            'reference_code' => $applicationState->reference_code,
            'reference_code_expires_at' => $applicationState->reference_code_expires_at?->toISOString(),
            'created_at' => $applicationState->created_at->toISOString(),
            'updated_at' => $applicationState->updated_at->toISOString(),
            'cached_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $cacheData, $ttl);

        // Also cache by user identifier for quick lookups
        $userKey = $this->getUserApplicationKey($applicationState->user_identifier, $applicationState->channel);
        Cache::put($userKey, $applicationState->session_id, $ttl);

        Log::debug('Application state cached', [
            'session_id' => $applicationState->session_id,
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Get cached application state
     */
    public function getCachedApplicationState(string $sessionId): ?array
    {
        $cacheKey = $this->getApplicationStateKey($sessionId);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::debug('Application state cache hit', [
                'session_id' => $sessionId,
                'cache_key' => $cacheKey,
            ]);
        }

        return $cached;
    }

    /**
     * Get session ID by user identifier
     */
    public function getSessionIdByUser(string $userIdentifier, string $channel): ?string
    {
        $userKey = $this->getUserApplicationKey($userIdentifier, $channel);

        return Cache::get($userKey);
    }

    /**
     * Invalidate application state cache
     */
    public function invalidateApplicationState(string $sessionId): void
    {
        $cacheKey = $this->getApplicationStateKey($sessionId);
        Cache::forget($cacheKey);

        Log::debug('Application state cache invalidated', [
            'session_id' => $sessionId,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Invalidate user application cache
     */
    public function invalidateUserApplication(string $userIdentifier, string $channel): void
    {
        $userKey = $this->getUserApplicationKey($userIdentifier, $channel);
        Cache::forget($userKey);

        Log::debug('User application cache invalidated', [
            'user_identifier' => $userIdentifier,
            'channel' => $channel,
            'cache_key' => $userKey,
        ]);
    }

    /**
     * Cache form configuration
     */
    public function cacheFormConfig(string $formId, array $config, int $ttl = 7200): void
    {
        $cacheKey = $this->getFormConfigKey($formId);
        Cache::put($cacheKey, $config, $ttl);

        Log::debug('Form configuration cached', [
            'form_id' => $formId,
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Get cached form configuration
     */
    public function getCachedFormConfig(string $formId): ?array
    {
        $cacheKey = $this->getFormConfigKey($formId);

        return Cache::get($cacheKey);
    }

    /**
     * Cache business products
     */
    public function cacheBusinessProducts(array $products, int $ttl = 3600): void
    {
        $cacheKey = $this->getBusinessProductsKey();
        Cache::put($cacheKey, $products, $ttl);

        Log::debug('Business products cached', [
            'cache_key' => $cacheKey,
            'count' => count($products),
            'ttl' => $ttl,
        ]);
    }

    /**
     * Get cached business products
     */
    public function getCachedBusinessProducts(): ?array
    {
        $cacheKey = $this->getBusinessProductsKey();

        return Cache::get($cacheKey);
    }

    /**
     * Cache application statistics
     */
    public function cacheStatistics(array $statistics, int $ttl = 1800): void
    {
        $cacheKey = $this->getStatisticsKey();
        Cache::put($cacheKey, $statistics, $ttl);

        Log::debug('Statistics cached', [
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Get cached statistics
     */
    public function getCachedStatistics(): ?array
    {
        $cacheKey = $this->getStatisticsKey();

        return Cache::get($cacheKey);
    }

    /**
     * Cache reference code validation
     */
    public function cacheReferenceCodeValidation(string $referenceCode, bool $isValid, int $ttl = 300): void
    {
        $cacheKey = $this->getReferenceCodeKey($referenceCode);
        Cache::put($cacheKey, $isValid, $ttl);

        Log::debug('Reference code validation cached', [
            'reference_code' => $referenceCode,
            'is_valid' => $isValid,
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Get cached reference code validation
     */
    public function getCachedReferenceCodeValidation(string $referenceCode): ?bool
    {
        $cacheKey = $this->getReferenceCodeKey($referenceCode);

        return Cache::get($cacheKey);
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmUpCache(): void
    {
        Log::info('Starting cache warm-up');

        try {
            // Warm up business products
            // This would typically fetch from database and cache
            Log::info('Cache warm-up completed');

        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear all application-related cache
     */
    public function clearAllCache(): void
    {
        $patterns = [
            self::CACHE_PREFIX.'app_state:*',
            self::CACHE_PREFIX.'user_app:*',
            self::CACHE_PREFIX.'form_config:*',
            self::CACHE_PREFIX.'business_products',
            self::CACHE_PREFIX.'statistics',
            self::CACHE_PREFIX.'ref_code:*',
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }

        Log::info('All application cache cleared');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        // This is a simplified implementation
        // In production, you might want to use Redis commands to get actual stats
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => self::CACHE_PREFIX,
            'default_ttl' => self::CACHE_TTL,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate cache key for application state
     */
    private function getApplicationStateKey(string $sessionId): string
    {
        return self::CACHE_PREFIX.'app_state:'.$sessionId;
    }

    /**
     * Generate cache key for user application lookup
     */
    private function getUserApplicationKey(string $userIdentifier, string $channel): string
    {
        return self::CACHE_PREFIX.'user_app:'.md5($userIdentifier.':'.$channel);
    }

    /**
     * Generate cache key for form configuration
     */
    private function getFormConfigKey(string $formId): string
    {
        return self::CACHE_PREFIX.'form_config:'.$formId;
    }

    /**
     * Generate cache key for business products
     */
    private function getBusinessProductsKey(): string
    {
        return self::CACHE_PREFIX.'business_products';
    }

    /**
     * Generate cache key for statistics
     */
    private function getStatisticsKey(): string
    {
        return self::CACHE_PREFIX.'statistics';
    }

    /**
     * Generate cache key for reference code validation
     */
    private function getReferenceCodeKey(string $referenceCode): string
    {
        return self::CACHE_PREFIX.'ref_code:'.$referenceCode;
    }

    /**
     * Clear cache by pattern (Redis specific)
     */
    private function clearCacheByPattern(string $pattern): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys($pattern);
                if (! empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // For other cache drivers, we can't easily clear by pattern
                Log::warning('Pattern-based cache clearing not supported for driver: '.config('cache.default'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
