<?php

use App\Http\Controllers\Admin\Announcement\AdminAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AdminAnnouncementController::class, 'index'])->name('index');
    Route::get('stats', [AdminAnnouncementController::class, 'stats'])->name('stats');
    Route::get('list', [AdminAnnouncementController::class, 'list'])->name('list');
    Route::post('store', [AdminAnnouncementController::class, 'store'])->name('store');
    Route::put('{announcement}', [AdminAnnouncementController::class, 'update'])->name('update');
    Route::delete('{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('destroy');
});
