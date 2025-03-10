<?php

use App\Http\Controllers\Admin\AdminAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/', [AdminAnnouncementController::class, 'index'])->name('index');
    // مسیرهای دیگر مدیریت اعلانات در ادمین...
});
