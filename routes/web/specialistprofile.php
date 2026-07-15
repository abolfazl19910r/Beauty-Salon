<?php

use App\Http\Controllers\Specialist\SpecialistBookingManagementController;
use App\Http\Controllers\Specialist\SpecialistLeaveController;
use App\Http\Controllers\Specialist\SpecialistNotificationController;
use App\Http\Controllers\Specialist\SpecialistProfileController;
use App\Http\Controllers\Specialist\SpecialistReportController;
use App\Http\Controllers\Specialist\SpecialistReviewController;
use App\Http\Controllers\Specialist\Wallet\Iban\SpecialistIbanController;
use App\Http\Controllers\Specialist\Wallet\SpecialistWalletController;
use App\Http\Controllers\Specialist\Wallet\Withdrawal\SpecialistWithdrawalController;
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

    Route::get('/my-dashboard', [SpecialistProfileController::class, 'dashboard'])->name('my-dashboard');

    Route::prefix('specialist')->group(function () {
        Route::get('/profile', [SpecialistProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [SpecialistProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [SpecialistProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [SpecialistProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/schedule', [SpecialistProfileController::class, 'schedule'])->name('schedule');
        Route::put('/schedule', [SpecialistProfileController::class, 'updateSchedule'])->name('schedule.update');

        Route::get('/loyalty', [SpecialistProfileController::class, 'loyalty'])->name('loyalty');
    });

    Route::prefix('specialist/leaves')->name('leaves.')->group(function () {
        Route::get('/', [SpecialistLeaveController::class, 'index'])->name('index');
        Route::get('/create', [SpecialistLeaveController::class, 'create'])->name('create');
        Route::post('/', [SpecialistLeaveController::class, 'store'])->name('store');
        Route::delete('/{leave}', [SpecialistLeaveController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('specialist/bookings')->name('bookings.')->group(function () {
        Route::get('/', [SpecialistBookingManagementController::class, 'index'])->name('index');
        Route::get('/{booking}', [SpecialistBookingManagementController::class, 'show'])->name('show');
        Route::put('/{booking}/complete', [SpecialistBookingManagementController::class, 'complete'])->name('complete');
        Route::put('/{booking}/mark-completed', [SpecialistBookingManagementController::class, 'markAsCompleted'])->name('mark-completed');
        Route::put('/{booking}/cancel', [SpecialistBookingManagementController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('specialist/notifications')->name('notifications.')->group(function () {
        Route::get('/', [SpecialistNotificationController::class, 'index'])->name('index');
        Route::get('/latest', [SpecialistNotificationController::class, 'latest'])->name('latest');
        Route::get('/count', [SpecialistNotificationController::class, 'count'])->name('count');
        Route::post('/mark-all-read', [SpecialistNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/{id}/read', [SpecialistNotificationController::class, 'markAsRead'])->name('read');
    });

    Route::get('/specialist/reports', [SpecialistReportController::class, 'index'])->name('reports.index');

    Route::prefix('specialist/wallet')->name('wallet.')->group(function () {
        Route::get('/', [SpecialistWalletController::class, 'index'])->name('index');
        Route::get('/transactions', [SpecialistWalletController::class, 'transactions'])->name('transactions');
        Route::post('/calculate-fee', [SpecialistWalletController::class, 'calculateFee'])->name('calculate-fee');

        Route::get('/iban/edit', [SpecialistIbanController::class, 'edit'])->name('edit-iban');
        Route::put('/iban', [SpecialistIbanController::class, 'update'])->name('update-iban');

        Route::get('/withdrawal/create', [SpecialistWithdrawalController::class, 'create'])->name('create-withdrawal');
        Route::post('/withdrawal', [SpecialistWithdrawalController::class, 'store'])->name('store-withdrawal');
        Route::delete('/withdrawal/{withdrawalRequest}', [SpecialistWithdrawalController::class, 'cancel'])->name('cancel-withdrawal');
    });

    Route::prefix('specialist/reviews')->name('reviews.')->group(function () {
        Route::get('/', [SpecialistReviewController::class, 'index'])->name('index');
        Route::get('/stats', [SpecialistReviewController::class, 'stats'])->name('stats');
        Route::get('/{review}', [SpecialistReviewController::class, 'show'])->name('show');
        Route::post('/{review}/respond', [SpecialistReviewController::class, 'respond'])->name('respond');
        Route::put('/{review}/update-response', [SpecialistReviewController::class, 'updateResponse'])->name('update-response');
        Route::delete('/{review}/delete-response', [SpecialistReviewController::class, 'deleteResponse'])->name('delete-response');
    });
});
