<?php

use App\Http\Controllers\Admin\Gallery\AdminGalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [AdminGalleryController::class, 'index'])->name('index');
    Route::post('/', [AdminGalleryController::class, 'store'])->name('store');
    Route::delete('/{image}', [AdminGalleryController::class, 'destroy'])->name('destroy');
    Route::put('/{image}/move-up', [AdminGalleryController::class, 'moveUp'])->name('move-up');
    Route::put('/{image}/move-down', [AdminGalleryController::class, 'moveDown'])->name('move-down');
});
