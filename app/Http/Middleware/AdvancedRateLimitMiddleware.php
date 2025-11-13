<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AdvancedRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limiter = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Check if IP is blocked
        if ($this->isBlocked($request->ip())) {
            return $this->blocked($request);
        }

        // Apply rate limiting
        $executed = RateLimiter::attempt(
            $key,
            $this->getMaxAttempts($limiter),
            function () use ($next, $request) {
                return $next($request);
            },
            $this->getDecayMinutes($limiter)
        );

        if (!$executed) {
            $this->handleRateLimitExceeded($request, $key);
            return $this->buildResponse($request, $key);
        }

        return $executed;
    }

    /**
     * Resolve the rate limiting key for the request
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? 'unknown';
        $userAgent = md5($request->userAgent() ?? '');
        
        // Include user ID if authenticated
        $userId = $request->user()?->id ?? 'guest';
        
        return "rate_limit:{$ip}:{$route}:{$userId}:{$userAgent}";
    }

    /**
     * Get maximum attempts for the limiter
     */
    protected function getMaxAttempts(string $limiter): int
    {
        return match ($limiter) {
            'api' => 60,
            'auth' => 5,
            'pdf' => 10,
            'upload' => 20,
            'strict' => 10,
            default => 60,
        };
    }

    /**
     * Get decay minutes for the limiter
     */
    protected function getDecayMinutes(string $limiter): int
    {
        return match ($limiter) {
            'api' => 1,
            'auth' => 15,
            'pdf' => 5,
            'upload' => 10,
            'strict' => 5,
            default => 1,
        };
    }

    /**
     * Check if IP is blocked
     */
    protected function isBlocked(string $ip): bool
    {
        $blockKey = "blocked_ip:{$ip}";
        return Cache::has($blockKey);
    }

    /**
     * Handle rate limit exceeded
     */
    protected function handleRateLimitExceeded(Request $request, string $key): void
    {
        $ip = $request->ip();
        $violations = $this->incrementViolations($ip);
        
        Log::warning('Rate limit exceeded', [
            'ip' => $ip,
            'key' => $key,
            'violations' => $violations,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        // Block IP after multiple violations
        if ($violations >= 5) {
            $this->blockIp($ip, 3600); // Block for 1 hour
            
            Log::alert('IP blocked due to repeated rate limit violations', [
                'ip' => $ip,
                'violations' => $violations,
                'blocked_until' => now()->addHour()->toISOString(),
            ]);
        }
    }

    /**
     * Increment violation count for IP
     */
    protected function incrementViolations(string $ip): int
    {
        $violationKey = "violations:{$ip}";
        $violations = Cache::get($violationKey, 0) + 1;
        
        Cache::put($violationKey, $violations, 3600); // Store for 1 hour
        
        return $violations;
    }

    /**
     * Block IP address
     */
    protected function blockIp(string $ip, int $seconds): void
    {
        $blockKey = "blocked_ip:{$ip}";
        Cache::put($blockKey, true, $seconds);
        
        // Also add to permanent block list if too many blocks
        $blockCount = Cache::get("block_count:{$ip}", 0) + 1;
        Cache::put("block_count:{$ip}", $blockCount, 86400); // 24 hours
        
        if ($blockCount >= 3) {
            // Add to permanent block list (would typically be in database)
            Log::critical('IP added to permanent block list', [
                'ip' => $ip,
                'block_count' => $blockCount,
            ]);
        }
    }

    /**
     * Build rate limit response
     */
    protected function buildResponse(Request $request, string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        $response = response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429);

        $response->headers->set('Retry-After', $retryAfter);
        $response->headers->set('X-RateLimit-Limit', $this->getMaxAttempts('default'));
        $response->headers->set('X-RateLimit-Remaining', 0);
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);

        return $response;
    }

    /**
     * Build blocked response
     */
    protected function blocked(Request $request): Response
    {
        Log::warning('Blocked IP attempted access', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return response()->json([
            'error' => 'Access Denied',
            'message' => 'Your IP address has been temporarily blocked.',
        ], 403);
    }

    /**
     * Check for suspicious patterns in requests
     */
    protected function isSuspiciousRequest(Request $request): bool
    {
        // Check for common attack patterns
        $suspiciousPatterns = [
            '/\.\.[\/\\]/',
            '/\b(union|select|insert|update|delete|drop)\b/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
        ];

        $content = $request->getContent();
        $queryString = $request->getQueryString();
        $userAgent = $request->userAgent() ?? '';

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content) || 
                preg_match($pattern, $queryString) || 
                preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply adaptive rate limiting based on request patterns
     */
    protected function getAdaptiveLimit(Request $request): int
    {
        $ip = $request->ip();
        $baseLimit = 60;

        // Reduce limit for suspicious requests
        if ($this->isSuspiciousRequest($request)) {
            return max(5, $baseLimit * 0.1);
        }

        // Check request frequency pattern
        $recentRequests = $this->getRecentRequestCount($ip);
        
        if ($recentRequests > 100) {
            return max(10, $baseLimit * 0.2);
        }

        if ($recentRequests > 50) {
            return max(20, $baseLimit * 0.5);
        }

        return $baseLimit;
    }

    /**
     * Get recent request count for IP
     */
    protected function getRecentRequestCount(string $ip): int
    {
        $key = "request_count:{$ip}";
        $count = Cache::get($key, 0);
        
        Cache::put($key, $count + 1, 300); // 5 minutes
        
        return $count;
    }

    /**
     * Whitelist certain IPs or user agents
     */
    protected function isWhitelisted(Request $request): bool
    {
        $whitelistedIps = config('security.whitelisted_ips', []);
        $whitelistedUserAgents = config('security.whitelisted_user_agents', []);

        if (in_array($request->ip(), $whitelistedIps)) {
            return true;
        }

        $userAgent = $request->userAgent() ?? '';
        foreach ($whitelistedUserAgents as $pattern) {
            if (fnmatch($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rate limit statistics
     */
    public function getStatistics(string $ip): array
    {
        return [
            'violations' => Cache::get("violations:{$ip}", 0),
            'block_count' => Cache::get("block_count:{$ip}", 0),
            'is_blocked' => $this->isBlocked($ip),
            'recent_requests' => $this->getRecentRequestCount($ip),
        ];
    }
}
