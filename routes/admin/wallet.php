<?php

use App\Http\Controllers\Admin\Wallet\AdminWalletController;
use App\Http\Controllers\Admin\Wallet\AdminWalletSettingsController;
use App\Http\Controllers\Admin\Wallet\AdminWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [AdminWalletController::class, 'index'])->name('index');

    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals');
    Route::get('/settings', [AdminWalletSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [AdminWalletSettingsController::class, 'update'])->name('settings.update');

    Route::get('/withdrawals/{withdrawalRequest}', [AdminWithdrawalController::class, 'show'])->name('withdrawals.show');

    // ⭐ فیکس باگ (فاز R-AdminWallet): این دو فرم در withdrawal-show.blade.php از @method('PUT') استفاده
    // می‌کنند اما روت قبلی POST بود؛ با فعال بودن method-spoofing میدل‌ور، درخواست واقعی PUT ارسال
    // می‌شد و به روت POST-only نمی‌خورد (405). به PUT تغییر کرد تا با Blade هماهنگ شود.
    Route::put('/withdrawals/{withdrawalRequest}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::put('/withdrawals/{withdrawalRequest}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');

    Route::post('/withdrawals/{withdrawalRequest}/auto-payout', [AdminWithdrawalController::class, 'autoPayout'])->name('withdrawals.auto-payout');

    Route::get('/{wallet}', [AdminWalletController::class, 'show'])->name('show');
    Route::post('/{wallet}/verify-iban', [AdminWalletController::class, 'verifyIban'])->name('verify-iban');
    Route::post('/{wallet}/adjust', [AdminWalletController::class, 'adjust'])->name('adjust');
});
