<?php

use App\Http\Controllers\User\AttendanceLinkController;
use Illuminate\Support\Facades\Route;

// تأیید حضور با لینک پیامک (بسته‌ی 5ه) — بدون ورود؛ توکن فقط در سالن خودش
Route::get('/attend/{token}', [AttendanceLinkController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1')->name('attendance.show');
Route::post('/attend/{token}', [AttendanceLinkController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:10,1')->name('attendance.confirm');
