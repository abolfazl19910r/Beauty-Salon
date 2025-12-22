<?php

use App\Http\Controllers\Specialist\SpecialistProfileController;
use App\Http\Controllers\SpecialistController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->name('specialists.')->group(function () {
    Route::get('/search', [SpecialistController::class, 'search'])->name('search');
    Route::get('/service/{service}', [SpecialistController::class, 'byService'])->name('by-service');
    Route::get('/top-rated', [SpecialistController::class, 'topRated'])->name('top-rated');
    Route::get('/{specialist}', [SpecialistController::class, 'show'])->name('show');
    Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])->name('availability');
    Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])->name('available-slots');
});

Route::middleware(['auth', 'verified'])->name('specialist.')->group(function () {

    Route::get('/my-dashboard', [SpecialistProfileController::class, 'dashboardBookings'])->name('my-dashboard');

    Route::get('/specialist/profile', [SpecialistProfileController::class, 'show'])->name('profile.show');
    Route::get('/specialist/profile/edit', [SpecialistProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/specialist/profile', [SpecialistProfileController::class, 'update'])->name('profile.update');
    Route::put('/specialist/profile/password', [SpecialistProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/specialist/schedule', [SpecialistProfileController::class, 'schedule'])->name('schedule');
    Route::put('/specialist/schedule', [SpecialistProfileController::class, 'updateSchedule'])->name('schedule.update');
    Route::get('/specialist/leaves', [SpecialistProfileController::class, 'leaves'])->name('leaves');
    Route::post('/specialist/leaves', [SpecialistProfileController::class, 'storeLeave'])->name('leaves.store');
    Route::delete('/specialist/leaves/{leave}', [SpecialistProfileController::class, 'destroyLeave'])->name('leaves.destroy');

    Route::get('/specialist/leaves/create', [SpecialistProfileController::class, 'createLeave'])->name('leaves.create');

    Route::get('/specialist/bookings', [SpecialistProfileController::class, 'bookings'])->name('bookings');

    Route::put('/specialist/bookings/{booking}/complete', [SpecialistProfileController::class, 'completeBooking'])->name('bookings.complete');
    Route::put('/specialist/bookings/{booking}/cancel', [SpecialistProfileController::class, 'cancelBooking'])->name('bookings.cancel');
    Route::get('/specialist/bookings/{booking}', [SpecialistProfileController::class, 'showBooking'])->name('bookings.show');
});
