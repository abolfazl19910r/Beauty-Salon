<?php

use App\Http\Controllers\Admin\AdminLoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->group(function () {
    Route::get('/rewards', [AdminLoyaltyController::class, 'getRewards']);
    Route::post('/rewards', [AdminLoyaltyController::class, 'storeReward']);
    Route::get('/rewards/{reward}', [AdminLoyaltyController::class, 'showReward']);
    Route::put('/rewards/{reward}', [AdminLoyaltyController::class, 'updateReward']);
    Route::delete('/rewards/{reward}', [AdminLoyaltyController::class, 'destroyReward']);

    Route::get('/points', [AdminLoyaltyController::class, 'getPoints']);
    Route::get('/history', [AdminLoyaltyController::class, 'getHistory']);
    Route::get('/statistics', [AdminLoyaltyController::class, 'getStatistics']);
    Route::get('/export', [AdminLoyaltyController::class, 'export']);

    Route::post('/rewards/{reward}/redeem', [AdminLoyaltyController::class, 'redeemReward']);

    Route::get('/user/{user}/points', [AdminLoyaltyController::class, 'getUserPoints']);
    Route::post('/user/{user}/points/add', [AdminLoyaltyController::class, 'addUserPoints']);
    Route::post('/user/{user}/points/deduct', [AdminLoyaltyController::class, 'deductUserPoints']);

    Route::get('/settings', [AdminLoyaltyController::class, 'getSettings']);
    Route::post('/settings', [AdminLoyaltyController::class, 'updateSettings']);
});
