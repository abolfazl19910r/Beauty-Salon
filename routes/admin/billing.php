<?php

use App\Http\Controllers\Admin\Billing\AdminBillingController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». عمداً بدون prefix/name اضافه — گروه‌بندی‌
 * کننده‌ی این فایل در routes/web.php از قبل prefix('admin')->name('admin.') را اعمال کرده،
 * دقیقاً هم‌الگو با بقیه‌ی routes/admin/*.php.
 *
 * ⚠️ این روت‌ها تنها استثنای EnsureAdminSalonActive برای سالن *منقضی‌شده* هستند (به بخش «تصمیم
 * تأییدشده» در docblock خودِ آن middleware نگاه کن) — یعنی حتی وقتی بقیه‌ی /admin/* برای این
 * ادمین مسدود است، همچنان به‌درستی در دسترس می‌مانند تا امکان تمدید آنلاین وجود داشته باشد.
 */
Route::prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [AdminBillingController::class, 'index'])->name('index');
    Route::post('/purchase', [AdminBillingController::class, 'purchase'])->name('purchase');
    Route::get('/callback', [AdminBillingController::class, 'callback'])->name('callback');
});
