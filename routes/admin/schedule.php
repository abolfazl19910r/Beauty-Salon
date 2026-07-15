<?php

use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Admin\AdminSpecialistsWorkScheduleController;
use App\Http\Controllers\Admin\AdminHolidayController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists/{specialist}')->group(function () {
    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [AdminSpecialistsWorkScheduleController::class, 'index'])->name('index');
        Route::post('/', [AdminSpecialistsWorkScheduleController::class, 'store'])->name('store');
        Route::get('/check', [AdminSpecialistsWorkScheduleController::class, 'checkAvailability'])->name('check');
        Route::get('/slots', [AdminSpecialistsWorkScheduleController::class, 'getAvailableSlots'])->name('slots');
        Route::put('/{schedule}', [AdminSpecialistsWorkScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [AdminSpecialistsWorkScheduleController::class, 'destroy'])->name('destroy');
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
