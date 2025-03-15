<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [BookingController::class, 'getUserBookings']);
    Route::get('/upcoming', [BookingController::class, 'getUpcomingBookings']);
    Route::get('/past', [BookingController::class, 'getPastBookings']);
    Route::get('/latest', [BookingController::class, 'latestSuccessful']);

    Route::post('/', [BookingController::class, 'store']);

    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::post('/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/{booking}/rate', [BookingController::class, 'rate']);
    Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount']);

    Route::get('/services', [BookingController::class, 'getServices']);
    Route::get('/specialists/{service_id}', [BookingController::class, 'getSpecialistsByService']);
    Route::get('/available-dates/{specialist}', [BookingController::class, 'getAvailableDates']);
    Route::get('/time-slots/{specialist}/{date}', [BookingController::class, 'getAvailableTimeSlots']);
    Route::post('/check-discount', [BookingController::class, 'checkDiscount']);
});
