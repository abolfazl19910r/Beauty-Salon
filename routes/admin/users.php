<?php

use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::get('/create', [AdminUserController::class, 'create'])->name('create');
    Route::post('/', [AdminUserController::class, 'store'])->name('store');

    Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
    Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
    Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');

    Route::put('/{user}/status', [AdminUserController::class, 'updateStatus'])->name('status.update');
    Route::put('/{user}/password', [AdminUserController::class, 'resetPassword'])->name('password.reset');

    Route::post('/{user}/roles', [AdminUserController::class, 'syncRoles'])->name('roles.sync');
});
