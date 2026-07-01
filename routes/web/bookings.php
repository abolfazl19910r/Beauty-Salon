<?php

use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingDiscountController;
use App\Http\Controllers\BookingReservationController;
use App\Http\Controllers\BookingRescheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {

    Route::get('/success', [BookingController::class, 'success'])->name('success');
    Route::get('/failed', [BookingController::class, 'failed'])->name('failed');
    Route::get('/', [BookingController::class, 'index'])->name('index');

    Route::get('/create', [BookingReservationController::class, 'create'])->name('create');
    Route::post('/confirm', [BookingReservationController::class, 'confirm'])->name('confirm');
    Route::post('/', [BookingReservationController::class, 'store'])->name('store');

    Route::get('/specialists/{specialist}/dates', [BookingAvailabilityController::class, 'getAvailableDates'])->name('available-dates');
    Route::get('/specialists/{specialist}/slots/{date}', [BookingAvailabilityController::class, 'getAvailableTimeSlots'])->name('available-slots');
    Route::get('/services/{service}/specialists', [BookingAvailabilityController::class, 'getSpecialistsByService'])->name('service-specialists');

    Route::post('/check-discount', [BookingDiscountController::class, 'check'])->name('check-discount');

    Route::middleware('check.booking.ownership')->group(function () {
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::put('/{booking}/cancel', [BookingReservationController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/rate', [BookingController::class, 'rate'])->name('rate');
        Route::post('/{booking}/apply-discount', [BookingDiscountController::class, 'apply'])->name('apply-discount');

        Route::get('/{booking}/reschedule', [BookingRescheduleController::class, 'show'])->name('reschedule');
        Route::put('/{booking}/reschedule', [BookingRescheduleController::class, 'update'])->name('update-reschedule');
    });
});
