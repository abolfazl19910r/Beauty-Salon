<?php

use App\Http\Controllers\LoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/overview', [LoyaltyController::class, 'overview'])->name('overview');
    Route::get('/points', [LoyaltyController::class, 'getPoints'])->name('points');
    Route::get('/points/history', [LoyaltyController::class, 'history'])->name('history');
    Route::get('/rewards', [LoyaltyController::class, 'getRewards'])->name('rewards');
    Route::get('/progress', [LoyaltyController::class, 'getProgress'])->name('progress');

    Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward'])->name('redeem-reward');

    Route::get('/discount-codes', [LoyaltyController::class, 'discountCodes'])->name('discount-codes');
    Route::post('/discount-codes/validate', [LoyaltyController::class, 'validateDiscountCode'])->name('validate-discount');

    Route::get('/rewards/history', [LoyaltyController::class, 'rewardsHistory'])->name('rewards-history');

    Route::get('/points/expiring', [LoyaltyController::class, 'getExpiringPoints'])->name('expiring-points');

    Route::get('/transactions', [LoyaltyController::class, 'getTransactions'])->name('transactions');

    Route::get('/level', [LoyaltyController::class, 'getUserLevel'])->name('user-level');

    Route::get('/offers', [LoyaltyController::class, 'getSpecialOffers'])->name('offers');

    Route::get('/next-goal', [LoyaltyController::class, 'getNextGoal'])->name('next-goal');

    Route::get('/earning-options', [LoyaltyController::class, 'getEarningOptions'])->name('earning-options');

    Route::post('/redeem-gift', [LoyaltyController::class, 'redeemGift'])->name('redeem-gift');

    Route::post('/transfer-points', [LoyaltyController::class, 'transferPoints'])->name('transfer-points');

    Route::get('/export', [LoyaltyController::class, 'export'])->name('export');
});
