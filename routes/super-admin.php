<?php

use App\Http\Controllers\SuperAdmin\DemoRequestController;
use App\Http\Controllers\SuperAdmin\PlatformOperationsController;
use App\Http\Controllers\SuperAdmin\PlatformSettingsController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\SuperAdminPaymentController;
use App\Http\Controllers\SuperAdmin\SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\SupportTicketController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4). Required from
 * routes/web.php inside a group that already applies prefix('superadmin')->name('superadmin.')
 * ->middleware(['auth', 'super_admin']) — this file follows the same convention as
 * routes/admin/*.php (e.g. dashboard.php): no re-declared prefix/name/middleware in here, since
 * the wrapping group already supplies all of it. See EnsureSuperAdmin's docblock for why
 * 'super_admin' here means hasRole('super-admin'), not hasPermission('super_admin').
 *
 * SuperAdminController doesn't exist yet (commit 5) — safe to require this file now anyway,
 * since PHP only resolves the controller class when a request actually dispatches to one of
 * these routes, not at route-registration time.
 */
Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

Route::get('/salons', [SuperAdminController::class, 'index'])->name('salons.index');
Route::get('/salons/create', [SuperAdminController::class, 'create'])->name('salons.create');
Route::post('/salons', [SuperAdminController::class, 'store'])->name('salons.store');
Route::get('/salons/{salon}/edit', [SuperAdminController::class, 'edit'])->name('salons.edit');
Route::put('/salons/{salon}', [SuperAdminController::class, 'update'])->name('salons.update');
Route::post('/salons/{salon}/renew', [SuperAdminController::class, 'renewSubscription'])->name('salons.renew');
Route::post('/salons/{salon}/sms-credit', [SuperAdminController::class, 'grantSmsCredit'])->name('salons.sms-credit');
Route::get('/salons/{salon}/invoices', [SuperAdminController::class, 'invoices'])->name('salons.invoices');
Route::post('/salons/{salon}/toggle-suspend', [SuperAdminController::class, 'toggleSuspend'])->name('salons.toggle-suspend');

// ⭐ کیف پول / درآمد اشتراک (۲۰۲۶-۰۹-۲۴) — همه‌ی واریزهای خرید/تمدید اشتراک سالن‌ها.
Route::get('/payments', [SuperAdminPaymentController::class, 'index'])->name('payments.index');
Route::get('/payments/export', [SuperAdminPaymentController::class, 'export'])->name('payments.export');
Route::get('/payments/{invoice}', [SuperAdminPaymentController::class, 'show'])->whereNumber('invoice')->name('payments.show');

Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('index');
    Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
    Route::put('/{ticket}', [SupportTicketController::class, 'update'])->name('update');
    Route::post('/{ticket}/resolve', [SupportTicketController::class, 'resolve'])->name('resolve');
    Route::post('/{ticket}/close', [SupportTicketController::class, 'close'])->name('close');
    Route::post('/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('reopen');
});

// درخواست‌های دموی صفحه‌ی فروش (۲۰۲۶-۱۰-۱۴)
Route::get('/demo-requests', [DemoRequestController::class, 'index'])->name('demo-requests.index');
Route::patch('/demo-requests/{demoRequest}', [DemoRequestController::class, 'update'])->name('demo-requests.update');

// تنظیمات پلتفرم: راه‌های تماس صفحه‌ی فروش و ایمیل درخواست‌های دمو (۲۰۲۶-۱۰-۱۴)
Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
Route::put('/settings/legal', [PlatformSettingsController::class, 'updateLegal'])->name('settings.legal');
Route::put('/settings/push', [PlatformSettingsController::class, 'updatePush'])->name('settings.push');
Route::post('/settings/push/check', [PlatformSettingsController::class, 'checkPush'])->middleware('throttle:10,1')->name('settings.push.check');
Route::put('/settings/bot', [PlatformSettingsController::class, 'updateBot'])->middleware('throttle:20,1')->name('settings.bot');
Route::get('/push', [\App\Http\Controllers\SuperAdmin\PushStatsController::class, 'index'])->name('push.index');
// «دستگاه‌ها»ی پوش (بسته‌ی ۷، ۲۰۲۶-۱۰-۲۲)
Route::get('/push/devices', [\App\Http\Controllers\SuperAdmin\PushDeviceController::class, 'index'])->name('push.devices.index');
Route::get('/push/devices/{device}', [\App\Http\Controllers\SuperAdmin\PushDeviceController::class, 'show'])->whereNumber('device')->name('push.devices.show');
Route::post('/push/devices/{device}/test', [\App\Http\Controllers\SuperAdmin\PushDeviceController::class, 'test'])->whereNumber('device')->middleware('throttle:20,1')->name('push.devices.test');
Route::delete('/push/devices/{device}', [\App\Http\Controllers\SuperAdmin\PushDeviceController::class, 'signOut'])->whereNumber('device')->name('push.devices.sign-out');

// پلن‌ها و امکانات (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴) + پلن و استثنای امکانات هر سالن
Route::get('/plans', [SuperAdminPlanController::class, 'index'])->name('plans.index');
Route::get('/plans/create', [SuperAdminPlanController::class, 'create'])->name('plans.create');
Route::post('/plans', [SuperAdminPlanController::class, 'store'])->name('plans.store');
Route::get('/plans/{plan}/edit', [SuperAdminPlanController::class, 'edit'])->name('plans.edit');
Route::put('/plans/{plan}', [SuperAdminPlanController::class, 'update'])->name('plans.update');
Route::delete('/plans/{plan}', [SuperAdminPlanController::class, 'destroy'])->name('plans.destroy');
Route::put('/salons/{salon}/plan', [SuperAdminPlanController::class, 'assign'])->name('salons.plan');
Route::post('/salons/{salon}/feature-overrides', [SuperAdminPlanController::class, 'storeOverride'])->name('salons.feature-overrides.store');
Route::delete('/salons/{salon}/feature-overrides/{override}', [SuperAdminPlanController::class, 'destroyOverride'])->name('salons.feature-overrides.destroy');

