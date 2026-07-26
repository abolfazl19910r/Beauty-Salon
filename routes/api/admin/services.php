<?php

use App\Http\Controllers\User\GalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('gallery')->group(function () {
    Route::post('/', [GalleryController::class, 'store']);
    Route::post('/reorder', [GalleryController::class, 'reorder']);
    Route::put('/{image}', [GalleryController::class, 'update']);
    Route::delete('/{image}', [GalleryController::class, 'destroy']);
});
