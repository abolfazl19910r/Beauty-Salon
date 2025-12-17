<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/specialists/{serviceId}', [BookingController::class, 'getSpecialistsByService']);
Route::get('/available-dates/{specialist}', [BookingController::class, 'getAvailableDates']);
Route::get('/time-slots/{specialist}/{date}', [BookingController::class, 'getAvailableTimeSlots']);
