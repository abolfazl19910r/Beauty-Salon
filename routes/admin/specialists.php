<?php

use App\Http\Controllers\Admin\AdminSpecialistController;
use App\Http\Controllers\Admin\AdminSpecialistScheduleController;
use App\Http\Controllers\Admin\AdminSpecialistLeaveController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->name('specialists.')->group(function () {
    Route::get('/', [AdminSpecialistController::class, 'index'])->name('index');
    Route::get('/create', [AdminSpecialistController::class, 'create'])->name('create');
    Route::post('/', [AdminSpecialistController::class, 'store'])->name('store');

    Route::get('/{specialist}', [AdminSpecialistController::class, 'show'])->name('show');
    Route::get('/{specialist}/edit', [AdminSpecialistController::class, 'edit'])->name('edit');
    Route::put('/{specialist}', [AdminSpecialistController::class, 'update'])->name('update');
    Route::delete('/{specialist}', [AdminSpecialistController::class, 'destroy'])->name('destroy');

    Route::get('/{specialist}/schedules/edit', [AdminSpecialistScheduleController::class, 'edit'])
        ->name('schedules.edit');
    Route::put('/{specialist}/schedules', [AdminSpecialistScheduleController::class, 'update'])
        ->name('schedules.update');

    Route::get('/{specialist}/leaves', [AdminSpecialistLeaveController::class, 'index'])
        ->name('leaves.index');
    Route::post('/{specialist}/leaves', [AdminSpecialistLeaveController::class, 'store'])
        ->name('leaves.store');
    Route::put('/{specialist}/leaves/{leave}', [AdminSpecialistLeaveController::class, 'update'])
        ->name('leaves.update');
});
