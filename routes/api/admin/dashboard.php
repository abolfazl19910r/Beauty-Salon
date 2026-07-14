<?php

use App\Http\Controllers\Admin\Dashboard\AdminDashboardAnalyticsController;
use Illuminate\Support\Facades\Route;

// Note: These methods (except getData) are not currently called by any known clients —
// They were kept, not removed, by project decision. Previously, the route below had no name at all
// (small bug: name() was not called), fixed here.
Route::prefix('dashboard')->name('admin.dashboard.')->group(function () {
    Route::get('/', [AdminDashboardAnalyticsController::class, 'getData'])->name('data');
    Route::get('/daily-revenue', [AdminDashboardAnalyticsController::class, 'getDailyRevenue'])->name('daily-revenue');
    Route::get('/popular-services', [AdminDashboardAnalyticsController::class, 'getPopularServices'])->name('popular-services');
    Route::get('/active-specialists', [AdminDashboardAnalyticsController::class, 'getActiveSpecialists'])->name('active-specialists');
    Route::get('/period/{period}', [AdminDashboardAnalyticsController::class, 'getDashboardByPeriod'])->name('period');
});
