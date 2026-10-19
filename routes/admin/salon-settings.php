<?php

use App\Http\Controllers\Admin\SalonSettings\AdminSalonSettingsController;
use Illuminate\Support\Facades\Route;

// ⭐ صفحه‌ی «اطلاعات سالن» مالک سالن (۲۰۲۶-۰۹-۲۴) — داخل گروه salon.owner در routes/web.php.
Route::get('/salon-settings', [AdminSalonSettingsController::class, 'edit'])->name('salon-settings.edit');
Route::put('/salon-settings', [AdminSalonSettingsController::class, 'update'])->name('salon-settings.update');

// بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو سالن
Route::post('/salon-settings/pause', [\App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController::class, 'pause'])->name('salon-settings.pause');
Route::delete('/salon-settings/pause', [\App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController::class, 'resume'])->name('salon-settings.resume');
