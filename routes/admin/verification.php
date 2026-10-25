<?php

use App\Http\Controllers\Admin\Verification\AdminSalonVerificationController;
use Illuminate\Support\Facades\Route;

// احراز سالن (بسته‌ی مجوز 5د) — فقط مالک، داخل گروه salon.owner در routes/web.php
Route::get('/verification', [AdminSalonVerificationController::class, 'show'])->name('verification.show');
Route::post('/verification', [AdminSalonVerificationController::class, 'store'])->middleware('throttle:10,60')->name('verification.store');
