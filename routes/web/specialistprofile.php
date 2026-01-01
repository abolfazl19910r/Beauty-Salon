<?php

use App\Http\Controllers\Specialist\SpecialistProfileController;
use App\Http\Controllers\Specialist\SpecialistReportController;
use App\Http\Controllers\Specialist\SpecialistReviewController;
use App\Http\Controllers\Specialist\SpecialistWalletController;
use App\Http\Controllers\SpecialistController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->name('specialists.')->group(function () {
    Route::get('/search', [SpecialistController::class, 'search'])->name('search');
    Route::get('/service/{service}', [SpecialistController::class, 'byService'])->name('by-service');
    Route::get('/top-rated', [SpecialistController::class, 'topRated'])->name('top-rated');
    Route::get('/{specialist}', [SpecialistController::class, 'show'])->name('show');
    Route::get('/{specialist}/availability', [SpecialistController::class, 'availability'])->name('availability');
    Route::get('/{specialist}/available-slots/{date}', [SpecialistController::class, 'availableSlots'])->name('available-slots');
});

Route::middleware(['auth', 'verified'])->name('specialist.')->group(function () {

    Route::get('/my-dashboard', [SpecialistProfileController::class, 'dashboardBookings'])->name('my-dashboard');

    Route::get('/specialist/profile', [SpecialistProfileController::class, 'show'])->name('profile.show');
    Route::get('/specialist/profile/edit', [SpecialistProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/specialist/profile', [SpecialistProfileController::class, 'update'])->name('profile.update');
    Route::put('/specialist/profile/password', [SpecialistProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/specialist/schedule', [SpecialistProfileController::class, 'schedule'])->name('schedule');
    Route::put('/specialist/schedule', [SpecialistProfileController::class, 'updateSchedule'])->name('schedule.update');

    Route::get('/specialist/leaves', [SpecialistProfileController::class, 'leaves'])->name('leaves');
    Route::post('/specialist/leaves', [SpecialistProfileController::class, 'storeLeave'])->name('leaves.store');
    Route::delete('/specialist/leaves/{leave}', [SpecialistProfileController::class, 'destroyLeave'])->name('leaves.destroy');
    Route::get('/specialist/leaves/create', [SpecialistProfileController::class, 'createLeave'])->name('leaves.create');

    Route::get('/specialist/bookings', [SpecialistProfileController::class, 'bookings'])->name('bookings');
    Route::put('/specialist/bookings/{booking}/complete', [SpecialistProfileController::class, 'completeBooking'])->name('bookings.complete');
    Route::put('/specialist/bookings/{booking}/mark-completed', [SpecialistProfileController::class, 'markAsCompleted'])->name('bookings.mark-completed');
    Route::put('/specialist/bookings/{booking}/cancel', [SpecialistProfileController::class, 'cancelBooking'])->name('bookings.cancel');
    Route::get('/specialist/bookings/{booking}', [SpecialistProfileController::class, 'showBooking'])->name('bookings.show');

    Route::get('/specialist/reports', [SpecialistReportController::class, 'index'])->name('reports.index');

    Route::prefix('specialist/wallet')->name('wallet.')->group(function () {
        Route::get('/', [SpecialistWalletController::class, 'index'])->name('index');
        Route::get('/transactions', [SpecialistWalletController::class, 'transactions'])->name('transactions');

        Route::get('/iban/edit', [SpecialistWalletController::class, 'editIban'])->name('edit-iban');
        Route::put('/iban', [SpecialistWalletController::class, 'updateIban'])->name('update-iban');

        Route::get('/withdrawal/create', [SpecialistWalletController::class, 'createWithdrawal'])->name('create-withdrawal');
        Route::post('/withdrawal', [SpecialistWalletController::class, 'storeWithdrawal'])->name('store-withdrawal');
        Route::delete('/withdrawal/{withdrawalRequest}', [SpecialistWalletController::class, 'cancelWithdrawal'])->name('cancel-withdrawal');

        Route::post('/calculate-fee', [SpecialistWalletController::class, 'calculateFee'])->name('calculate-fee');
    });

    Route::prefix('specialist')->group(function () {
        Route::get('/notifications', [SpecialistProfileController::class, 'notifications'])->name('notifications.index');
        Route::get('/notifications/latest', [SpecialistProfileController::class, 'latestNotifications'])->name('notifications.latest');
        Route::get('/notifications/count', [SpecialistProfileController::class, 'notificationsCount'])->name('notifications.count');
        Route::post('/notifications/{id}/read', [SpecialistProfileController::class, 'markNotificationAsRead'])->name('notifications.read');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [SpecialistReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [SpecialistReviewController::class, 'show'])->name('show');
        Route::post('/{review}/respond', [SpecialistReviewController::class, 'respond'])->name('respond');
        Route::put('/{review}/update-response', [SpecialistReviewController::class, 'updateResponse'])->name('update-response');
        Route::delete('/{review}/delete-response', [SpecialistReviewController::class, 'deleteResponse'])->name('delete-response');
        Route::get('/stats', [SpecialistReviewController::class, 'stats'])->name('stats');
    });
});
