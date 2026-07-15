<?php

use App\Http\Controllers\User\SecurePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments/secure')->middleware('verified.2fa')->group(function () {
    Route::post('/initiate', [SecurePaymentController::class, 'initiate']);
    Route::post('/verify', [SecurePaymentController::class, 'verify']);
    Route::get('/{reference}/status', [SecurePaymentController::class, 'checkStatus']);
});
