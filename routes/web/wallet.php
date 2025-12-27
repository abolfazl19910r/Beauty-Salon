<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserWalletController;

Route::prefix('wallet')->name('wallet.')->group(function () {

    Route::get('/', [UserWalletController::class, 'index'])
        ->name('index');

    Route::get('/transactions', [UserWalletController::class, 'transactions'])
        ->name('transactions');

    Route::get('/charge', [UserWalletController::class, 'showCharge'])
        ->name('charge');

    Route::post('/charge/process', [UserWalletController::class, 'processCharge'])
        ->name('charge.process');

    Route::get('/charge/callback', [UserWalletController::class, 'chargeCallback'])
        ->name('charge.callback');

    Route::get('/charge/success', [UserWalletController::class, 'chargeSuccess'])
        ->name('charge.success');
});
