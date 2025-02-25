<?php

use App\Http\Controllers\LoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->group(function () {
    Route::get('/overview', [LoyaltyController::class, 'overview']);
    Route::get('/points', [LoyaltyController::class, 'getPoints']);
    Route::get('/points/history', [LoyaltyController::class, 'history']);
    Route::get('/rewards', [LoyaltyController::class, 'getRewards']);
    Route::get('/progress', [LoyaltyController::class, 'getProgress']);

    Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward']);

    Route::get('/discount-codes', [LoyaltyController::class, 'discountCodes']);
    Route::post('/discount-codes/validate', [LoyaltyController::class, 'validateDiscountCode']);

    Route::get('/rewards/history', [LoyaltyController::class, 'rewardsHistory']);

    Route::get('/points/expiring', [LoyaltyController::class, 'getExpiringPoints']);

    Route::get('/transactions', [LoyaltyController::class, 'getTransactions']);

    Route::get('/level', [LoyaltyController::class, 'getUserLevel']);

    Route::get('/offers', [LoyaltyController::class, 'getSpecialOffers']);

    Route::get('/next-goal', [LoyaltyController::class, 'getNextGoal']);

    Route::get('/earning-options', [LoyaltyController::class, 'getEarningOptions']);

    Route::post('/redeem-gift', [LoyaltyController::class, 'redeemGift']);

    Route::post('/transfer-points', [LoyaltyController::class, 'transferPoints']);
});
