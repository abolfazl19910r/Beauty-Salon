<?php

use App\Http\Controllers\Admin\AdminLoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/', [AdminLoyaltyController::class, 'index'])->name('index');
    Route::get('/create', [AdminLoyaltyController::class, 'create'])->name('create');
    Route::post('/', [AdminLoyaltyController::class, 'store'])->name('store');
    Route::get('/{loyalty}', [AdminLoyaltyController::class, 'show'])->name('show');
    Route::get('/{loyalty}/edit', [AdminLoyaltyController::class, 'edit'])->name('edit');
    Route::put('/{loyalty}', [AdminLoyaltyController::class, 'update'])->name('update');
    Route::delete('/{loyalty}', [AdminLoyaltyController::class, 'destroy'])->name('destroy');
});
