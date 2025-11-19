<?php

use App\Http\Middleware\ContentSecurityPolicyMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestSizeLimitMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Apply CSP to all web routes if enabled
        if (env('CONTENT_SECURITY_POLICY_ENABLED', true)) {
            $middleware->web(append: [
                ContentSecurityPolicyMiddleware::class,
            ]);
        }

        $middleware->api(append: [
            RequestSizeLimitMiddleware::class . ':10', // 10MB limit for API requests
        ]);

        // Register named middleware
        $middleware->alias([
            'request.size' => RequestSizeLimitMiddleware::class,
            'csp' => ContentSecurityPolicyMiddleware::class,
            'auth.agent' => \App\Http\Middleware\AuthenticateAgent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
