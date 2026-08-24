<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('register/verify', [RegisteredUserController::class, 'showVerify'])->name('register.verify.show');
    Route::post('register/verify', [RegisteredUserController::class, 'verify'])->name('register.verify');
    Route::post('register/resend', [RegisteredUserController::class, 'resendCode'])->name('register.resend');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // ⭐ Wired up (post-test-writing-phase, throttle:auth): these three routes are the actual
    // credential/OTP-guessing surface of the login flow — password check (store), OTP code
    // guess (verify), and resend (SMS-spam vector). The 'auth' rate limiter itself (IP-based,
    // configurable via MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES) was wired up in test-writing
    // session 11 but was not yet attached to any route. Deliberately scoped to only these three
    // login-flow routes (not register/reset-password/phone-verification) because Laravel's named
    // rate limiter shares one cache key per limiter name + ->by() value regardless of route —
    // attaching the same 'auth' limiter to unrelated flows would silently share one combined
    // attempt budget across them (e.g. failed logins counting against a later registration
    // attempt), which the MAX_LOGIN_ATTEMPTS config name does not describe. See
    // Rasta_unified_prompt.md for the full rationale and what was deliberately left out.
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth');
    Route::get('login/verify', [AuthenticatedSessionController::class, 'showVerify'])->name('login.verify.show');
    Route::post('login/verify', [AuthenticatedSessionController::class, 'verify'])->name('login.verify')->middleware('throttle:auth');
    Route::post('login/resend', [AuthenticatedSessionController::class, 'resendCode'])->name('login.resend')->middleware('throttle:auth');
    Route::get('forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.send');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.verify');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // ⭐ Fix (test-writing session 10, option A): real phone-verification page for the
    // 'verified' middleware's failure path — deliberately outside any 'verified'-gated
    // group (that would be circular). See App\Http\Middleware\EnsurePhoneIsVerified.
    Route::get('verify-phone', [PhoneVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('verify-phone/verify', [PhoneVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('verify-phone/resend', [PhoneVerificationController::class, 'resend'])->name('verification.resend');
});
