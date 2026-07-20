<?php

use App\Http\Controllers\Admin\Wallet\AdminWalletController;
use App\Http\Controllers\Admin\Wallet\AdminWalletSettingsController;
use App\Http\Controllers\Admin\Wallet\AdminWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [AdminWalletController::class, 'index'])->name('index');

    Route::post('/settle-pending', [AdminWalletController::class, 'settlePending'])->name('settle-pending');

    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals');
    Route::get('/settings', [AdminWalletSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [AdminWalletSettingsController::class, 'update'])->name('settings.update');

    Route::get('/withdrawals/{withdrawalRequest}', [AdminWithdrawalController::class, 'show'])->name('withdrawals.show');

    Route::put('/withdrawals/{withdrawalRequest}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::put('/withdrawals/{withdrawalRequest}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');

    Route::post('/withdrawals/{withdrawalRequest}/auto-payout', [AdminWithdrawalController::class, 'autoPayout'])->name('withdrawals.auto-payout');

    Route::get('/{wallet}', [AdminWalletController::class, 'show'])->name('show');
    Route::post('/{wallet}/verify-iban', [AdminWalletController::class, 'verifyIban'])->name('verify-iban');
    Route::post('/{wallet}/adjust', [AdminWalletController::class, 'adjust'])->name('adjust');
    Route::post('/{wallet}/settle-pending', [AdminWalletController::class, 'settlePendingForWallet'])->name('settle-pending-wallet');
});
