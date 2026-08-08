<?php

use App\Http\Controllers\Admin\Report\AdminReportRevenueController;
use App\Http\Controllers\Admin\Report\AdminReportSpecialistController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.api.')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::get('/daily', [AdminReportRevenueController::class, 'daily'])->name('daily');
    Route::get('/weekly', [AdminReportRevenueController::class, 'weekly'])->name('weekly');
    Route::get('/monthly', [AdminReportRevenueController::class, 'monthly'])->name('monthly');
    Route::get('/financial', [AdminReportRevenueController::class, 'financial'])->name('financial');

    Route::get('/specialists/performance', [AdminReportSpecialistController::class, 'performance'])->name('performance');
    Route::get('/specialists/satisfaction', [AdminReportSpecialistController::class, 'satisfaction'])->name('satisfaction');
    Route::get('/services/popular', [AdminReportSpecialistController::class, 'popularServices'])->name('popular-services');
});
