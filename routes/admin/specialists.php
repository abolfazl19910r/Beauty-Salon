<?php

use App\Http\Controllers\Admin\AdminSpecialistController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialists')->name('specialists.')->group(function () {
    Route::get('/', [AdminSpecialistController::class, 'index'])->name('index');
    Route::get('/create', [AdminSpecialistController::class, 'create'])->name('create');
    Route::post('/', [AdminSpecialistController::class, 'store'])->name('store');

    Route::get('/{specialist}', [AdminSpecialistController::class, 'show'])->name('show');
    Route::get('/{specialist}/edit', [AdminSpecialistController::class, 'edit'])->name('edit');
    Route::put('/{specialist}', [AdminSpecialistController::class, 'update'])->name('update');
    Route::delete('/{specialist}', [AdminSpecialistController::class, 'destroy'])->name('destroy');
});
