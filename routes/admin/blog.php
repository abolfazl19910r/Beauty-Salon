<?php

use App\Http\Controllers\Admin\Blog\AdminBlogCategoryController;
use App\Http\Controllers\Admin\Blog\AdminBlogController;
use App\Http\Controllers\Admin\Blog\AdminBlogPostActionController;
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
    Route::get('/create', [AdminBlogPostActionController::class, 'create'])->name('create');
    Route::post('/', [AdminBlogPostActionController::class, 'store'])->name('store');
    Route::get('/{post}', [AdminBlogController::class, 'show'])->name('show');
    Route::get('/{post}/edit', [AdminBlogPostActionController::class, 'edit'])->name('edit');
    Route::put('/{post}', [AdminBlogPostActionController::class, 'update'])->name('update');
    Route::delete('/{post}', [AdminBlogPostActionController::class, 'destroy'])->name('destroy');
    Route::patch('/{post}/publish', [AdminBlogPostActionController::class, 'togglePublish'])->name('toggle-publish');
});
