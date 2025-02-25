<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/create', [BookingController::class, 'create'])->name('create');
    Route::post('/', [BookingController::class, 'store'])->name('store');

    Route::middleware('check.booking.ownership')->group(function () {
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::get('/{booking}/reschedule', [BookingController::class, 'showReschedule'])->name('reschedule');
        Route::post('/{booking}/reschedule', [BookingController::class, 'reschedule']);
        Route::put('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');

        Route::post('/{booking}/rate', [BookingController::class, 'rate'])->name('rate');
        Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount'])->name('apply-discount');
    });
});
