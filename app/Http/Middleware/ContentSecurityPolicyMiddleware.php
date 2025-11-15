<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip CSP in development to avoid Vite issues
        if (app()->environment('production')) {
            // Only apply CSP to HTML responses in production
            if ($this->shouldApplyCSP($response)) {
                $csp = $this->buildCSP();
                $response->headers->set('Content-Security-Policy', $csp);

                // Also set other security headers
                $this->setSecurityHeaders($response);
            }
        }

        return $response;
    }

    /**
     * Check if CSP should be applied to this response
     */
    private function shouldApplyCSP(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        
        return str_contains($contentType, 'text/html') || 
               empty($contentType); // Default for HTML responses
    }

    /**
     * Build the Content Security Policy string
     */
    private function buildCSP(): string
    {
        $isProduction = app()->environment('production');

        if ($isProduction) {
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https:",
                "connect-src 'self'",
                "media-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "upgrade-insecure-requests",
            ];
        } else {
            // Development mode - more permissive for Vite
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* http://127.0.0.1:* http://[::1]:*",
                "style-src 'self' 'unsafe-inline' http://fonts.googleapis.com https://fonts.googleapis.com http://fonts.bunny.net https://fonts.bunny.net",
                "font-src 'self' http://fonts.gstatic.com https://fonts.gstatic.com http://fonts.bunny.net https://fonts.bunny.net data:",
                "img-src 'self' data: blob: http: https:",
                "connect-src 'self' http://localhost:* http://127.0.0.1:* http://[::1]:* ws://localhost:* ws://127.0.0.1:* ws://[::1]:* wss://localhost:* wss://127.0.0.1:* wss://[::1]:*",
                "media-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ];
        }

        return implode('; ', $policies);
    }

    /**
     * Set additional security headers
     */
    private function setSecurityHeaders(Response $response): void
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        // Add HSTS in production with HTTPS
        if (app()->environment('production') && request()->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
    }
}
