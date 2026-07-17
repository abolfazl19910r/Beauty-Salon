<?php

use App\Http\Controllers\Admin\Holiday\AdminHolidayController;
use App\Http\Controllers\Admin\Leave\AdminLeaveController;
use App\Http\Controllers\Admin\Specialist\AdminSpecialistsWorkScheduleController;
use Illuminate\Support\Facades\Route;

/*
* ⚠️ Note: The name of this group has been changed from 'schedule.' to 'specialists.work-schedule.'
* to avoid conflict with the 'schedule.' group defined in routes/admin/specialists.php for the old system
* (SpecialistSchedule). Previously, both names
* were 'admin.schedule.index' and since this file was loaded after specialists.php
* , it silently overrode the old registry.
 */
Route::prefix('specialists/{specialist}')->group(function () {
    Route::prefix('work-schedule')->name('specialists.work-schedule.')->group(function () {
        Route::get('/', [AdminSpecialistsWorkScheduleController::class, 'index'])->name('index');
        Route::post('/', [AdminSpecialistsWorkScheduleController::class, 'store'])->name('store');
        Route::put('/', [AdminSpecialistsWorkScheduleController::class, 'update'])->name('update');
        Route::delete('/', [AdminSpecialistsWorkScheduleController::class, 'destroy'])->name('destroy');
        Route::get('/check', [AdminSpecialistsWorkScheduleController::class, 'checkAvailability'])->name('check');
        Route::get('/slots', [AdminSpecialistsWorkScheduleController::class, 'getAvailableSlots'])->name('slots');
    });

    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [AdminHolidayController::class, 'index'])->name('index');
        Route::post('/', [AdminHolidayController::class, 'store'])->name('store');
        Route::delete('/{holiday}', [AdminHolidayController::class, 'destroy'])->name('destroy');
        Route::get('/upcoming', [AdminHolidayController::class, 'upcomingHolidays'])->name('upcoming');
        Route::post('/check', [AdminHolidayController::class, 'checkDate'])->name('check');
    });

});

Route::get('/leaves/pending', [AdminLeaveController::class, 'pendingLeaves'])->name('leaves.pending');
