<?php

use App\Http\Controllers\Admin\Leave\AdminLeaveController;
use Illuminate\Support\Facades\Route;

Route::prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', [AdminLeaveController::class, 'index'])->name('index');
    Route::put('/{leave}', [AdminLeaveController::class, 'updateStatus'])->name('update');
    Route::get('/pending', [AdminLeaveController::class, 'pendingLeaves'])->name('pending');
});
