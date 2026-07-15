<?php

use App\Http\Controllers\User\SecurityController;
use Illuminate\Support\Facades\Route;

Route::prefix('security')->name('security.')->group(function () {
    Route::get('/logs', [SecurityController::class, 'adminLogs'])->name('logs');

    Route::get('/users', [SecurityController::class, 'adminUsers'])->name('users');

    Route::get('/settings', [SecurityController::class, 'adminSettings'])->name('settings');
    Route::post('/settings', [SecurityController::class, 'updateSettings'])->name('settings.update');
});
