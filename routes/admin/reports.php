<?php

use App\Http\Controllers\Admin\AdminReportExportController;
use App\Http\Controllers\Admin\AdminReportRevenueController;
use App\Http\Controllers\Admin\AdminReportsController;
use Illuminate\Support\Facades\Route;


Route::prefix('reports')->name('reports.')->group(function () {

    // ── Reports Home Page ─────────────────────────────────────
    Route::get('/', [AdminReportsController::class, 'index'])->name('index');

    // ── Export ──────────────────────────────────────────
    Route::get('/export', [AdminReportExportController::class, 'export'])->name('export');

    // ── Chart endpoints for Admin Dashboard ────────────────────────
    // dashboard.blade.php calls these with fetch('/admin/reports/today|week|month')
    // are web routes → session auth works (without auth:sanctum)
    Route::get('/today', [AdminReportRevenueController::class, 'today'])->name('today');
    Route::get('/week',  [AdminReportRevenueController::class, 'week'])->name('week');
    Route::get('/month', [AdminReportRevenueController::class, 'month'])->name('month');
});
