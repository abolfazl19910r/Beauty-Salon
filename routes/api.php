<?php

use Illuminate\Support\Facades\Route;

// webhook ربات پلتفرم بله/تلگرام (۲۰۲۶-۱۰-۰۱) — بدون session/CSRF؛ secret در مسیر
Route::post('bot/webhook/{messenger}/{secret}', \App\Http\Controllers\Bot\BotWebhookController::class)
    ->whereIn('messenger', \App\Models\BotLink::MESSENGERS)
    ->middleware('throttle:120,1')
    ->name('bot.webhook');

// ⭐ API اپ‌های موبایل فقط /api/v1 است (بسته‌ی ۱ اپلیکیشن، ۲۰۲۶-۱۰-۱۰) — همیشه JSON با قالب یکسان.
// /api قدیمی (bookings، payments/secure، security، announcements) در بسته‌ی ۳ (۲۰۲۶-۱۰-۱۱) حذف شد: نسخه‌ی v1 مشتری
// جایش را گرفت؛ دو مسیری که وب واقعاً صدا می‌زد (بنر اطلاعیه و شروع پرداخت امن) به مسیرهای وب سالن منتقل شدند.
Route::name('api.')->group(function () {
    Route::prefix('v1')->name('v1.')->middleware(\App\Http\Middleware\Api\ForceJsonResponse::class)->group(function () {
        require __DIR__.'/api/v1.php';
    });
});
