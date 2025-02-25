<?php

use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

Route::prefix('security')->group(function () {
    Route::get('/sessions/active', [SecurityController::class, 'getActiveSessions']);
    Route::post('/sessions/{id}/terminate', [SecurityController::class, 'terminateSession']);
    Route::post('/sessions/terminate-all', [SecurityController::class, 'terminateAllSessions']);

    Route::get('/logs', [SecurityController::class, 'getSecurityLogs']);
    Route::get('/login-history', [SecurityController::class, 'getLoginHistory']);
});
