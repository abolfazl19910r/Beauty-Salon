<?php

use App\Http\Controllers\Admin\Profile\AdminProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [AdminProfileController::class, 'show'])->name('show');
    Route::get('/edit', [AdminProfileController::class, 'edit'])->name('edit');
    Route::patch('/update', [AdminProfileController::class, 'update'])->name('update');
    Route::put('/password', [AdminProfileController::class, 'updatePassword'])->name('password');
});
