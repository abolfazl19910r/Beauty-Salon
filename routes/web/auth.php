<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('register/verify', [RegisteredUserController::class, 'showVerify'])->name('register.verify.show');
    Route::post('register/verify', [RegisteredUserController::class, 'verify'])->name('register.verify');
    Route::post('register/resend', [RegisteredUserController::class, 'resendCode'])->name('register.resend');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('login/verify', [AuthenticatedSessionController::class, 'showVerify'])->name('login.verify.show');
    Route::post('login/verify', [AuthenticatedSessionController::class, 'verify'])->name('login.verify');
    Route::post('login/resend', [AuthenticatedSessionController::class, 'resendCode'])->name('login.resend');
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
});
