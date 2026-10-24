<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\User\SecurityController;
use Illuminate\Support\Facades\Route;

Route::prefix('security')->name('security.')->group(function () {
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa');
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetup'])->middleware('throttle:otp-send')->name('2fa.setup');
    Route::get('/2fa/confirm', [TwoFactorController::class, 'showConfirmation'])->name('2fa.confirm');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:otp-verify')->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:otp-verify')->name('2fa.disable');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:otp-verify')->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:otp-send')->name('2fa.resend');

    Route::get('/dashboard', [SecurityController::class, 'dashboard'])->name('dashboard');
    Route::get('/sessions', [SecurityController::class, 'sessions'])->name('sessions');
    Route::get('/activity', [SecurityController::class, 'activity'])->name('activity');

    Route::post('/sessions/{sessionId}/terminate', [SecurityController::class, 'terminateSession'])->middleware('throttle:sensitive')->name('sessions.terminate');
    Route::post('/sessions/terminate-all', [SecurityController::class, 'terminateAllSessions'])->middleware('throttle:sensitive')->name('sessions.terminate-all');
});
