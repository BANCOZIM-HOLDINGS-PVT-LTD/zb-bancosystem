<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestSizeLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxSizeInMB = 50): Response
    {
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;
        $contentLength = $request->header('Content-Length');

        if ($contentLength && $contentLength > $maxSizeInBytes) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'error' => 'Request too large',
                    'message' => "Request size exceeds the maximum allowed size of {$maxSizeInMB}MB",
                    'max_size_mb' => $maxSizeInMB,
                ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }

            abort(413, "Request size exceeds the maximum allowed size of {$maxSizeInMB}MB");
        }

        return $next($request);
    }
}
