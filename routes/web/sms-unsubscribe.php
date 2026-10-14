<?php

use App\Http\Controllers\Marketing\SmsUnsubscribeController;
use Illuminate\Support\Facades\Route;

// انصراف از پیامک‌های پیگیری/تبلیغاتی سالن با لینک امضاشده‌ی داخل پیامک (۲۰۲۶-۱۰-۱۴) — بدون ورود. GET فقط صفحه‌ی تأیید است تا
// پیش‌نمایش خودکار لینک در گوشی مشتری را بی‌خبر لغو اشتراک نکند؛ انصراف با POST همان آدرس امضاشده.
Route::prefix('sms/unsubscribe')->name('sms.unsubscribe')->middleware(['signed', 'throttle:30,1'])->controller(SmsUnsubscribeController::class)->group(function () {
    Route::get('{customer}', 'show')->whereNumber('customer');
    Route::post('{customer}', 'store')->whereNumber('customer')->name('.store');
});
