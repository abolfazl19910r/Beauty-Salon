<?php

use App\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [GalleryController::class, 'index'])->name('index');
    Route::post('/', [GalleryController::class, 'store'])->name('store');
    Route::post('/reorder', [GalleryController::class, 'reorder'])->name('reorder');
    Route::put('/{image}', [GalleryController::class, 'update'])->name('update');
    Route::delete('/{image}', [GalleryController::class, 'destroy'])->name('destroy');
});
