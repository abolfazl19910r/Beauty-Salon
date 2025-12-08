<?php

use App\Http\Controllers\Specialist\SpecialistProfileController;
use App\Http\Controllers\SpecialistController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->name('specialists.')->group(function () {

    Route::get('/search', [SpecialistController::class, 'search'])
        ->name('search');
    Route::get('/service/{service}', [SpecialistController::class, 'byService'])
        ->name('by-service');
    Route::get('/top-rated', [SpecialistController::class, 'topRated'])
        ->name('top-rated');
    Route::get('/{specialist}', [SpecialistController::class, 'show'])
        ->name('show');
    Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])
        ->name('availability');
    Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])
        ->name('available-slots');
});

Route::middleware(['auth', 'role:specialists'])->name('specialist.')->group(function () {

    Route::get('/dashboard', [SpecialistProfileController::class, 'dashboardBookings'])
        ->name('dashboard');
    Route::get('/my-profile', [SpecialistProfileController::class, 'show'])
        ->name('profile.show');
    Route::get('/my-profile/edit', [SpecialistProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::put('/my-profile', [SpecialistProfileController::class, 'update'])
        ->name('profile.update');
    Route::put('/my-profile/password', [SpecialistProfileController::class, 'updatePassword'])
        ->name('profile.password');
    Route::get('/schedule', [SpecialistProfileController::class, 'schedule'])
        ->name('schedule');
    Route::put('/schedule', [SpecialistProfileController::class, 'updateSchedule'])
        ->name('schedule.update');
    Route::get('/leaves', [SpecialistProfileController::class, 'leaves'])
        ->name('leaves');
    Route::post('/leaves', [SpecialistProfileController::class, 'storeLeave'])
        ->name('leaves.store');
    Route::delete('/leaves/{leave}', [SpecialistProfileController::class, 'destroyLeave'])
        ->name('leaves.destroy');
    Route::put('/my-bookings/{booking}/complete', [SpecialistProfileController::class, 'completeBooking'])
        ->name('bookings.complete');
    Route::put('/my-bookings/{booking}/cancel', [SpecialistProfileController::class, 'cancelBooking'])
        ->name('bookings.cancel');
});
