<?php

use App\Http\Controllers\Admin\AdminGalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [App\Http\Controllers\GalleryController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\GalleryController::class, 'store'])->name('store');
    Route::post('/reorder', [App\Http\Controllers\GalleryController::class, 'reorder'])->name('reorder');
    Route::put('/{image}', [App\Http\Controllers\GalleryController::class, 'update'])->name('update');
    Route::delete('/{image}', [App\Http\Controllers\GalleryController::class, 'destroy'])->name('destroy');
});
