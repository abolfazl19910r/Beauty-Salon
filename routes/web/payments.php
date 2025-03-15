<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SecurePaymentController;
use App\Http\Controllers\LoyaltyController;
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
});

Route::prefix('payments/secure')->middleware('2fa.enabled')->name('payments.secure.')->group(function () {
    Route::get('/checkout/{booking}', [SecurePaymentController::class, 'showCheckout'])->name('checkout');
    Route::get('/verify/{reference}', [SecurePaymentController::class, 'showVerification'])->name('verify');
    Route::get('/result/{reference}', [SecurePaymentController::class, 'showResult'])->name('result');
});

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/', [LoyaltyController::class, 'index'])->name('index');
    Route::get('/points', [LoyaltyController::class, 'getPoints'])->name('points');
    Route::get('/history', [LoyaltyController::class, 'getHistory'])->name('history');
    Route::get('/rewards', [LoyaltyController::class, 'getRewards'])->name('rewards');
    Route::get('/progress', [LoyaltyController::class, 'getProgress'])->name('progress');

    Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward'])->name('redeem');
});
