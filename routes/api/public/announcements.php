<?php

use App\Http\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index'])->name('index');
    Route::get('active', [AnnouncementController::class, 'active'])->name('active');
    Route::get('top', [AnnouncementController::class, 'top'])->name('top');
    Route::get('{id}', [AnnouncementController::class, 'show'])->name('show');
});
