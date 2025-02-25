<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [AdminReportsController::class, 'index'])->name('index');

    Route::get('/revenue', [AdminReportsController::class, 'revenueData'])->name('revenue');

    Route::get('/daily', [AdminReportsController::class, 'dailyRevenue'])->name('daily');
    Route::get('/weekly', [AdminReportsController::class, 'weeklyRevenue'])->name('weekly');
    Route::get('/monthly', [AdminReportsController::class, 'monthlyRevenue'])->name('monthly');

    Route::get('/specialists', [AdminReportsController::class, 'specialistPerformance'])->name('specialists');
    Route::get('/specialist-performance', [AdminReportsController::class, 'specialistPerformanceReport'])->name('specialist-performance');
    Route::get('/customer-satisfaction', [AdminReportsController::class, 'customerSatisfaction'])->name('satisfaction');
    Route::get('/financial', [AdminReportsController::class, 'financialReport'])->name('financial');

    Route::get('/popular-services', [AdminDashboardController::class, 'getPopularServices']);
    Route::get('/active-specialists', [AdminDashboardController::class, 'getActiveSpecialists']);

    Route::get('/export', [AdminReportsController::class, 'exportReport'])->name('export');
});
