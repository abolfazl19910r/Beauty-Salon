<?php

use App\Http\Controllers\Admin\Announcement\AdminAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AdminAnnouncementController::class, 'index'])->name('index');
    Route::get('/create', [AdminAnnouncementController::class, 'create'])->name('create');
    Route::post('/', [AdminAnnouncementController::class, 'store'])->name('store');
    Route::get('/{announcement}/edit', [AdminAnnouncementController::class, 'edit'])->name('edit');
    Route::put('/{announcement}', [AdminAnnouncementController::class, 'update'])->name('update');
    Route::delete('/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('destroy');
});
