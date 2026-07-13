<?php

use App\Http\Controllers\Admin\Loyalty\AdminLoyaltySettingsController;
use App\Http\Controllers\Admin\Loyalty\Point\AdminLoyaltyPointsController;
use App\Http\Controllers\Admin\Loyalty\Reward\AdminLoyaltyRewardController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('admin.loyalty.')->group(function () {

    // امتیازات
    Route::get('/points', [AdminLoyaltyPointsController::class, 'getPoints'])->name('points');
    Route::get('/history', [AdminLoyaltyPointsController::class, 'getHistory'])->name('history');
    Route::get('/statistics', [AdminLoyaltyPointsController::class, 'getStatistics'])->name('statistics');
    Route::get('/export', [AdminLoyaltyPointsController::class, 'export'])->name('export');

    Route::prefix('users/{user}')->name('users.')->group(function () {
        Route::get('/points', [AdminLoyaltyPointsController::class, 'getUserPoints'])->name('points');
        Route::post('/points/add', [AdminLoyaltyPointsController::class, 'addUserPoints'])->name('add-points');
        Route::post('/points/deduct', [AdminLoyaltyPointsController::class, 'deductUserPoints'])->name('deduct-points');
    });

    // پاداش‌ها (API)
    Route::get('/rewards', [AdminLoyaltyRewardController::class, 'getRewards'])->name('rewards');
    Route::post('/rewards', [AdminLoyaltyRewardController::class, 'storeReward'])->name('rewards.store');
    Route::get('/rewards/{reward}', [AdminLoyaltyRewardController::class, 'showReward'])->name('rewards.show');
    Route::put('/rewards/{reward}', [AdminLoyaltyRewardController::class, 'updateReward'])->name('rewards.update');
    Route::delete('/rewards/{reward}', [AdminLoyaltyRewardController::class, 'destroyReward'])->name('rewards.destroy');

    // تنظیمات
    Route::get('/settings', [AdminLoyaltySettingsController::class, 'getSettings'])->name('settings');
    Route::put('/settings', [AdminLoyaltySettingsController::class, 'updateSettings'])->name('settings.update');
});

