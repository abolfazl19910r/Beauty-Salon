<?php

use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\CustomerLoginController;
use App\Http\Controllers\Api\V1\Auth\StaffLoginController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\Staff\StaffBookingController;
use App\Http\Controllers\Api\V1\Staff\StaffCalendarController;
use App\Http\Controllers\Api\V1\Staff\StaffWalletController;
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

// اپ «ماهرو همکار» — فقط متخصص (بسته‌ی ۲ اپلیکیشن)؛ متخصص و سالن از توکن
Route::prefix('staff')->name('staff.')->middleware(['auth:sanctum', 'api.audience:staff', 'throttle:api-v1'])->group(function () {
    Route::get('today', [StaffBookingController::class, 'today'])->name('today');

    Route::prefix('bookings')->name('bookings.')->controller(StaffBookingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{bookingId}', 'show')->whereNumber('bookingId')->name('show');
        Route::post('{bookingId}/confirm', 'confirm')->whereNumber('bookingId')->name('confirm');
        Route::post('{bookingId}/cancel', 'cancel')->whereNumber('bookingId')->name('cancel');
        Route::post('{bookingId}/complete', 'complete')->whereNumber('bookingId')->name('complete');
    });

    Route::controller(StaffCalendarController::class)->group(function () {
        Route::get('calendar', 'calendar')->name('calendar');
        Route::get('schedule', 'schedule')->name('schedule');
        Route::put('schedule', 'updateSchedule')->name('schedule.update');
        Route::get('leaves', 'leaves')->name('leaves.index');
        Route::post('leaves', 'storeLeave')->name('leaves.store');
        Route::delete('leaves/{leaveId}', 'destroyLeave')->whereNumber('leaveId')->name('leaves.destroy');
    });

    Route::prefix('wallet')->name('wallet.')->controller(StaffWalletController::class)->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('fee', 'fee')->name('fee');
        Route::get('transactions', 'transactions')->name('transactions');
        Route::get('withdrawals', 'withdrawals')->name('withdrawals.index');
        Route::post('withdrawals', 'storeWithdrawal')->name('withdrawals.store');
        Route::delete('withdrawals/{withdrawalId}', 'cancelWithdrawal')->whereNumber('withdrawalId')->name('withdrawals.cancel');
        // رمز فعلی لازم است؛ سقف جدا تا حدس رمز از این مسیر ممکن نباشد
        Route::put('iban', 'updateIban')->middleware('throttle:api-v1-sensitive')->name('iban.update');
    });
});
