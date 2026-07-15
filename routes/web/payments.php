<?php

use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\SecurePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback', [PaymentController::class, 'callback'])->name('callback');
    Route::get('/result', [PaymentController::class, 'result'])->name('result');
    Route::get('/failed', [PaymentController::class, 'failed'])->name('failed');

    Route::get('/{booking}', [PaymentController::class, 'show'])->name('show')
        ->middleware('check.booking.ownership')
        ->whereNumber('booking');

    Route::post('/{booking}/process', [PaymentController::class, 'process'])->name('process')
        ->middleware('check.booking.ownership')
        ->whereNumber('booking');

    Route::post('/{booking}/wallet', [PaymentController::class, 'processWithWallet'])->name('wallet')
        ->middleware('check.booking.ownership')
        ->whereNumber('booking');
});

Route::prefix('payments/secure')->middleware('2fa.enabled')->name('payments.secure.')->group(function () {
    Route::get('/checkout/{booking}', [SecurePaymentController::class, 'showCheckout'])->name('checkout');
    Route::get('/verify/{reference}', [SecurePaymentController::class, 'showVerification'])->name('verify');
    Route::get('/result/{reference}', [SecurePaymentController::class, 'showResult'])->name('result');
});
