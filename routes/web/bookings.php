<?php

use App\Http\Controllers\User\BookingAvailabilityController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\BookingDiscountController;
use App\Http\Controllers\User\BookingRescheduleController;
use App\Http\Controllers\User\BookingReservationController;
use App\Http\Controllers\User\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {

    Route::get('/success', [BookingController::class, 'success'])->name('success');
    Route::get('/failed', [BookingController::class, 'failed'])->name('failed');
    Route::get('/', [BookingController::class, 'index'])->name('index');

    Route::get('/create', [BookingReservationController::class, 'create'])->name('create');
    Route::post('/confirm', [BookingReservationController::class, 'confirm'])->name('confirm');
    Route::post('/', [BookingReservationController::class, 'store'])->name('store');

    // ⭐ Fix (real, confirmed cross-tenant data leak, session 2026-09-20): moved here from the
    // unscoped routes/api/public/services.php (ServiceController::list(), same JSON payload —
    // controller/method untouched). That route sat entirely outside /s/{salon_slug}, so
    // CurrentSalon was never bound and BeautyService::all() returned every salon's services
    // mixed together (confirmed: created one service per salon, hit /api/services with no salon
    // context, got both back). Placed alongside the sibling bookings.available-dates/
    // available-slots/service-specialists routes below — same controller-reuse pattern, same
    // booking-wizard consumer (bookings/create.blade.php).
    Route::get('/services-list', [ServiceController::class, 'list'])->name('services-list');

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