// ⭐ بخش‌هایی که داده‌شان بود ولی در ابرمدیر دیده نمی‌شد (۲۰۲۶-۱۰-۱۵)
Route::get('/sms', [PlatformOperationsController::class, 'sms'])->name('sms.index');
Route::get('/payment-attention', [PlatformOperationsController::class, 'attention'])->name('payment-attention.index');
Route::post('/payment-attention/{transaction}/resolve', [PlatformOperationsController::class, 'resolveAttention'])->whereNumber('transaction')->name('payment-attention.resolve');
Route::get('/failed-jobs', [PlatformOperationsController::class, 'failedJobs'])->name('failed-jobs.index');
Route::post('/failed-jobs/retry-all', [PlatformOperationsController::class, 'retryAllFailedJobs'])->name('failed-jobs.retry-all');
Route::post('/failed-jobs/{uuid}/retry', [PlatformOperationsController::class, 'retryFailedJob'])->name('failed-jobs.retry');
Route::delete('/failed-jobs/{uuid}', [PlatformOperationsController::class, 'forgetFailedJob'])->name('failed-jobs.forget');

// ⭐ ابزارهای ابرمدیر (۲۰۲۶-۱۰-۱۵)
Route::post('/salons/{salon}/impersonate', [\App\Http\Controllers\SuperAdmin\ImpersonationController::class, 'start'])->middleware('throttle:20,1')->name('salons.impersonate');
Route::put('/salons/{salon}/owner', [\App\Http\Controllers\SuperAdmin\SalonOwnerController::class, 'update'])->name('salons.owner');
Route::post('/salons/{salon}/members', [\App\Http\Controllers\SuperAdmin\SalonMemberController::class, 'store'])->middleware('throttle:30,1')->name('salons.members.store');
Route::get('/notifications', [\App\Http\Controllers\SuperAdmin\NotificationInboxController::class, 'index'])->name('notifications.index');
Route::post('/notifications/read-all', [\App\Http\Controllers\SuperAdmin\NotificationInboxController::class, 'readAll'])->name('notifications.read-all');
Route::get('/notifications/{id}', [\App\Http\Controllers\SuperAdmin\NotificationInboxController::class, 'open'])->name('notifications.open');
Route::resource('announcements', \App\Http\Controllers\SuperAdmin\PlatformAnnouncementController::class)->except(['show']);
Route::get('/audit-log', [\App\Http\Controllers\SuperAdmin\AuditLogController::class, 'index'])->name('audit-log.index');
// احراز سالن‌ها (بسته‌ی مجوز 5د، مرحله‌ی الف دستی)
Route::prefix('verifications')->name('verifications.')->controller(\App\Http\Controllers\SuperAdmin\SalonVerificationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{verification}', 'show')->whereNumber('verification')->name('show');
    Route::get('/{verification}/file', 'file')->whereNumber('verification')->name('file');
    Route::post('/{verification}/approve', 'approve')->whereNumber('verification')->name('approve');
    Route::post('/{verification}/reject', 'reject')->whereNumber('verification')->name('reject');
});
// داده‌های نگهداری‌شده (بسته‌ی مجوز 5د): IP کارهای کلیدی و بایگانی حساب‌های حذف‌شده — فقط پاسخ به مرجع قضایی
Route::get('/retained-data', [\App\Http\Controllers\SuperAdmin\RetainedDataController::class, 'index'])->middleware('throttle:30,1')->name('retained-data.index');
Route::get('/search', [\App\Http\Controllers\SuperAdmin\PhoneSearchController::class, 'index'])->name('search.index');
Route::get('/templates', [\App\Http\Controllers\SuperAdmin\TemplateAccessController::class, 'index'])->name('templates.index');
Route::put('/templates', [\App\Http\Controllers\SuperAdmin\TemplateAccessController::class, 'update'])->name('templates.update');

// بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو سالن از طرف ماهرو (مدیر سالن نمی‌تواند بازش کند)
Route::post('/salons/{salon}/pause', [\App\Http\Controllers\SuperAdmin\SuperAdminSalonPauseController::class, 'pause'])->name('salons.pause');
Route::delete('/salons/{salon}/pause', [\App\Http\Controllers\SuperAdmin\SuperAdminSalonPauseController::class, 'resume'])->name('salons.resume');

// بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): نسخه‌های APK اپ مشتری و همکار برای صفحه‌ی «دانلود اپ ماهرو» (بدون مارکت)
Route::get('/app-releases', [\App\Http\Controllers\SuperAdmin\AppReleaseController::class, 'index'])->name('app-releases.index');
Route::post('/app-releases', [\App\Http\Controllers\SuperAdmin\AppReleaseController::class, 'store'])->name('app-releases.store');
Route::patch('/app-releases/{release}', [\App\Http\Controllers\SuperAdmin\AppReleaseController::class, 'update'])->whereNumber('release')->name('app-releases.update');
Route::delete('/app-releases/{release}', [\App\Http\Controllers\SuperAdmin\AppReleaseController::class, 'destroy'])->whereNumber('release')->name('app-releases.destroy');
