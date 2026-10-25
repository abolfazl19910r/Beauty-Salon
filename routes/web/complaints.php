<?php

use App\Http\Controllers\User\ComplaintController;
use Illuminate\Support\Facades\Route;

// «شکایت و پیگیری» مشتری (بسته‌ی مجوز 5د) — فقط مشتری واردشده‌ی همین سالن (گروه auth + salon.customer در routes/web.php)
Route::prefix('complaints')->name('complaints.')->controller(ComplaintController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->middleware('throttle:10,60')->name('store');
    Route::get('/{complaint}', 'show')->whereNumber('complaint')->name('show');
    Route::post('/{complaint}/reply', 'reply')->whereNumber('complaint')->middleware('throttle:customer-actions')->name('reply');
});
