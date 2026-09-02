<?php

use App\Http\Controllers\User\ReviewController;
use Illuminate\Support\Facades\Route;

// ⭐ Commit 4b-3 (feat/saas-multi-tenant-salons): reviews.specialist (viewing reviews) moved to
// routes/web/public-specialists.php — it's guest-readable content on a public specialist
// profile page, unlike these three, which require an authenticated customer
// (ReviewController::create()/store() check the booking belongs to auth()->id()).
Route::get('/review/create', [ReviewController::class, 'create'])->name('reviews.create');
Route::post('/review/store', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/review/thank-you', [ReviewController::class, 'thankYou'])->name('reviews.thank-you');
