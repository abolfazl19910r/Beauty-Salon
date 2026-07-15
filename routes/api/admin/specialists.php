<?php

use App\Http\Controllers\Admin\Holiday\AdminHolidayController;
use App\Http\Controllers\Admin\Specialist\AdminSpecialistsWorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->group(function () {
    Route::get('/{specialist}/schedule/check', [AdminSpecialistsWorkScheduleController::class, 'checkAvailability']);
    Route::get('/{specialist}/schedule/slots', [AdminSpecialistsWorkScheduleController::class, 'getAvailableSlots']);

    Route::get('/{specialist}/holidays/check', [AdminHolidayController::class, 'checkDate']);
});
