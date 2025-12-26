<?php

use App\Http\Controllers\Admin\AdminWalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [AdminWalletController::class, 'index'])->name('index');
    Route::get('/withdrawals', [AdminWalletController::class, 'withdrawals'])->name('withdrawals');
    Route::get('/settings', [AdminWalletController::class, 'settings'])->name('settings');
    Route::put('/settings', [AdminWalletController::class, 'updateSettings'])->name('settings.update');

    Route::get('/withdrawals/{withdrawalRequest}', [AdminWalletController::class, 'showWithdrawal'])->name('withdrawals.show');
    Route::post('/withdrawals/{withdrawalRequest}/approve', [AdminWalletController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawalRequest}/reject', [AdminWalletController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawalRequest}/auto-payout', [AdminWalletController::class, 'autoPayout'])->name('withdrawals.auto-payout');

    Route::get('/{wallet}', [AdminWalletController::class, 'show'])->name('show');
    Route::post('/{wallet}/verify-iban', [AdminWalletController::class, 'verifyIban'])->name('verify-iban');
    Route::post('/{wallet}/adjust', [AdminWalletController::class, 'adjust'])->name('adjust');
});
