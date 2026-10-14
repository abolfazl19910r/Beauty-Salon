<?php

use App\Http\Controllers\User\CourseController;
use Illuminate\Support\Facades\Route;

/*
 * دوره‌های درمانی سالن برای بازدیدکننده (بسته‌ی ب، ۲۰۲۶-۱۰-۱۴) — فقط با امکان «دوره‌های درمانی» پلن سالن؛ بدون آن ۴۰۴.
 */
Route::prefix('courses')->name('courses.')->middleware('feature:treatment_courses,hide')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{package}', [CourseController::class, 'show'])->whereNumber('package')->name('show');
});
