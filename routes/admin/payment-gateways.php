<?php

use App\Http\Controllers\Admin\PaymentGateway\AdminPaymentGatewayController;
use Illuminate\Support\Facades\Route;

// ⭐ درگاه‌های پرداخت سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — داخل گروه salon.owner در routes/web.php.
Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
    Route::get('/', [AdminPaymentGatewayController::class, 'index'])->name('index');
    Route::post('/', [AdminPaymentGatewayController::class, 'store'])->name('store');
    Route::put('/{gatewayId}', [AdminPaymentGatewayController::class, 'update'])->whereNumber('gatewayId')->name('update');
    Route::delete('/{gatewayId}', [AdminPaymentGatewayController::class, 'destroy'])->whereNumber('gatewayId')->name('destroy');
    Route::post('/{gatewayId}/move', [AdminPaymentGatewayController::class, 'move'])->whereNumber('gatewayId')->name('move');
    Route::post('/{gatewayId}/test', [AdminPaymentGatewayController::class, 'test'])->whereNumber('gatewayId')->middleware('throttle:10,1')->name('test');
});
