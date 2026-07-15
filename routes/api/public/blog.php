<?php

use App\Http\Controllers\User\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/posts', [BlogController::class, 'index'])->name('posts.index');
        Route::get('/categories', [BlogController::class, 'getCategories'])->name('categories.index');
    });
