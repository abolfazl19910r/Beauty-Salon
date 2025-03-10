<?php

use App\Http\Controllers\Admin\AdminBookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [AdminBookingController::class, 'index'])->name('index');
    Route::get('/create', [AdminBookingController::class, 'create'])->name('create');
    Route::post('/', [AdminBookingController::class, 'store'])->name('store');
    Route::get('/{booking}/edit', [AdminBookingController::class, 'edit'])->name('edit');
    Route::get('/{booking}', [AdminBookingController::class, 'show'])->name('show');
    Route::put('/{booking}', [AdminBookingController::class, 'update'])->name('update');
    Route::delete('/{booking}', [AdminBookingController::class, 'destroy'])->name('destroy');
});
