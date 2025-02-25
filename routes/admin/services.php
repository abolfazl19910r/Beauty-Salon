<?php

use App\Http\Controllers\Admin\AdminServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('services')->name('services.')->group(function () {
    Route::get('/', [AdminServiceController::class, 'index'])->name('index');
    Route::get('/create', [AdminServiceController::class, 'create'])->name('create');
    Route::post('/', [AdminServiceController::class, 'store'])->name('store');

    Route::get('/{service}/edit', [AdminServiceController::class, 'edit'])->name('edit');
    Route::put('/{service}', [AdminServiceController::class, 'update'])->name('update');
    Route::delete('/{service}', [AdminServiceController::class, 'destroy'])->name('destroy');
});
