<?php

use App\Http\Controllers\User\SpecialistController;
use Illuminate\Support\Facades\Route;

// ⭐ (۲۰۲۶-۰۹-۲۷) ۱۴ مسیر مرده حذف شد (جست‌وجو، فیلتر، دسته، پرطرفدار، جدید، تخفیف‌دار، مقایسه، علاقه‌مندی‌ها،
// سابقه، مشابه، ثبت/فهرست نظر خدمت): به متدهایی اشاره می‌کردند که در ServiceController وجود نداشتند (۵۰۰ یا ۴۰۴) و
// هیچ صفحه‌ای به آن‌ها لینک نمی‌داد. معادل‌های کارکننده: فهرست خدمات با ?category، «خدمات مرتبط» صفحه‌ی خدمت،
// «نوبت‌های من»، و جریان توکن نظر / امتیازدهی به نوبت.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('specialists')->name('specialists.')->group(function () {
        Route::get('/by-service/{service}', [SpecialistController::class, 'byService'])->name('by-service');

        Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])->name('availability');
        Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])->name('available-slots');
    });
});
