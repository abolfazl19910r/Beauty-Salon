<?php

use App\Http\Controllers\Specialist\Booking\SpecialistBookingManagementController;
use App\Http\Controllers\Specialist\Leave\SpecialistLeaveController;
use App\Http\Controllers\Specialist\Notification\SpecialistNotificationController;
use App\Http\Controllers\Specialist\Profile\SpecialistProfileController;
use App\Http\Controllers\Specialist\Report\SpecialistReportController;
use App\Http\Controllers\Specialist\Review\SpecialistReviewController;
use App\Http\Controllers\Specialist\Wallet\Iban\SpecialistIbanController;
use App\Http\Controllers\Specialist\Wallet\SpecialistWalletController;
use App\Http\Controllers\Specialist\Wallet\Withdrawal\SpecialistWithdrawalController;
use Illuminate\Support\Facades\Route;

// ⭐ Commit 4b-3 (feat/saas-multi-tenant-salons): the "PUBLIC ROUTES" block that used to sit
// above this one was moved out to routes/web/public-specialists.php — it was never actually
// public in practice (nested inside the old global auth group in web.php), and even once fixed
// it belongs under /s/{slug} with customers, not here with the specialist's own staff dashboard.
// This file is now ONLY the specialist's own panel — global, not salon-slug-prefixed, same
// shape as /admin. 'salon.specialist' (EnsureSpecialistSalonActive) is new here too: without it,
// CurrentSalon was never set for any of these routes at all, so BelongsToSalon's scope was
// silently inactive for anything here that doesn't already filter by specialist_id explicitly
// (WalletSetting::first() in SpecialistWalletController, for one — a genuine latent leak, not
// hypothetical, caught while doing this split).

// ============================================
// AUTHENTICATED ROUTES (specialist panel)
// ============================================

Route::middleware(['auth', 'verified', 'salon.specialist'])->name('specialist.')->group(function () {

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

    Route::get('/specialist/leaves', [SpecialistLeaveController::class, 'index'])
        ->name('leaves');

    Route::get('/specialist/leaves/index', [SpecialistLeaveController::class, 'index'])
        ->name('leaves.index');

    Route::prefix('specialist/leaves')->name('leaves.')->group(function () {
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

        // ✅ NEW: Route to redirect to notification link
        Route::get('/{id}', [SpecialistNotificationController::class, 'showAndRedirect'])->name('show');
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
