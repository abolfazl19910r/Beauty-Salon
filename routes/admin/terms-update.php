<?php

use App\Http\Controllers\Admin\Legal\AdminTermsUpdateController;
use Illuminate\Support\Facades\Route;

// «قوانین به‌روز شد» مالک سالن (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸) — داخل گروه salon.owner در routes/web.php
Route::get('/terms-update', [AdminTermsUpdateController::class, 'show'])->name('terms-update.show');
Route::post('/terms-update', [AdminTermsUpdateController::class, 'accept'])->middleware('throttle:10,1')->name('terms-update.accept');
