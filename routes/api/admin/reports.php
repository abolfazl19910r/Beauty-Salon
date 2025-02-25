<?php

use App\Http\Controllers\Admin\AdminReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function () {
    Route::get('/monthly-revenue', [AdminReportsController::class, 'monthlyRevenue']);
    Route::get('/specialist-performance', [AdminReportsController::class, 'specialistPerformance']);
    Route::get('/customer-satisfaction', [AdminReportsController::class, 'customerSatisfaction']);
    Route::get('/financial', [AdminReportsController::class, 'financialReport']);
});

Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    Route::get('/revenue', [AdminReportsController::class, 'getFinancialSummary']);
    Route::get('/daily', [AdminReportsController::class, 'dailyRevenue']);
    Route::get('/weekly', [AdminReportsController::class, 'weeklyRevenue']);
    Route::get('/monthly', [AdminReportsController::class, 'monthlyRevenue']);
    Route::get('/specialists', [AdminReportsController::class, 'specialistPerformance']);
    Route::get('/customers', [AdminReportsController::class, 'customerSatisfaction']);
    Route::get('/services', [AdminReportsController::class, 'popularServices']);
    Route::post('/export', [AdminReportsController::class, 'exportReport']);
});
