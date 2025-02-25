<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->group(function () {
    Route::get('/user', [BookingController::class, 'getUserBookings']);
    Route::get('/upcoming', [BookingController::class, 'getUpcomingBookings']);
    Route::get('/past', [BookingController::class, 'getPastBookings']);

    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::post('/', [BookingController::class, 'store']);
    Route::post('/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::post('/{booking}/rate', [BookingController::class, 'rate']);
    Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount']);
});
