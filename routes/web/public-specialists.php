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
    Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])->name('availability');
    Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])->name('available-slots');
});

// Declared outside the 'specialists.' name group above specifically to keep its original exact
// name (reviews.specialist) — nesting it inside that group would have made it
// 'specialists.reviews.specialist' or similar, an unnecessary rename with no upside.
Route::get('/specialists/{specialist}/reviews', [ReviewController::class, 'specialistReviews'])->name('reviews.specialist');
