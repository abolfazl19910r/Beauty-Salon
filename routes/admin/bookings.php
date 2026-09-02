<?php

use App\Http\Controllers\Admin\Booking\AdminBookingController;
use App\Http\Controllers\Admin\Booking\AdminBookingCustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [AdminBookingController::class, 'index'])->name('index');
    Route::get('/create', [AdminBookingController::class, 'create'])->name('create');
    Route::post('/', [AdminBookingController::class, 'store'])->name('store');
    // ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): customer search/quick-create widget
    // used by admin/bookings/create.blade.php — see AdminBookingCustomerController.
    Route::get('/customers/search', [AdminBookingCustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers/quick-create', [AdminBookingCustomerController::class, 'quickCreate'])->name('customers.quick-create');
    Route::get('/{booking}/edit', [AdminBookingController::class, 'edit'])->name('edit');
    Route::get('/{booking}', [AdminBookingController::class, 'show'])->name('show');
    Route::put('/{booking}', [AdminBookingController::class, 'update'])->name('update');
    Route::delete('/{booking}', [AdminBookingController::class, 'destroy'])->name('destroy');
});
