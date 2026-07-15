<?php

use App\Http\Controllers\User\UserWalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('wallet')->name('wallet.')->group(function () {

    Route::get('/', [UserWalletController::class, 'index'])
        ->name('index');

    Route::get('/transactions', [UserWalletController::class, 'transactions'])
        ->name('transactions');

    Route::get('/transactions/{transaction}', [UserWalletController::class, 'showTransaction'])
        ->name('transactions.show');

    Route::get('/charge', [UserWalletController::class, 'showCharge'])
        ->name('charge');

    Route::post('/charge/process', [UserWalletController::class, 'processCharge'])
        ->name('charge.process');

    Route::get('/charge/callback', [UserWalletController::class, 'chargeCallback'])
        ->name('charge.callback');

    Route::get('/charge/success', [UserWalletController::class, 'chargeSuccess'])
        ->name('charge.success');
});
