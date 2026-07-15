<?php

use App\Http\Controllers\Admin\Payment\AdminPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/create', [AdminPaymentController::class, 'create'])->name('create');
    Route::post('/', [AdminPaymentController::class, 'store'])->name('store');
});
