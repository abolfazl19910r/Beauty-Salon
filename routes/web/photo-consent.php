<?php

use App\Http\Controllers\Courses\PhotoConsentLinkController;
use Illuminate\Support\Facades\Route;

// لینک پیامکی رضایت عکس دوره (۲۰۲۶-۱۰-۲۸) — بدون ورود؛ توکن یک‌بارمصرف، فقط در سالن خودش
Route::get('/photo-consent/{token}', [PhotoConsentLinkController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1')->name('photo-consent.show');
Route::post('/photo-consent/{token}', [PhotoConsentLinkController::class, 'decide'])
    ->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:10,1')->name('photo-consent.decide');
