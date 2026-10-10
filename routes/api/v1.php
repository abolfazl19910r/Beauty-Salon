<?php

use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Support\Facades\Route;

/*
 * API نسخه‌ی ۱ اپ‌های موبایل («ماهرو» و «ماهرو همکار») — بسته‌ی ۱ اپلیکیشن (۲۰۲۶-۱۰-۱۰).
 * از routes/api.php داخل prefix('v1') و ForceJsonResponse بارگذاری می‌شود؛ نام‌ها با «api.v1.» شروع می‌شوند.
 * قالب پاسخ: App\Http\Responses\ApiResponse؛ خطاها: App\Exceptions\Api\ApiExceptionRenderer.
 */

Route::get('status', StatusController::class)->middleware('throttle:api-v1')->name('status');
