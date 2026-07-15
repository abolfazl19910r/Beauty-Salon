<?php

use App\Http\Controllers\User\ServiceController;
use App\Http\Controllers\User\SpecialistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/services/search', [ServiceController::class, 'search'])->name('services.search');
    Route::get('/services/filter', [ServiceController::class, 'filter'])->name('services.filter');

    Route::get('/services/category/{category}', [ServiceController::class, 'byCategory'])->name('services.by-category');

    Route::get('/services/popular', [ServiceController::class, 'popular'])->name('services.popular');

    Route::get('/services/new', [ServiceController::class, 'newest'])->name('services.newest');

    Route::get('/services/discounted', [ServiceController::class, 'discounted'])->name('services.discounted');

    Route::get('/services/compare', [ServiceController::class, 'compare'])->name('services.compare');

    Route::prefix('specialists')->name('specialists.')->group(function () {
        Route::get('/search', [SpecialistController::class, 'search'])->name('search');

        Route::get('/by-service/{service}', [SpecialistController::class, 'byService'])->name('by-service');

        Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])->name('availability');
        Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])->name('available-slots');

        Route::get('/top-rated', [SpecialistController::class, 'topRated'])->name('top-rated');
    });

    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/', [ServiceController::class, 'favorites'])->name('index');
        Route::post('/services/{service}', [ServiceController::class, 'addToFavorites'])->name('add');
        Route::delete('/services/{service}', [ServiceController::class, 'removeFromFavorites'])->name('remove');
    });

    Route::get('/service-history', [ServiceController::class, 'history'])->name('services.history');

    Route::get('/services/{service}/similar', [ServiceController::class, 'similar'])->name('services.similar');

    Route::post('/services/{service}/review', [ServiceController::class, 'addReview'])->name('services.add-review');
    Route::get('/services/{service}/reviews', [ServiceController::class, 'getReviews'])->name('services.reviews');
});
