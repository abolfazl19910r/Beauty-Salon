<?php

use App\Http\Controllers\Admin\Dashboard\AdminDashboardAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->name('admin.dashboard.')->group(function () {
    Route::get('/', [AdminDashboardAnalyticsController::class, 'getData'])->name('data');
    Route::get('/popular-services', [AdminDashboardAnalyticsController::class, 'getPopularServices'])->name('popular-services');
    Route::get('/active-specialists', [AdminDashboardAnalyticsController::class, 'getActiveSpecialists'])->name('active-specialists');
});
