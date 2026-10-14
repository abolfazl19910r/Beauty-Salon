<?php

use App\Http\Controllers\User\CourseController;
use Illuminate\Support\Facades\Route;

/*
 * خرید دوره (فقط با امکان پلن) و «دوره‌های من» (همیشه — دوره‌ی خریده‌شده بعد از تنزل پلن هم قابل استفاده است). بسته‌ی ب.
 */
Route::post('/courses/{package}/purchase', [CourseController::class, 'purchase'])
    ->whereNumber('package')->middleware(['feature:treatment_courses,hide', 'throttle:20,1'])->name('courses.purchase');

Route::prefix('my-courses')->name('my-courses.')->group(function () {
    Route::get('/', [CourseController::class, 'mine'])->name('index');
    Route::get('/payment/callback', [CourseController::class, 'callback'])->name('payment.callback');
    Route::get('/{course}', [CourseController::class, 'mineShow'])->whereNumber('course')->name('show');
    Route::get('/{course}/pay', [CourseController::class, 'payForm'])->whereNumber('course')->name('pay');
    Route::post('/{course}/pay', [CourseController::class, 'pay'])->whereNumber('course')->middleware('throttle:20,1')->name('pay.store');
    Route::get('/{course}/book', [CourseController::class, 'bookForm'])->whereNumber('course')->name('book');
    Route::post('/{course}/book', [CourseController::class, 'book'])->whereNumber('course')->middleware('throttle:20,1')->name('book.store');
    Route::get('/{course}/photos/{photo}', [CourseController::class, 'photo'])->whereNumber(['course', 'photo'])->name('photos.show');
    Route::delete('/{course}/photos/{photo}', [CourseController::class, 'destroyPhoto'])->whereNumber(['course', 'photo'])->name('photos.destroy');
});
