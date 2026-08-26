<?php

use App\Http\Controllers\Admin\Notification\AdminNotificationSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('notification-settings')->name('notification-settings.')->group(function () {
    Route::get('/', [AdminNotificationSettingController::class, 'index'])->name('index');
    Route::post('/', [AdminNotificationSettingController::class, 'update'])->name('update');
});
