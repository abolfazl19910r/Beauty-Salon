<?php

use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');

    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');

    Route::get('/{role}/assign', [RoleController::class, 'assignUserForm'])->name('assign.form');
    Route::post('/{role}/assign', [RoleController::class, 'assignUser'])->name('assign');
    Route::delete('/{role}/users/{user}', [RoleController::class, 'removeUser'])->name('remove.user');
});
