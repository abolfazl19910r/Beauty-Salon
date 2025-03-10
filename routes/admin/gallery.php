<?php

use App\Http\Controllers\Admin\AdminGalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [AdminGalleryController::class, 'index'])->name('index');
});
