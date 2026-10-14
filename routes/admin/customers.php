<?php

use App\Http\Controllers\Admin\Customer\AdminCustomerController;
use Illuminate\Support\Facades\Route;

// پرونده‌ی مشتری (۲۰۲۶-۱۰-۱۴، همراه بسته‌ی ب) — فقط خواندنی
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
    Route::get('/{customer}', [AdminCustomerController::class, 'show'])->whereNumber('customer')->name('show');
});
