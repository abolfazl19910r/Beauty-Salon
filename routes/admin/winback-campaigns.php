<?php

use App\Http\Controllers\Admin\Marketing\AdminWinbackCampaignController;
use Illuminate\Support\Facades\Route;

// «ارسال دستی به مشتری‌های قدیمی» (بسته‌ی ج، ۲۰۲۶-۱۰-۱۴)
Route::prefix('winback/campaigns')->name('winback.campaigns.')->middleware('feature:winback_manual')->group(function () {
    Route::get('/', [AdminWinbackCampaignController::class, 'index'])->name('index');
    Route::get('/create', [AdminWinbackCampaignController::class, 'create'])->name('create');
    Route::post('/', [AdminWinbackCampaignController::class, 'store'])->middleware('throttle:30,1')->name('store');
    Route::get('/{campaign}', [AdminWinbackCampaignController::class, 'show'])->whereNumber('campaign')->name('show');
    Route::post('/{campaign}/pause', [AdminWinbackCampaignController::class, 'pause'])->whereNumber('campaign')->name('pause');
    Route::post('/{campaign}/resume', [AdminWinbackCampaignController::class, 'resume'])->whereNumber('campaign')->name('resume');
    Route::post('/{campaign}/cancel', [AdminWinbackCampaignController::class, 'cancel'])->whereNumber('campaign')->name('cancel');
});
