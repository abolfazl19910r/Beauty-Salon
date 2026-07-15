<?php

use App\Http\Controllers\Admin\Booking\AdminBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/bookings/stats', [AdminBookingController::class, 'getStats']);
