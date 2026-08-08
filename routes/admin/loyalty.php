<?php

use App\Http\Controllers\Admin\Loyalty\AdminLoyaltyController;
use App\Http\Controllers\Admin\Loyalty\Reward\AdminLoyaltyRewardController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('loyalty.')->group(function () {

    // Home page
    Route::get('/', [AdminLoyaltyController::class, 'index'])->name('index');

    // Rewards (CRUD)
    Route::prefix('rewards')->name('rewards.')->group(function () {
        Route::get('/create', [AdminLoyaltyRewardController::class, 'create'])->name('create');
        Route::post('/', [AdminLoyaltyRewardController::class, 'store'])->name('store');
        Route::get('/{reward}', [AdminLoyaltyRewardController::class, 'show'])->name('show');
        Route::get('/{reward}/edit', [AdminLoyaltyRewardController::class, 'edit'])->name('edit');
        Route::put('/{reward}', [AdminLoyaltyRewardController::class, 'update'])->name('update');
        Route::delete('/{reward}', [AdminLoyaltyRewardController::class, 'destroy'])->name('destroy');
        Route::post('/{reward}/redeem', [AdminLoyaltyRewardController::class, 'redeemReward'])->name('redeem');
    });
});
