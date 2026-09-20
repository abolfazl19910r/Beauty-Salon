<?php

use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
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
    // ⭐ Fix (same audit continued, 2026-09-20): services.php (ServiceController::list()) and
    // specialists.php (all 3 routes, BookingAvailabilityController) — the two files that used to
    // sit right above this comment — had the identical leak, confirmed directly for each (two
    // salons, no salon context, both salons' data returned in one response). Both files are now
    // DELETED rather than moved here, because:
    //   - specialists.php's 3 routes (getSpecialistsByService/getAvailableDates/
    //     getAvailableTimeSlots) were exact duplicates, same controller/methods, of the
    //     already-scoped, already-auth-protected bookings.service-specialists/available-dates/
    //     available-slots routes in routes/web/bookings.php. Their only live callers
    //     (bookings/create.blade.php, bookings/reschedule.blade.php) now call those instead —
    //     duplicating the leak fix here would have meant two parallel routes to the same
    //     controller methods, one of them permanently a foot-gun.
    //   - services.php's single route (ServiceController::list()) had no scoped duplicate, so
    //     that one was genuinely moved (see bookings.services-list in routes/web/bookings.php) —
    //     along with a second, sneakier bug found in the same method: its 30-minute Cache::
    //     remember() key was the literal global string 'all_beauty_services', so even after
    //     scoping the query, an unscoped cache key would have let the first salon to hit the
    //     route silently serve its cached list to every other salon for the next 30 minutes (the
    //     exact same bug HomeController's home_services/home_specialists keys had before being
    //     fixed) — the key is now salon-suffixed, same pattern.
    //   - admin/schedule/index.blade.php also referenced a bare '/api/specialists' (never
    //     '/api/specialists/{id}'), but that's dead markup: the React mount it fed
    //     (window.initialData.routes.specialists) was removed from resources/js/admin.jsx in an
    //     earlier cleanup pass, so nothing ever actually fetches it. Left as-is — a separate,
    //     pre-existing dead-page bug, not a leak, out of scope here.
    //
    // ⚠️ Still not fixed (deliberately left alone): bookings.php and gallery.php below — neither
    // file exists in this codebase yet (both file_exists() guards are false), so there's nothing
    // to leak today. The moment either is added, give it the same treatment as above BEFORE
    // wiring it into a JS/Blade consumer: check whether routes/web/bookings.php (or an
    // equivalent salon-scoped file) already covers the same functionality first, and only add a
    // genuinely new scoped route if it doesn't.
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
