<?php

use App\Http\Controllers\Admin\Holiday\AdminHolidayController;
use Illuminate\Support\Facades\Route;

/*
* Note: The 'work-schedule' route group (specialists.work-schedule.*) that used to live here,
* pointing at AdminSpecialistsWorkScheduleController, has been removed along with the entire
* WorkSchedule feature (see WorkSchedule-Removal). It was always a fully-implemented,
* bug-free, but never-used duplicate of the existing SpecialistSchedule system
* (Specialist::getAvailableSlots() only ever read from SpecialistSchedule, never
* WorkSchedule). Only the 'holidays' group defined below actually stays in use.
 */
Route::prefix('specialists/{specialist}')->group(function () {
    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [AdminHolidayController::class, 'index'])->name('index');
        Route::post('/', [AdminHolidayController::class, 'store'])->name('store');
        Route::delete('/{holiday}', [AdminHolidayController::class, 'destroy'])->name('destroy');
        Route::get('/upcoming', [AdminHolidayController::class, 'upcomingHolidays'])->name('upcoming');
        Route::post('/check', [AdminHolidayController::class, 'checkDate'])->name('check');
    });

});
