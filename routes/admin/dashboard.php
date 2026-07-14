<?php

use App\Http\Controllers\Admin\Dashboard\AdminDashboardAnalyticsController;
use App\Http\Controllers\Admin\Dashboard\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');

// Note: This endpoint is not currently called by dashboard_blade.php (the chart reads from
// /admin/reports/{period}). It has been kept, not removed, as per project decision.
Route::get('/dashboard/data', [AdminDashboardAnalyticsController::class, 'getData'])->name('dashboard.data');
