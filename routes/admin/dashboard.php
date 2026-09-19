<?php

use App\Http\Controllers\Admin\Dashboard\AdminDashboardAnalyticsController;
use App\Http\Controllers\Admin\Dashboard\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');

// ⭐ Fix (پیگیری یادداشت نشست قبل، ۲۰۲۶-۰۹-۲۰): این endpoint توسط dashboard.blade.php صدا زده
// نمی‌شه (نمودار از /admin/reports/{period} می‌خونه — به کامنت خودِ AdminDashboardAnalyticsController
// نگاه کن)، ولی همچنان زنده و در دسترس نگه داشته شده (تصمیم قبلی پروژه). مشکل: getSummaryStats()
// شامل totalRevenue (مجموع درآمد پرداخت‌شده) هم می‌شه — یعنی هر ادمینی که فقط permission
// access_admin_panel داره (مثل نقش «منشی»، که طبق تصمیم بیزنسی صریح permission مالی نداره — به
// migration 2026_09_19_000201_add_salon_staff_finance_permissions.php نگاه کن) می‌تونست بدون
// هیچ گیت مالی، درآمد کل سالن رو از این JSON endpoint بخونه. wallet.php/billing.php/reports.php
// همگی زیر permission:manage-wallet هستن؛ این روت باید همون گیت رو داشته باشه.
Route::middleware(['permission:manage-wallet'])->group(function () {
    Route::get('/dashboard/data', [AdminDashboardAnalyticsController::class, 'getData'])->name('dashboard.data');
});
