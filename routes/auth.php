<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Auth\ClientRegisterController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Client Registration (National ID + Phone + OTP)
    Route::get('client/register', [ClientRegisterController::class, 'create'])
        ->name('client.register');

    Route::post('client/register', [ClientRegisterController::class, 'store']);

    Route::get('client/verify-otp', [ClientRegisterController::class, 'showOtpForm'])
        ->name('client.otp.verify');

    Route::post('client/verify-otp', [ClientRegisterController::class, 'verifyOtp']);

    Route::post('client/resend-otp', [ClientRegisterController::class, 'resendOtp'])
        ->name('client.otp.resend');

    // Client Login (National ID only)
    Route::get('client/login', [ClientLoginController::class, 'create'])
        ->name('client.login');

    Route::post('client/login', [ClientLoginController::class, 'store']);

    // Admin Login (Email + Password) - For admin access only
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
