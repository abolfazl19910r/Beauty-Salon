<?php

use Illuminate\Support\Facades\Route;

// ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4b-2/4b-3): guest routes +
// the customer-only authenticated routes, all under one salon-scoped tenant boundary. Route
// NAMES are unchanged throughout (services.index, bookings.store, wallet.index, ...) — see
// ResolveSalonFromRoute's docblock for how URL::defaults() keeps every existing route() call
// across the codebase working without being touched.
//
// ⭐ فاز ۲ SaaS، محور «۳. ساب‌دامین اختصاصی» (شروع‌شده): این بلوک قبلاً همیشه یک
// Route::prefix('s/{salon_slug}') بود. حالا به‌ازای یک تصمیم سطح-boot (نه per-request —
// دلیلش را در config/app.php کنار 'central_domain' ببین) بین دو حالت انتخاب می‌کنه:
//   - config('app.central_domain') خالیه (پیش‌فرض; دقیقاً چیزی که همه‌ی ۱۰۱۳ تست فعلی
//     می‌بینن) → دقیقاً همون Route::prefix('s/{salon_slug}') قبلی، بدون هیچ تغییر رفتاری.
//   - مقداردهی شده (محلی: نیپ.آی‌اُو، production: دامنه‌ی واقعی) → همون مسیرها به‌جاش زیر
//     Route::domain('{salon_slug}.'.central_domain) ثبت می‌شن; یعنی سالن از ساب‌دامین
//     تشخیص داده می‌شه، نه از URI. همون middleware (salon.resolve) بدون تغییر کار می‌کنه.
$centralDomain = config('app.central_domain');

$tenantRoutes = function () {
    require __DIR__.'/web/public.php';
    // ⭐ Commit 4b-3: split out of specialistprofile.php — see that file's own docblock. Public
    // specialist browsing genuinely belongs here now, unlike before.
    require __DIR__.'/web/public-specialists.php';
    // ⭐ Customer identity redesign (confirmed 2026-08-30).
    require __DIR__.'/salon-auth.php';
    // ⭐ Fix (found sweeping the last of the /s/{slug} test failures): reviews.create/store/
    // thank-you were placed in the authenticated-customer group below by an earlier commit
    // (4b-3), on the mistaken assumption that they "require an authenticated customer" — but
    // ReviewController::create()/store() only ever check auth()->check() conditionally
    // (`if (auth()->check() && $booking->user_id !== auth()->id())`), because the real flow is a
    // guest clicking a one-time ReviewToken link from an SMS after their appointment, almost
    // never logged in at that moment. Wrapping these in ['auth', 'verified', 'salon.customer']
    // silently broke that entire flow — every such click redirected straight to the login page
    // instead of the review form. They belong here, alongside the other genuinely
    // guest-reachable routes, not in the authenticated block below.
    require __DIR__.'/web/reviews.php';

    // ⭐ Commit 4b-2: moved once the customer-identity redesign made "logged in" mean "logged
    // in as a customer of THIS salon" — 'salon.customer' (EnsureCustomerBelongsToSalon) closes
    // the gap auth() alone can't: a customer logged in to salon A opening salon B's URL while
    // still authenticated.
    Route::middleware(['auth', 'verified', 'salon.customer'])->group(function () {
        require __DIR__.'/web/profiles.php';
        require __DIR__.'/web/services.php';
        require __DIR__.'/web/bookings.php';
        require __DIR__.'/web/payments.php';
        require __DIR__.'/web/loyalty.php';
        require __DIR__.'/web/security.php';
        require __DIR__.'/web/wallet.php';
    });
};

