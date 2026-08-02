<?php

use App\Http\Controllers\Admin\DiscountCode\AdminDiscountCodeController;
use Illuminate\Support\Facades\Route;

Route::prefix('discount-codes')->name('discount-codes.')->group(function () {
    Route::get('/', [AdminDiscountCodeController::class, 'index'])->name('index');
    Route::get('/create', [AdminDiscountCodeController::class, 'create'])->name('create');
    Route::post('/', [AdminDiscountCodeController::class, 'store'])->name('store');
    Route::get('/preview', [AdminDiscountCodeController::class, 'preview'])->name('preview');
    Route::get('/{discountCode}/edit', [AdminDiscountCodeController::class, 'edit'])->name('edit');
    Route::put('/{discountCode}', [AdminDiscountCodeController::class, 'update'])->name('update');
    Route::delete('/{discountCode}', [AdminDiscountCodeController::class, 'destroy'])->name('destroy');
});
