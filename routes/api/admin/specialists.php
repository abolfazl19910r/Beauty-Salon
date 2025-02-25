<?php

use App\Http\Controllers\Admin\AdminWorkScheduleController;
use App\Http\Controllers\Admin\AdminHolidayController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->group(function () {
    Route::get('/{specialist}/schedule/check', [AdminWorkScheduleController::class, 'checkAvailability']);
    Route::get('/{specialist}/schedule/slots', [AdminWorkScheduleController::class, 'getAvailableSlots']);

    Route::get('/{specialist}/holidays/check', [AdminHolidayController::class, 'checkDate']);
});
