<?php

use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\CustomerLoginController;
use App\Http\Controllers\Api\V1\Auth\StaffLoginController;
use App\Http\Controllers\Api\V1\Auth\StaffPasswordResetController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\Customer\CustomerAccountController;
use App\Http\Controllers\Api\V1\Customer\CustomerAuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerBookingController;
use App\Http\Controllers\Api\V1\Customer\CustomerCatalogController;
use App\Http\Controllers\Api\V1\Staff\StaffBookingController;
use App\Http\Controllers\Api\V1\Staff\StaffCalendarController;
use App\Http\Controllers\Api\V1\Staff\StaffNotificationController;
use App\Http\Controllers\Api\V1\Staff\StaffWalletController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Support\Facades\Route;

/*
 * API نسخه‌ی ۱ اپ‌های موبایل («ماهرو» و «ماهرو همکار») — بسته‌ی ۱ اپلیکیشن (۲۰۲۶-۱۰-۱۰).
 * از routes/api.php داخل prefix('v1') و ForceJsonResponse بارگذاری می‌شود؛ نام‌ها با «api.v1.» شروع می‌شوند.
 * قالب پاسخ: App\Http\Responses\ApiResponse؛ خطاها: App\Exceptions\Api\ApiExceptionRenderer.
 * ثبت‌نام و بازیابی رمز مشتری و همه‌ی مسیرهای اپ مشتری در بسته‌ی ۳ (۲۰۲۶-۱۰-۱۱)، بازیابی رمز کادر در بسته‌ی ۲.
 */

Route::get('status', StatusController::class)->middleware('throttle:api-v1')->name('status');

// ورود دومرحله‌ای (رمز ← کد پیامکی ← توکن Sanctum)
Route::middleware('throttle:api-v1-login')->group(function () {
    Route::prefix('staff/login')->name('staff.login')->controller(StaffLoginController::class)->group(function () {
        Route::post('/', 'login');
        Route::post('verify', 'verify')->name('.verify');
        Route::post('resend', 'resend')->name('.resend');
    });

    // بازیابی رمز کادر (بسته‌ی ۲)
    Route::prefix('staff/password')->name('staff.password.')->controller(StaffPasswordResetController::class)->group(function () {
        Route::post('forgot', 'forgot')->name('forgot');
        Route::post('resend', 'resend')->name('resend');
        Route::post('reset', 'reset')->name('reset');
    });

    Route::prefix('customer/salons/{salon_slug}/login')->name('customer.login')->middleware('salon.resolve')
        ->controller(CustomerLoginController::class)->group(function () {
            Route::post('/', 'login');
            Route::post('verify', 'verify')->name('.verify');
            Route::post('resend', 'resend')->name('.resend');
        });

    // ثبت‌نام و بازیابی رمز مشتری داخل سالن (بسته‌ی ۳؛ ورود مشتری فقط از سالن خودش — تصمیم ۲۰۲۶-۱۰-۱۰)
    Route::prefix('customer/salons/{salon_slug}')->name('customer.')->middleware('salon.resolve')
        ->controller(CustomerAuthController::class)->group(function () {
            Route::post('register', 'register')->name('register');
            Route::post('register/verify', 'verifyRegistration')->name('register.verify');
            Route::post('register/resend', 'resendRegistration')->name('register.resend');
            Route::post('password/forgot', 'forgotPassword')->name('password.forgot');
            Route::post('password/resend', 'resendPassword')->name('password.resend');
            Route::post('password/reset', 'resetPassword')->name('password.reset');
        });
});

// کارت عمومی سالن — تنها چیزی که پیش از ورود در اپ «ماهرو» دیده می‌شود (لینک/QR/کد سالن را اعتبارسنجی می‌کند)
Route::get('customer/salons/{salon_slug}', [CustomerAuthController::class, 'card'])
    ->middleware(['salon.resolve', 'throttle:api-v1'])->name('customer.salon.card');

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

    Route::prefix('notifications')->name('notifications.')->controller(StaffNotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('read-all', 'readAll')->name('read-all');
        Route::post('{notificationId}/read', 'read')->whereUuid('notificationId')->name('read');
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

// اپ «ماهرو» — مشتری (بسته‌ی ۳)؛ مشتری و سالن از توکن (حساب مشتری در هر سالن جداست)
Route::prefix('customer')->name('customer.')->middleware(['auth:sanctum', 'api.audience:customer', 'throttle:api-v1'])->group(function () {
    Route::controller(CustomerCatalogController::class)->group(function () {
        Route::get('salon', 'salonInfo')->name('salon');
        Route::get('home', 'home')->name('home');
        Route::get('services', 'services')->name('services');
        Route::get('services/{serviceId}/specialists', 'serviceSpecialists')->whereNumber('serviceId')->name('services.specialists');
        Route::get('specialists', 'specialists')->name('specialists');
        Route::get('specialists/{specialistId}', 'specialist')->whereNumber('specialistId')->name('specialists.show');
        Route::get('specialists/{specialistId}/dates', 'dates')->whereNumber('specialistId')->name('specialists.dates');
        Route::get('specialists/{specialistId}/slots', 'slots')->whereNumber('specialistId')->name('specialists.slots');
    });

    Route::prefix('bookings')->name('bookings.')->controller(CustomerBookingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('quote', 'quote')->name('quote');
        Route::post('/', 'store')->name('store');
        Route::get('{bookingId}', 'show')->whereNumber('bookingId')->name('show');
        Route::post('{bookingId}/cancel', 'cancel')->whereNumber('bookingId')->name('cancel');
        Route::post('{bookingId}/reschedule', 'reschedule')->whereNumber('bookingId')->name('reschedule');
        Route::post('{bookingId}/rate', 'rate')->whereNumber('bookingId')->name('rate');
    });

    Route::controller(CustomerAccountController::class)->group(function () {
        Route::get('wallet', 'wallet')->name('wallet.show');
        Route::get('wallet/transactions', 'walletTransactions')->name('wallet.transactions');

        Route::get('loyalty', 'loyalty')->name('loyalty.show');
        Route::get('loyalty/history', 'loyaltyHistory')->name('loyalty.history');
        Route::get('loyalty/codes', 'discountCodes')->name('loyalty.codes');
        Route::post('loyalty/rewards/{rewardId}/redeem', 'redeem')->whereNumber('rewardId')->name('loyalty.redeem');

        Route::get('profile', 'profile')->name('profile.show');
        Route::patch('profile', 'updateProfile')->name('profile.update');
        // رمز فعلی لازم است؛ سقف جدا تا حدس رمز از این مسیرها ممکن نباشد
        Route::put('password', 'updatePassword')->middleware('throttle:api-v1-sensitive')->name('password.update');
        Route::delete('account', 'destroy')->middleware('throttle:api-v1-sensitive')->name('account.destroy');

        Route::get('notifications', 'notifications')->name('notifications.index');
        Route::post('notifications/read-all', 'readAllNotifications')->name('notifications.read-all');
        Route::post('notifications/{notificationId}/read', 'readNotification')->whereUuid('notificationId')->name('notifications.read');
    });
});
