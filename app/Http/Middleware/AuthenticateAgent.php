<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('agent')->check()) {
            return redirect()->route('filament.agent.auth.login');
        }

        // Check if agent is active
        $agent = Auth::guard('agent')->user();
        if ($agent && $agent->status !== 'active') {
            Auth::guard('agent')->logout();
            return redirect()->route('filament.agent.auth.login')
                ->with('error', 'Your agent account is not active. Please contact support.');
        }

        return $next($request);
    }
}
