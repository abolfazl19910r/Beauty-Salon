<?php

use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Admin\AdminWorkScheduleController;
use App\Http\Controllers\Admin\AdminHolidayController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists/{specialist}')->group(function () {
    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [AdminWorkScheduleController::class, 'index'])->name('index');
        Route::post('/', [AdminWorkScheduleController::class, 'store'])->name('store');
        Route::get('/check', [AdminWorkScheduleController::class, 'checkAvailability'])->name('check');
        Route::get('/slots', [AdminWorkScheduleController::class, 'getAvailableSlots'])->name('slots');
        Route::put('/{schedule}', [AdminWorkScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [AdminWorkScheduleController::class, 'destroy'])->name('destroy');
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
