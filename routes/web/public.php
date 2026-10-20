<?php

use App\Http\Controllers\User\BlogController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\ServiceController;
use Illuminate\Support\Facades\Route;

// ?src=qr (QR چاپی سالن، بسته‌ی ۵) یک بازدید QR ثبت می‌کند
Route::get('/', [HomeController::class, 'index'])->middleware('qr.scan')->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// بنر اطلاعیه‌های سالن در layouts/app (JSON) — از /api/s/{slug}/announcements/active منتقل شد (حذف /api قدیمی، ۲۰۲۶-۱۰-۱۱)
Route::get('/announcements/active', [\App\Http\Controllers\User\AnnouncementController::class, 'active'])->name('announcements.active');
