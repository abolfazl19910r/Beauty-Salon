<?php

use App\Http\Controllers\User\BookingController;
use Illuminate\Support\Facades\Route;

Route::post('/check-discount', [BookingController::class, 'checkDiscount']);
