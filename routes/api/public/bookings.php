<?php

use App\Http\Controllers\User\BookingDiscountController;
use Illuminate\Support\Facades\Route;

Route::post('/check-discount', [BookingDiscountController::class, 'check']);
