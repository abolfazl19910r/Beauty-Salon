<?php

use App\Http\Controllers\BookingAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/specialists/{serviceId}', [BookingAvailabilityController::class, 'getSpecialistsByService']);
Route::get('/available-dates/{specialist}', [BookingAvailabilityController::class, 'getAvailableDates']);
Route::get('/time-slots/{specialist}/{date}', [BookingAvailabilityController::class, 'getAvailableTimeSlots']);
