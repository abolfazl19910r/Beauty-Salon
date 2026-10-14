<?php

use App\Http\Controllers\Admin\Marketing\AdminWinbackController;
use Illuminate\Support\Facades\Route;

// پیگیری خودکار مشتریانی که مدتی نیامده‌اند (۲۰۲۶-۱۰-۱۴)
Route::prefix('winback')->name('winback.')->middleware('feature:winback')->group(function () {
    Route::get('/', [AdminWinbackController::class, 'index'])->name('index');
    Route::put('/', [AdminWinbackController::class, 'update'])->name('update');
});
