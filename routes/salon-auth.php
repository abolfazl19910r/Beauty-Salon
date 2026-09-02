<?php

use App\Http\Controllers\Salon\Auth\CustomerAuthenticatedController;
use App\Http\Controllers\Salon\Auth\CustomerPasswordResetController;
use App\Http\Controllers\Salon\Auth\CustomerRegisteredController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ Customer identity redesign (confirmed 2026-08-30). Required from routes/web.php inside the
 * group that already applies prefix('s/{salon_slug}')->middleware(['salon.resolve']) — same
 * convention as web/public.php. Route names are prefixed `salon.` (salon.register, salon.login,
 * ...) specifically so they never collide with the existing global `register`/`login` names
 * (App\Http\Controllers\Auth\*), which stay untouched for admin/specialist accounts.
 *
 * Reuses the same named rate limiters (throttle:registration, throttle:auth) as the staff auth
 * routes in web/auth.php — the abuse this guards against (SMS spam, phone enumeration,
 * credential/OTP guessing) applies identically to the customer flow, and RouteServiceProvider
 * defines these limiters by name, not by route, so no new limiter definitions are needed.
 */
Route::middleware('guest')->group(function () {
    Route::get('register', [CustomerRegisteredController::class, 'create'])->name('salon.register');
    Route::post('register', [CustomerRegisteredController::class, 'store'])->middleware('throttle:registration');
    Route::get('register/verify', [CustomerRegisteredController::class, 'showVerify'])->name('salon.register.verify.show');
    Route::post('register/verify', [CustomerRegisteredController::class, 'verify'])->name('salon.register.verify')->middleware('throttle:registration');
    Route::post('register/resend', [CustomerRegisteredController::class, 'resendCode'])->name('salon.register.resend')->middleware('throttle:registration');

    Route::get('login', [CustomerAuthenticatedController::class, 'create'])->name('salon.login');
    Route::post('login', [CustomerAuthenticatedController::class, 'store'])->middleware('throttle:auth');
    Route::get('login/verify', [CustomerAuthenticatedController::class, 'showVerify'])->name('salon.login.verify.show');
    Route::post('login/verify', [CustomerAuthenticatedController::class, 'verify'])->name('salon.login.verify')->middleware('throttle:auth');
    Route::post('login/resend', [CustomerAuthenticatedController::class, 'resendCode'])->name('salon.login.resend')->middleware('throttle:auth');

    // ⭐ Customer identity redesign, item 3 (confirmed 2026-08-30).
    Route::get('forgot-password', [CustomerPasswordResetController::class, 'create'])->name('salon.password.request');
    Route::post('forgot-password', [CustomerPasswordResetController::class, 'sendCode'])->name('salon.password.send')->middleware('throttle:auth');
    Route::get('reset-password/{token}', [CustomerPasswordResetController::class, 'showReset'])->name('salon.password.verify');
    Route::post('reset-password', [CustomerPasswordResetController::class, 'reset'])->name('salon.password.store')->middleware('throttle:auth');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [CustomerAuthenticatedController::class, 'destroy'])->name('salon.logout');
});
