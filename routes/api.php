<?php

use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    require __DIR__.'/api/public/services.php';
    require __DIR__.'/api/public/specialists.php';

    if (file_exists(__DIR__.'/api/public/bookings.php')) {
        require __DIR__.'/api/public/bookings.php';
    }

    if (file_exists(__DIR__.'/api/public/gallery.php')) {
        require __DIR__.'/api/public/gallery.php';
    }

    // ⭐ Fix (real, confirmed cross-tenant data leak, found while building a Blade replacement
    // for the removed AnnouncementBanner.jsx): every route in this api.php file sits OUTSIDE
    // the /s/{salon_slug} prefix, so ResolveSalonFromRoute never runs for any of them and
    // CurrentSalon stays unbound. Announcement (like every BelongsToSalon model) treats an
    // unbound CurrentSalon as "don't filter" by design (so tests/seeders/the superadmin panel
    // work without one) — meaning this endpoint returned EVERY salon's announcements mixed
    // together, confirmed directly: created one announcement per salon in two different salons,
    // hit /api/announcements/active with no salon context bound, and got both back in one
    // response. Moved under /s/{salon_slug} to close it, matching how every other genuinely
    // salon-scoped route in this project already works.
    //
    // ⚠️ Not fixed here (same leak, deliberately left alone): services.php, specialists.php,
    // bookings.php, and gallery.php in this same file are equally affected (confirmed
    // separately for services.php) but moving them has a much larger blast radius — finding and
    // updating every JS/Blade caller across the app — that needs its own dedicated pass, not a
    // change made as a side effect of an unrelated announcement-banner question.
    if (file_exists(__DIR__.'/api/public/announcements.php')) {
        Route::prefix('s/{salon_slug}')->middleware('salon.resolve')->group(function () {
            require __DIR__.'/api/public/announcements.php';
        });
    }

    Route::middleware('auth:sanctum')->group(function () {
        if (file_exists(__DIR__.'/api/auth/security.php')) {
            require __DIR__.'/api/auth/security.php';
        }

        if (file_exists(__DIR__.'/api/user/bookings.php')) {
            require __DIR__.'/api/user/bookings.php';
        }
        if (file_exists(__DIR__.'/api/user/payments.php')) {
            require __DIR__.'/api/user/payments.php';
        }
    });
});
