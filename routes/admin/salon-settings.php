<?php

use App\Http\Controllers\Admin\SalonSettings\AdminSalonSettingsController;
use Illuminate\Support\Facades\Route;

// ⭐ صفحه‌ی «اطلاعات سالن» مالک سالن (۲۰۲۶-۰۹-۲۴) — داخل گروه salon.owner در routes/web.php.
Route::get('/salon-settings', [AdminSalonSettingsController::class, 'edit'])->name('salon-settings.edit');
Route::put('/salon-settings', [AdminSalonSettingsController::class, 'update'])->name('salon-settings.update');

// بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو سالن
Route::post('/salon-settings/pause', [\App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController::class, 'pause'])->name('salon-settings.pause');
Route::delete('/salon-settings/pause', [\App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController::class, 'resume'])->name('salon-settings.resume');

// بسته‌ی 5ه (۲۰۲۶-۱۰-۲۸): «نحوه‌ی رزرو» — با/بدون پیش‌پرداخت، سقف نوبت مشتری، مشتری بدقول
Route::get('/booking-policy', [\App\Http\Controllers\Admin\BookingPolicy\AdminBookingPolicyController::class, 'edit'])->name('booking-policy.edit');
Route::put('/booking-policy/mode', [\App\Http\Controllers\Admin\BookingPolicy\AdminBookingPolicyController::class, 'updateMode'])->name('booking-policy.mode');
Route::put('/booking-policy/loyalty', [\App\Http\Controllers\Admin\BookingPolicy\AdminBookingPolicyController::class, 'updateLoyalty'])->name('booking-policy.loyalty');
Route::put('/booking-policy/limits', [\App\Http\Controllers\Admin\BookingPolicy\AdminBookingPolicyController::class, 'updateLimits'])->name('booking-policy.limits');
