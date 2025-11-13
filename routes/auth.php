<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Auth\ClientRegisterController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
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

    // Client Login (National ID + OTP)
    Route::get('client/login', [ClientLoginController::class, 'create'])
        ->name('client.login');

    Route::post('client/login', [ClientLoginController::class, 'store']);

    Route::get('client/login/verify-otp', [ClientLoginController::class, 'showOtpForm'])
        ->name('client.login.otp');

    Route::post('client/login/verify-otp', [ClientLoginController::class, 'verifyOtp'])
        ->name('client.login.otp.verify');

    Route::post('client/login/resend-otp', [ClientLoginController::class, 'resendOtp'])
        ->name('client.login.otp.resend');

    // Admin Registration & Login (Email + Password) - Keep existing
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

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
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
