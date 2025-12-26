<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserWalletController;

Route::prefix('wallet')->name('wallet.')->group(function () {

    Route::get('/', [UserWalletController::class, 'index'])
        ->name('index');

    Route::get('/transactions', [UserWalletController::class, 'transactions'])
        ->name('transactions');

});
