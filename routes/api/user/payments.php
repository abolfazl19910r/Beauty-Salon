<?php

use App\Http\Controllers\User\SecurePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments/secure')->middleware('2fa.enabled')->name('payments.secure.')->group(function () {
    Route::post('/initiate/{booking}', [SecurePaymentController::class, 'initiate'])->name('initiate');
    Route::get('/{reference}/status', [SecurePaymentController::class, 'checkStatus'])->name('status');
});
