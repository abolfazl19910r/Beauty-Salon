<?php

use App\Http\Controllers\User\ReviewController;
use App\Http\Controllers\User\SpecialistController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ Commit 4b-3 (feat/saas-multi-tenant-salons): split out of routes/web/specialistprofile.php,
 * which had this exact block under a "PUBLIC ROUTES (no auth required)" comment — while actually
 * nested inside the old global `Route::middleware(['auth', 'verified'])->group()` wrapper in
 * web.php, so it was never truly public. Fixed as a side effect of this split, not the point of
 * it. reviews.specialist (viewing a specialist's reviews) is included here rather than staying
 * in reviews.php — it's read-only content on the same public specialist-profile page as
 * everything else in this file; only reviews.create/store/thank-you (writing a review, which
 * requires an authenticated customer) stayed behind in reviews.php.
 */
Route::prefix('specialists')->name('specialists.')->group(function () {
    Route::get('/search', [SpecialistController::class, 'search'])->name('search');
    Route::get('/service/{service}', [SpecialistController::class, 'byService'])->name('by-service-web');
    Route::get('/top-rated', [SpecialistController::class, 'topRated'])->name('top-rated');
    Route::get('/{specialist}', [SpecialistController::class, 'show'])->name('show');
});

// ⭐ Fix (2026-09-20 cleanup): specialists.availability/available-slots used to be duplicated
// here AND in web/services.php (identical method+URI: 'specialists/{specialist}/availability'
// and '.../available-slots/{date}'), both under the same 's/{salon_slug}' domain/prefix.
// Illuminate\Routing\RouteCollection::addToCollections() keys routes by "$method.$domainAndUri",
// so registering the same method+URI twice doesn't add two dispatchable routes — the SECOND
// registration silently overwrites the first in that keyed array. Because $tenantRoutes requires
// this file before services.php (see routes/web.php), services.php's copy — nested inside the
// ['auth', 'verified', 'salon.customer'] group — was the one actually winning the overwrite, so
// this "public" pair was already unreachable dead code, not a live public endpoint; the ->name()
// table (last-registered-wins there too) happened to agree, so route() helpers were never
// pointing at a stale URL either. Confirmed nothing in the actual front-end calls
// specialists.availability/available-slots by name — resources/views/bookings/*.blade.php use
// the separate bookings.available-slots route (routes/web/bookings.php, also auth-gated) for the
// real slot-picker; specialists.availability's own Blade fallback (view('specialists.availability'))
// has no resources/views/specialists/ directory backing it either, so it only ever worked for
// wantsJson() requests. Removed here (dead code) and left as the sole definition in
// services.php, which is what tests (CrossSalonCustomerFacingImplicitBindingTest,
// SpecialistControllerTest — both use actingAs()) already exercise. If a genuinely public,
// no-login availability preview is wanted later, it needs a distinct route name/URI here, not a
// re-collision with services.php's.

// Declared outside the 'specialists.' name group above specifically to keep its original exact
// name (reviews.specialist) — nesting it inside that group would have made it
// 'specialists.reviews.specialist' or similar, an unnecessary rename with no upside.
Route::get('/specialists/{specialist}/reviews', [ReviewController::class, 'specialistReviews'])->name('reviews.specialist');
