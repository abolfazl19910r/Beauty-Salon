<?php

use App\Http\Controllers\Admin\AdminReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('reviews')->name('reviews.')->group(function () {
    Route::get('/', [AdminReviewController::class, 'index'])->name('index');
    Route::get('/stats', [AdminReviewController::class, 'stats'])->name('stats');
    Route::get('/trashed', [AdminReviewController::class, 'trashed'])->name('trashed');
    Route::get('/{review}', [AdminReviewController::class, 'show'])->name('show');
    Route::post('/{review}/approve', [AdminReviewController::class, 'approve'])->name('approve');
    Route::post('/{review}/reject', [AdminReviewController::class, 'reject'])->name('reject');
    Route::post('/{review}/toggle-featured', [AdminReviewController::class, 'toggleFeatured'])->name('toggle-featured');
    Route::delete('/{review}', [AdminReviewController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [AdminReviewController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force-delete', [AdminReviewController::class, 'forceDelete'])->name('force-delete');
});
