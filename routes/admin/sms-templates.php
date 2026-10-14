<?php

use App\Http\Controllers\Admin\Notification\AdminSmsTemplateController;
use Illuminate\Support\Facades\Route;

// متن پیامک‌های سالن (تصمیم ۲۰۲۶-۱۰-۱۴) — کنار تنظیمات اطلاع‌رسانی
Route::prefix('sms-templates')->name('sms-templates.')->middleware('feature:sms_templates')->group(function () {
    Route::get('/', [AdminSmsTemplateController::class, 'index'])->name('index');
    Route::put('{key}', [AdminSmsTemplateController::class, 'update'])->where('key', '[a-z_]+')->name('update');
    Route::delete('{key}', [AdminSmsTemplateController::class, 'reset'])->where('key', '[a-z_]+')->name('reset');
});
