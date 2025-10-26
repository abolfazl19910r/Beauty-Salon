<?php

use App\Http\Controllers\Admin\AdminGalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [AdminGalleryController::class, 'index'])->name('index');
    Route::get('/images', [AdminGalleryController::class, 'getImages'])->name('images');
    Route::get('/stats', [AdminGalleryController::class, 'stats'])->name('stats'); // اضافه شد
    Route::post('/upload', [AdminGalleryController::class, 'store'])->name('store');
    Route::delete('/{id}', [AdminGalleryController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [AdminGalleryController::class, 'reorder'])->name('reorder');
});
