<?php

use App\Http\Controllers\Admin\AdminBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/bookings/stats', [AdminBookingController::class, 'getStats']);
