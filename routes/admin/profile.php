<?php

use App\Http\Controllers\Admin\Profile\AdminProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [AdminProfileController::class, 'show'])->name('show');
    Route::get('/edit', [AdminProfileController::class, 'edit'])->name('edit');
    Route::patch('/update', [AdminProfileController::class, 'update'])->name('update');
    Route::put('/password', [AdminProfileController::class, 'updatePassword'])->name('password');
    // ربات پلتفرم: اتصال/قطع اتصال بله یا تلگرام (۲۰۲۶-۱۰-۰۱)
    Route::post('/bot/{messenger}', [\App\Http\Controllers\Bot\BotConnectController::class, 'store'])->whereIn('messenger', \App\Models\BotLink::MESSENGERS)->name('bot.connect');
    Route::delete('/bot/{messenger}', [\App\Http\Controllers\Bot\BotConnectController::class, 'destroy'])->whereIn('messenger', \App\Models\BotLink::MESSENGERS)->name('bot.disconnect');
});
