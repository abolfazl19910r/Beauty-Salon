<?php

use App\Http\Controllers\Payment\AppPaymentController;
use Illuminate\Support\Facades\Route;

/*
 * پرداخت اپ «ماهرو» (بسته‌ی ۳، ۲۰۲۶-۱۰-۱۱) — زیر /s/{slug} و ساب‌دامین سالن، بدون ورود و بدون session (مرورگر بیرونی
 * گوشی، نه اپ). checkout فقط با لینک امضاشده‌ی API؛ callbackها فقط با tx معتبر همین سالن کاری انجام می‌دهند.
 */
Route::prefix('app')->name('app.')->middleware('throttle:60,1')->controller(AppPaymentController::class)->group(function () {
    Route::get('checkout/{publicId}', 'checkout')->whereUuid('publicId')->middleware('signed')->name('checkout');
    Route::get('payment/callback', 'booking')->name('payment.callback');
    Route::get('wallet/callback', 'wallet')->name('wallet.callback');
    // پرداخت دوره‌ی درمانی از اپ (بسته‌ی ب)
    Route::get('course/callback', 'course')->name('course.callback');
});
