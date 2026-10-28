<?php

use App\Http\Controllers\Legal\CustomerTermsUpdateController;
use Illuminate\Support\Facades\Route;

/*
 * «قوانین به‌روز شد» مشتری (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸) — داخل گروه auth مشتری همین سالن.
 */
Route::get('/terms-update', [CustomerTermsUpdateController::class, 'show'])->name('terms-update.show');
Route::post('/terms-update', [CustomerTermsUpdateController::class, 'accept'])->middleware('throttle:sensitive')->name('terms-update.accept');
