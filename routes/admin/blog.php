<?php

use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminBlogCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog/categories')->name('blog.categories.')->group(function () {
    Route::get('/', [AdminBlogCategoryController::class, 'index'])->name('index');
    Route::get('/create', [AdminBlogCategoryController::class, 'create'])->name('create');
    Route::post('/', [AdminBlogCategoryController::class, 'store'])->name('store');
    Route::get('/{category}/edit', [AdminBlogCategoryController::class, 'edit'])->name('edit');
    Route::put('/{category}', [AdminBlogCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [AdminBlogCategoryController::class, 'destroy'])->name('destroy');
});

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [AdminBlogController::class, 'index'])->name('index');
    Route::get('/create', [AdminBlogController::class, 'create'])->name('create');
    Route::post('/', [AdminBlogController::class, 'store'])->name('store');
    Route::get('/{post}', [AdminBlogController::class, 'show'])->name('show');
    Route::get('/{post}/edit', [AdminBlogController::class, 'edit'])->name('edit');
    Route::put('/{post}', [AdminBlogController::class, 'update'])->name('update');
    Route::delete('/{post}', [AdminBlogController::class, 'destroy'])->name('destroy');
    Route::patch('/{post}/publish', [AdminBlogController::class, 'togglePublish'])->name('toggle-publish');
});
