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

// OTP entry page for the secure-payment 2FA gate. Deliberately OUTSIDE the 'auth'->'2fa.enabled'
// group below (else a user without a verified session would be redirected here by the middleware,
// only to be redirected right back to itself — an infinite loop).
Route::get('/payments/secure/otp', [SecurePaymentController::class, 'showOtp'])->name('payments.secure.otp');

Route::prefix('payments/secure')->middleware('2fa.enabled')->name('payments.secure.')->group(function () {
    Route::get('/checkout/{booking}', [SecurePaymentController::class, 'showCheckout'])->name('checkout');
    Route::get('/verify/{reference}', [SecurePaymentController::class, 'showVerification'])->name('verify');
    // Real form submission + redirect (not JSON) — belongs here, not in the api/json route file.
    Route::post('/verify/{reference}', [SecurePaymentController::class, 'verify'])->name('verify.submit');
    Route::get('/result/{reference}', [SecurePaymentController::class, 'showResult'])->name('result');
});
