<?php

use App\Http\Controllers\Admin\Holiday\AdminHolidayController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->group(function () {
    Route::get('/{specialist}/holidays/check', [AdminHolidayController::class, 'checkDate']);
});
