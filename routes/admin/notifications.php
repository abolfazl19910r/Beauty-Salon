<?php

use App\Http\Controllers\Admin\Notification\AdminNotificationController;
use Illuminate\Support\Facades\Route;

// no-store: با «برگشت» مرورگر نسخه‌ی کش‌شده‌ی فهرست (اعلان خوانده‌شده هنوز پررنگ) نشان داده نشود
Route::prefix('notifications')->name('notifications.')->middleware('cache.headers:no_store;private')->group(function () {
    Route::get('count', [AdminNotificationController::class, 'unreadCount'])->name('count');
    Route::get('latest', [AdminNotificationController::class, 'latest'])->name('latest');

    Route::post('{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('read');
    Route::post('{id}/toggle', [AdminNotificationController::class, 'toggleRead'])->name('toggle');
    Route::delete('{id}/delete', [AdminNotificationController::class, 'delete'])->name('delete');

    Route::post('read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('delete-all', [AdminNotificationController::class, 'deleteAll'])->name('delete-all');

    Route::get('{id}', [AdminNotificationController::class, 'show'])->name('show');

    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
});
