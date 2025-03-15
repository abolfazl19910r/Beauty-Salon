<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/success', [BookingController::class, 'success'])->name('success');
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/create', [BookingController::class, 'create'])->name('create');
    Route::post('/confirm', [BookingController::class, 'confirm'])->name('confirm');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/failed', [BookingController::class, 'failed'])->name('failed');

    Route::middleware('check.booking.ownership')->group(function () {
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::get('/{booking}/reschedule', [BookingController::class, 'showReschedule'])->name('reschedule');
        Route::put('/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('update-reschedule');
        Route::put('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/rate', [BookingController::class, 'rate'])->name('rate');
        Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount'])->name('apply-discount');
    });
});

Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{booking}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{booking}/process', [PaymentController::class, 'process'])->name('process');
    Route::get('/{booking}/callback', [PaymentController::class, 'callback'])->name('callback');
});
