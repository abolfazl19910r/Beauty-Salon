<?php

use App\Http\Controllers\Admin\AdminNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('count', [AdminNotificationController::class, 'unreadCount'])->name('count');
    Route::get('latest', [AdminNotificationController::class, 'latest'])->name('latest');
    Route::post('{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('read');

    Route::post('read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('delete-all', [AdminNotificationController::class, 'deleteAll'])->name('delete-all');

    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
});
