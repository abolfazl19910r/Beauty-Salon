<?php

use App\Http\Controllers\Admin\Security\AdminSecurityController;
use Illuminate\Support\Facades\Route;

Route::prefix('security')->name('security.')->group(function () {
    Route::get('/logs', [AdminSecurityController::class, 'logs'])->name('logs');
    Route::get('/users', [AdminSecurityController::class, 'users'])->name('users');
    Route::get('/settings', [AdminSecurityController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminSecurityController::class, 'updateSettings'])->name('settings.update');
});
