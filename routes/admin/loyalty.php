<?php

use App\Http\Controllers\Admin\AdminLoyaltyController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminLoyaltyController::class)->group(function () {
    Route::get('/loyalty', 'index')->name('loyalty.index');
    Route::get('/loyalty/create', 'create')->name('loyalty.create');
    Route::post('/loyalty', 'store')->name('loyalty.store');
    Route::get('/loyalty/{reward}', 'show')->name('loyalty.show');
    Route::get('/loyalty/{reward}/edit', 'edit')->name('loyalty.edit');
    Route::put('/loyalty/{reward}', 'update')->name('loyalty.update');
    Route::delete('/loyalty/{reward}', 'destroy')->name('loyalty.destroy');

    Route::get('/loyalty/points', 'getPoints')->name('loyalty.points');
    Route::get('/loyalty/rewards', 'getRewards')->name('loyalty.rewards');
    Route::get('/loyalty/history', 'getHistory')->name('loyalty.history');
    Route::post('/loyalty/rewards/{reward}/redeem', 'redeemReward')->name('loyalty.redeem-reward');
    Route::get('/loyalty/export', 'export')->name('loyalty.export');
});
