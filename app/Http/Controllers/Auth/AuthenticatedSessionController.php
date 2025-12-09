<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect based on role or intended url
        $user = Auth::user();
        
        if ($user) {
            if ($user->is_admin || $user->role === \App\Models\User::ROLE_SUPER_ADMIN) {
                return redirect()->intended('/admin');
            } elseif ($user->role === \App\Models\User::ROLE_ZB_ADMIN) {
                return redirect()->intended('/zb-admin');
            } elseif ($user->role === \App\Models\User::ROLE_ACCOUNTING) {
                return redirect()->intended('/accounting');
            } elseif ($user->role === \App\Models\User::ROLE_STORES) {
                return redirect()->intended('/stores');
            } elseif ($user->role === \App\Models\User::ROLE_HR) {
                return redirect()->intended('/hr');
            } elseif ($user->role === \App\Models\User::ROLE_PARTNER) {
                return redirect()->intended('/partner');
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
