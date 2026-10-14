<?php

use App\Http\Controllers\Admin\Courses\AdminCoursePackageController;
use App\Http\Controllers\Admin\Courses\AdminCustomerCourseController;
use Illuminate\Support\Facades\Route;

/*
 * دوره‌های درمانی در پنل مدیر (بسته‌ی ب، ۲۰۲۶-۱۰-۱۴). تعریف بسته، تنظیمات و فروش تازه فقط با امکان پلن؛ فهرست و صفحه‌ی دوره‌های
 * موجود و هر کاری روی آن‌ها (رزرو جلسه، پرداخت باقی، تمدید، لغو، یادداشت/عکس) همیشه — مشتری پولش را داده است.
 */
Route::prefix('courses')->name('courses.')->group(function () {
    Route::middleware('feature:treatment_courses')->group(function () {
        Route::get('/packages', [AdminCoursePackageController::class, 'index'])->name('packages.index');
        Route::get('/packages/create', [AdminCoursePackageController::class, 'create'])->name('packages.create');
        Route::post('/packages', [AdminCoursePackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{package}/edit', [AdminCoursePackageController::class, 'edit'])->whereNumber('package')->name('packages.edit');
        Route::put('/packages/{package}', [AdminCoursePackageController::class, 'update'])->whereNumber('package')->name('packages.update');
        Route::delete('/packages/{package}', [AdminCoursePackageController::class, 'destroy'])->whereNumber('package')->name('packages.destroy');
        Route::put('/settings', [AdminCoursePackageController::class, 'updateSettings'])->name('settings.update');
        Route::get('/sell', [AdminCustomerCourseController::class, 'sellForm'])->name('sell');
        Route::post('/sell', [AdminCustomerCourseController::class, 'sell'])->name('sell.store');
    });

    Route::get('/', [AdminCustomerCourseController::class, 'index'])->name('index');
    Route::get('/{course}', [AdminCustomerCourseController::class, 'show'])->whereNumber('course')->name('show');
    Route::post('/{course}/payments', [AdminCustomerCourseController::class, 'recordPayment'])->whereNumber('course')->name('payments.store');
    Route::post('/{course}/sessions', [AdminCustomerCourseController::class, 'book'])->whereNumber('course')->name('sessions.store');
    Route::put('/{course}/expiry', [AdminCustomerCourseController::class, 'extend'])->whereNumber('course')->name('extend');
    Route::post('/{course}/cancel', [AdminCustomerCourseController::class, 'cancel'])->whereNumber('course')->name('cancel');
    Route::post('/{course}/consent', [AdminCustomerCourseController::class, 'consent'])->whereNumber('course')->name('consent');
    Route::put('/{course}/sessions/{booking}/note', [AdminCustomerCourseController::class, 'saveNote'])->whereNumber(['course', 'booking'])->name('sessions.note');
    Route::post('/{course}/sessions/{booking}/photos', [AdminCustomerCourseController::class, 'uploadPhoto'])->whereNumber(['course', 'booking'])->name('sessions.photos.store');
    Route::get('/{course}/photos/{photo}', [AdminCustomerCourseController::class, 'photo'])->whereNumber(['course', 'photo'])->name('photos.show');
    Route::delete('/{course}/photos/{photo}', [AdminCustomerCourseController::class, 'destroyPhoto'])->whereNumber(['course', 'photo'])->name('photos.destroy');
});
