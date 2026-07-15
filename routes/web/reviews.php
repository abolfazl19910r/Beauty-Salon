<?php

use App\Http\Controllers\User\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/review/create', [ReviewController::class, 'create'])->name('reviews.create');
Route::post('/review/store', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/review/thank-you', [ReviewController::class, 'thankYou'])->name('reviews.thank-you');

Route::get('/specialists/{specialist}/reviews', [ReviewController::class, 'specialistReviews'])->name('reviews.specialist');
