<?php

use App\Http\Controllers\Admin\AdminLoyaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/points', [AdminLoyaltyController::class, 'getPoints'])->name('points');
    Route::get('/rewards', [AdminLoyaltyController::class, 'getRewards'])->name('rewards');
    Route::get('/history', [AdminLoyaltyController::class, 'getHistory'])->name('history');
    Route::post('/rewards/{reward}/redeem', [AdminLoyaltyController::class, 'redeemReward'])->name('redeem-reward');
    Route::get('/export', [AdminLoyaltyController::class, 'export'])->name('export');
});
