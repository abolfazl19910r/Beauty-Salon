<?php

use App\Http\Controllers\Admin\SalonQr\AdminSalonQrController;
use Illuminate\Support\Facades\Route;

// بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): QR سالن و متخصص‌ها — PNG اینستاگرام، PDF چاپی، بازدید از QR
Route::prefix('salon-qr')->name('salon-qr.')->group(function () {
    Route::get('/', [AdminSalonQrController::class, 'index'])->name('index');
    Route::get('/pdf', [AdminSalonQrController::class, 'pdf'])->middleware('throttle:30,1')->name('pdf');
});
