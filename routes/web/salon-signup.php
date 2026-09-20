<?php

use App\Http\Controllers\SalonSignup\SalonSignupController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)» — نقطه‌ی ورود عمومی برای ساخت یک
 * سالن جدید بدون دخالت سوپرادمین.
 *
 * عمداً یک روت global (نه زیر s/{salon_slug}، چون وقتی این فرم رو می‌بینی هنوز هیچ سالنی وجود
 * نداره؛ نه پشت central_domain، چون central_domain یک زیرساخت کاملاً اختیاریه — این فیچر باید
 * حتی بدون تنظیم CENTRAL_DOMAIN هم در دسترس بمونه) — هم‌تراز با web/auth.php، require شده مستقیم
 * در routes/web.php، خارج از $tenantRoutes.
 *
 * throttle:registration دقیقاً هم‌الگو با routes/salon-auth.php (ثبت‌نام مشتری) روی هر سه POST
 * (نه فقط store) — همون محدودیت SMS-abuse که در RouteServiceProvider::configureRateLimiting()
 * مستند شده، اینجا هم صادقه چون این مسیر هم واقعاً یک پیامک Kavenegar می‌فرسته.
 */
Route::prefix('salon-signup')->name('salon-signup.')->group(function () {
    Route::get('/', [SalonSignupController::class, 'create'])->name('create');
    Route::post('/', [SalonSignupController::class, 'store'])
        ->middleware('throttle:registration')
        ->name('store');

    Route::get('/verify', [SalonSignupController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [SalonSignupController::class, 'verify'])
        ->middleware('throttle:registration')
        ->name('verify.store');
    Route::post('/resend-code', [SalonSignupController::class, 'resendCode'])
        ->middleware('throttle:registration')
        ->name('resend-code');

    // ⭐ مورد ۴ (نشست ۲۰۲۶-۰۹-۲۰): چک یکتایی زنده‌ی slug/phone — هم این فرم هم فرم سوپرادمین
    // (superadmin.salons.create) از همین دو endpoint استفاده می‌کنن (به docblock
    // SalonSignupController::checkSlug/checkPhone نگاه کن). throttle:60,1 عمومی Laravel
    // (نه یکی از rate limiter های نام‌دار SMS) چون این‌ها هیچ پیامکی نمی‌فرستن، فقط یک
    // کوئری سبک هستن که با هر keystroke (debounced) صدا زده می‌شن.
    Route::get('/check-slug', [SalonSignupController::class, 'checkSlug'])
        ->middleware('throttle:60,1')
        ->name('check-slug');
    Route::get('/check-phone', [SalonSignupController::class, 'checkPhone'])
        ->middleware('throttle:60,1')
        ->name('check-phone');
});
