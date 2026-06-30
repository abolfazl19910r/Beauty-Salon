<?php

use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingRescheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [BookingController::class, 'getUserBookings']);

    Route::get('/upcoming', [BookingController::class, 'getUpcomingBookings']);
    Route::get('/past', [BookingController::class, 'getPastBookings']);
    Route::get('/latest', [BookingController::class, 'latestSuccessful']);

    Route::post('/', [BookingController::class, 'store']);

    Route::get('/{booking}', [BookingController::class, 'show']);

    Route::post('/{booking}/reschedule', [BookingRescheduleController::class, 'update']);
    Route::post('/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/{booking}/rate', [BookingController::class, 'rate']);
    Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount']);

    Route::get('/specialists/{specialist}/available-dates', [BookingAvailabilityController::class, 'getAvailableDates']);
    Route::get('/specialists/{specialist}/time-slots/{date}', [BookingAvailabilityController::class, 'getAvailableTimeSlots']);
    Route::get('/services/{service}/specialists', [BookingAvailabilityController::class, 'getSpecialistsByService']);
    Route::post('/check-discount', [BookingController::class, 'checkDiscount']);
});
