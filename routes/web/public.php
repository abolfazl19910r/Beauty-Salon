<?php

use App\Http\Controllers\User\BlogController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\ServiceController;
use Illuminate\Support\Facades\Route;

// ?src=qr (QR چاپی سالن، بسته‌ی ۵) یک بازدید QR ثبت می‌کند
Route::get('/', [HomeController::class, 'index'])->middleware('qr.scan')->name('home');

// PWA سایت سالن (بسته‌ی ۶، آیفون: «افزودن به صفحه‌ی اصلی»): manifest و آیکون از لوگوی سالن
// سئو (۲۰۲۶-۱۱-۲۳): robots و sitemap هر سالن روی زیردامنه‌ی خودش (پیش از route‌های بی‌دامنه‌ی هم‌نام دامنه‌ی اصلی ثبت می‌شود)
Route::get('/robots.txt', \App\Http\Controllers\Seo\RobotsController::class)->name('salon.robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\Seo\SitemapController::class, 'salon'])->name('salon.sitemap');

Route::get('/manifest.webmanifest', [\App\Http\Controllers\Pwa\PwaController::class, 'salonManifest'])->name('salon.pwa.manifest');
Route::get('/pwa/icon-{variant}.png', [\App\Http\Controllers\Pwa\PwaController::class, 'salonIcon'])
    ->whereIn('variant', array_keys(\App\Services\Pwa\SalonPwaIcon::VARIANTS))->name('salon.pwa.icon');

// صفحه‌ی «اپ» سالن (بسته‌ی ۵ و ۶): دانلود اپ اندروید + کد سالن، راهنمای PWA آیفون
Route::get('/app', \App\Http\Controllers\User\SalonAppController::class)->name('salon.app');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// بنر اطلاعیه‌های سالن در layouts/app (JSON) — از /api/s/{slug}/announcements/active منتقل شد (حذف /api قدیمی، ۲۰۲۶-۱۰-۱۱)
Route::get('/announcements/active', [\App\Http\Controllers\User\AnnouncementController::class, 'active'])->name('announcements.active');

// بسته‌ی ۵الف: روزها و ساعت‌های خالی فرم رزرو قالب «شاهکار» (بی ورود؛ رزرو قطعی همچنان bookings.create پشت ورود است)
Route::get('/free-slots', \App\Http\Controllers\User\BookingQuickSlotsController::class)->middleware('throttle:60,1')->name('salon.free-slots');

// بسته‌ی ۵الف: صفحه‌ی عمومی «نظرها»ی سالن (فقط‌خواندنی؛ ثبت نظر فقط از پیوند پیامک)
Route::get('/reviews', [\App\Http\Controllers\User\SalonReviewController::class, 'index'])->name('salon.reviews');
