<?php

use App\Http\Controllers\Admin\AdminRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [AdminRoleController::class, 'index'])->name('index');
    Route::get('/create', [AdminRoleController::class, 'create'])->name('create');
    Route::post('/', [AdminRoleController::class, 'store'])->name('store');

    Route::get('/{role}', [AdminRoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [AdminRoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [AdminRoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [AdminRoleController::class, 'destroy'])->name('destroy');

    Route::get('/{role}/assign', [AdminRoleController::class, 'assignForm'])->name('assign.form');
    Route::post('/{role}/assign', [AdminRoleController::class, 'assign'])->name('assign');
    Route::delete('/{role}/users/{user}', [AdminRoleController::class, 'removeUser'])->name('remove.user');
});