if ($centralDomain) {
    Route::domain('{salon_slug}.'.$centralDomain)->middleware(['salon.resolve'])->group($tenantRoutes);

    // ⭐ محور «۳»: تصمیم بیزنسی تأییدشده (۲۰۲۶-۰۹-۱۹، به Rasta_unified_prompt.md نگاه کن) —
    // دامنه‌ی اصلی بدون ساب‌دامین فعلاً فقط یک صفحه‌ی placeholder ساده نشون می‌ده، نه یک
    // لندینگ کامل (اون با محور «۴. ثبت‌نام عمومی سالن» می‌آد، وقتی واقعاً یک فرم/CTA برای
    // لینک‌کردن بهش وجود داره).
    Route::domain($centralDomain)->group(function () {
        Route::view('/', 'central.placeholder')->name('central.home');
    });
} else {
    Route::prefix('s/{salon_slug}')->middleware(['salon.resolve'])->group($tenantRoutes);
}

require __DIR__.'/web/auth.php';
// ⭐ Commit 4b-3: this file is now ONLY the specialist's own staff dashboard (specialist.*) —
// the public browsing block that used to sit above it in the same file moved to
// web/public-specialists.php, under /s/{slug} above. Stays global/unprefixed, same shape as
// /admin, since a specialist authenticates globally (user_type='staff'), not through any
// salon's /s/{slug}/login.
require __DIR__.'/web/specialistprofile.php';

Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:access_admin_panel', 'salon.active'])->group(function () {

    Route::get('/', [\App\Http\Controllers\Admin\Dashboard\AdminDashboardController::class, 'dashboard'])->name('home');

    require __DIR__.'/admin/dashboard.php';
    require __DIR__.'/admin/profile.php';

    require __DIR__.'/admin/services.php';
    require __DIR__.'/admin/specialists.php';

    // ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — مدیریت ادمین‌های سالن (افزودن/ویرایش/حذف
    // owner یا staff دیگر) فقط برای owner همون سالن (به EnsureSalonOwner نگاه کن).
    Route::middleware(['salon.owner'])->group(function () {
        require __DIR__.'/admin/users.php';
    });

    require __DIR__.'/admin/search.php';

    require __DIR__.'/admin/bookings.php';
    require __DIR__.'/admin/payments.php';
    require __DIR__.'/admin/categories.php';
    require __DIR__.'/admin/schedule.php';
    require __DIR__.'/admin/leaves.php';
    require __DIR__.'/admin/gallery.php';
    require __DIR__.'/admin/loyalty.php';
    require __DIR__.'/admin/blog.php';
    require __DIR__.'/admin/announcements.php';
    require __DIR__.'/admin/discount-codes.php';

    require __DIR__.'/admin/notifications.php';

    require __DIR__.'/admin/security.php';
    require __DIR__.'/admin/notification-settings.php';
    require __DIR__.'/admin/roles.php';
    require __DIR__.'/admin/permissions.php';
    require __DIR__.'/admin/reviews.php';

    // ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — کیف‌پول/تسویه‌ی متخصصان و خرید/تمدید
    // اشتراک سالن هر دو «مالی» هستن (نقش «منشی» پیش‌فرض این پرمیشن رو نداره؛ به
    // 2026_09_19_000201_add_salon_staff_finance_permissions.php نگاه کن). is_admin=true
    // (owner) طبق bypass مستندشده‌ی PermissionMiddleware/hasPermission() همیشه رد می‌شه.
    // ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — گزارش‌ها (خلاصه‌ی مالی، نمودار درآمد،
    // تفکیک پرداخت، درآمد به‌تفکیک خدمت/متخصص، صادرات Excel/PDF از همین داده‌ها) هم مثل
    // wallet/billing کاملاً «مالی»ه؛ نمودار درآمدِ خودِ داشبورد اصلی (routes/admin/dashboard.php)
    // با داده‌ی سمت سرور رندر می‌شه (نه از این مسیرها)، فقط دکمه‌های فیلتر «امروز/هفته/ماه» به
    // اینجا fetch می‌زنن و از قبل یک catch() سالم دارن — پس گیت‌کردن این مسیرها چیزی رو نمی‌شکنه.
    Route::middleware(['permission:manage-wallet'])->group(function () {
        require __DIR__.'/admin/wallet.php';
        require __DIR__.'/admin/billing.php';
        require __DIR__.'/admin/reports.php';
    });
});

// ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4).
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'super_admin'])->group(function () {
    require __DIR__.'/super-admin.php';
});
