<?php

use App\Http\Controllers\Admin\AdminPermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('permissions')->name('permissions.')->middleware('permission:manage-roles')->group(function () {
    Route::get('/', [AdminPermissionController::class, 'index'])->name('index');
    Route::get('/filter', [AdminPermissionController::class, 'filter'])->name('filter');
    Route::get('/create', [AdminPermissionController::class, 'create'])->name('create');
    Route::post('/', [AdminPermissionController::class, 'store'])->name('store');
    Route::get('/{permission}', [AdminPermissionController::class, 'show'])->name('show');
    Route::get('/{permission}/edit', [AdminPermissionController::class, 'edit'])->name('edit');
    Route::put('/{permission}', [AdminPermissionController::class, 'update'])->name('update');
    Route::delete('/{permission}', [AdminPermissionController::class, 'destroy'])->name('destroy');
});
