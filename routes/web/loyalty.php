<?php

use App\Http\Controllers\User\LoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/', [LoyaltyController::class, 'index'])->name('index');
    Route::get('/points', [LoyaltyController::class, 'getPoints'])->name('points');
    Route::get('/history', [LoyaltyController::class, 'getHistory'])->name('history');
    Route::get('/rewards', [LoyaltyController::class, 'getRewards'])->name('rewards');
    Route::get('/progress', [LoyaltyController::class, 'getProgress'])->name('progress');
    Route::get('/overview', [LoyaltyController::class, 'overview'])->name('overview');

    Route::get('/discount-codes', [LoyaltyController::class, 'discountCodes'])->name('discount-codes');
    Route::get('/my-codes', [LoyaltyController::class, 'myCodes'])->name('my-codes');
    Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward'])->name('redeem');
});
