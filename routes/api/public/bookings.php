<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::post('/check-discount', [BookingController::class, 'checkDiscount']);
