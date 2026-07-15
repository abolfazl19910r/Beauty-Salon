<?php

use App\Http\Controllers\User\BlogController;
use App\Http\Controllers\User\GalleryController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->group(function () {
    Route::post('/posts', [BlogController::class, 'store']);
    Route::put('/posts/{post}', [BlogController::class, 'update']);
    Route::delete('/posts/{post}', [BlogController::class, 'destroy']);
});

Route::prefix('gallery')->group(function () {
    Route::post('/', [GalleryController::class, 'store']);
    Route::post('/reorder', [GalleryController::class, 'reorder']);
    Route::put('/{image}', [GalleryController::class, 'update']);
    Route::delete('/{image}', [GalleryController::class, 'destroy']);
});
