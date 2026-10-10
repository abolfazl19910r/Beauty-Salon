<?php

use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\CustomerLoginController;
use App\Http\Controllers\Api\V1\Auth\StaffLoginController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Support\Facades\Route;

/*
 * API نسخه‌ی ۱ اپ‌های موبایل («ماهرو» و «ماهرو همکار») — بسته‌ی ۱ اپلیکیشن (۲۰۲۶-۱۰-۱۰).
 * از routes/api.php داخل prefix('v1') و ForceJsonResponse بارگذاری می‌شود؛ نام‌ها با «api.v1.» شروع می‌شوند.
 * قالب پاسخ: App\Http\Responses\ApiResponse؛ خطاها: App\Exceptions\Api\ApiExceptionRenderer.
 * ثبت‌نام و بازیابی رمز مشتری در بسته‌ی ۳، بازیابی رمز کادر در بسته‌ی ۲ (تصمیم ۲۰۲۶-۱۰-۱۰).
 */

Route::get('status', StatusController::class)->middleware('throttle:api-v1')->name('status');

// ورود دومرحله‌ای (رمز ← کد پیامکی ← توکن Sanctum)
Route::middleware('throttle:api-v1-login')->group(function () {
    Route::prefix('staff/login')->name('staff.login')->controller(StaffLoginController::class)->group(function () {
        Route::post('/', 'login');
        Route::post('verify', 'verify')->name('.verify');
        Route::post('resend', 'resend')->name('.resend');
    });

    Route::prefix('customer/salons/{salon_slug}/login')->name('customer.login')->middleware('salon.resolve')
        ->controller(CustomerLoginController::class)->group(function () {
            Route::post('/', 'login');
            Route::post('verify', 'verify')->name('.verify');
            Route::post('resend', 'resend')->name('.resend');
        });
});

// مشترک هر دو اپ؛ مسیرهای مخصوص یک اپ (بسته‌ی ۲ و ۳) با 'api.audience:staff' / 'api.audience:customer'
Route::middleware(['auth:sanctum', 'api.audience', 'throttle:api-v1'])->group(function () {
    Route::get('me', [AccountController::class, 'me'])->name('me');
    Route::post('logout', [AccountController::class, 'logout'])->name('logout');

    Route::get('tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('tokens/revoke-others', [TokenController::class, 'revokeOthers'])->name('tokens.revoke-others');
    Route::delete('tokens/{tokenId}', [TokenController::class, 'destroy'])->whereNumber('tokenId')->name('tokens.destroy');
});
