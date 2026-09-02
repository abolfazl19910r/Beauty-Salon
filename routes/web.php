<?php

use Illuminate\Support\Facades\Route;

// ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4b-2/4b-3): guest routes +
// the customer-only authenticated routes, all under one salon-scoped prefix. Route NAMES are
// unchanged throughout (services.index, bookings.store, wallet.index, ...) — see
// ResolveSalonFromRoute's docblock for how URL::defaults() keeps every existing route() call
// across the codebase working without being touched.
Route::prefix('s/{salon_slug}')->middleware(['salon.resolve'])->group(function () {
    require __DIR__.'/web/public.php';
    // ⭐ Commit 4b-3: split out of specialistprofile.php — see that file's own docblock. Public
    // specialist browsing genuinely belongs here now, unlike before.
    require __DIR__.'/web/public-specialists.php';
    // ⭐ Customer identity redesign (confirmed 2026-08-30).
    require __DIR__.'/salon-auth.php';

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
        // ⭐ Commit 4b-3: only the write routes (create/store/thank-you) — reviews.specialist
        // (read) moved to public-specialists.php above.
        require __DIR__.'/web/reviews.php';
    });
});

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
    require __DIR__.'/admin/users.php';

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

    require __DIR__.'/admin/reports.php';
    require __DIR__.'/admin/security.php';
    require __DIR__.'/admin/notification-settings.php';
    require __DIR__.'/admin/roles.php';
    require __DIR__.'/admin/permissions.php';
    require __DIR__.'/admin/wallet.php';
    require __DIR__.'/admin/reviews.php';
});

// ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4).
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'super_admin'])->group(function () {
    require __DIR__.'/super-admin.php';
});
