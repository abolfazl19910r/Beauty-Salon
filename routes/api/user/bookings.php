<?php

use App\Http\Controllers\User\BookingAvailabilityController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\BookingDiscountController;
use App\Http\Controllers\User\BookingRescheduleController;
use App\Http\Controllers\User\BookingReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [BookingController::class, 'getUserBookings']);

    Route::get('/upcoming', [BookingController::class, 'getUpcomingBookings']);
    Route::get('/past', [BookingController::class, 'getPastBookings']);
    Route::get('/latest', [BookingController::class, 'latestSuccessful']);

    Route::post('/', [BookingReservationController::class, 'store']);

    Route::get('/{booking}', [BookingController::class, 'show']);

    Route::post('/{booking}/reschedule', [BookingRescheduleController::class, 'update']);
    Route::post('/{booking}/cancel', [BookingReservationController::class, 'cancel']);
    Route::post('/{booking}/rate', [BookingController::class, 'rate']);
    Route::post('/{booking}/apply-discount', [BookingDiscountController::class, 'applyApi']);

    Route::get('/specialists/{specialist}/available-dates', [BookingAvailabilityController::class, 'getAvailableDates']);
    Route::get('/specialists/{specialist}/time-slots/{date}', [BookingAvailabilityController::class, 'getAvailableTimeSlots']);
    Route::get('/services/{service}/specialists', [BookingAvailabilityController::class, 'getSpecialistsByService']);
    Route::post('/check-discount', [BookingDiscountController::class, 'check']);
});
