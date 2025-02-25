<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
Route::get('/dashboard/data', [AdminDashboardController::class, 'getData'])->name('dashboard.data');
