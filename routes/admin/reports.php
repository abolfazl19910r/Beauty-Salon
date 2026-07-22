<?php

use App\Http\Controllers\Admin\Report\AdminReportExportController;
use App\Http\Controllers\Admin\Report\AdminReportRevenueController;
use App\Http\Controllers\Admin\Report\AdminReportsController;
use Illuminate\Support\Facades\Route;


Route::prefix('reports')->name('reports.')->group(function () {

    // ── Reports Home Page ─────────────────────────────────────
    Route::get('/', [AdminReportsController::class, 'index'])->name('index');

    // ── Export (async — just creates a record and queues the Job) ──
    Route::post('/export', [AdminReportExportController::class, 'export'])->name('export');
    Route::get('/exports', [AdminReportExportController::class, 'index'])->name('exports.index');
    Route::get('/exports/{reportExport}/download', [AdminReportExportController::class, 'download'])->name('exports.download');

    // ── Chart endpoints for Admin Dashboard ────────────────────────
    // dashboard.blade.php calls these with fetch('/admin/reports/today|week|month')
    // are web routes → session auth works (without auth:sanctum)
    Route::get('/today', [AdminReportRevenueController::class, 'today'])->name('today');
    Route::get('/week',  [AdminReportRevenueController::class, 'week'])->name('week');
    Route::get('/month', [AdminReportRevenueController::class, 'month'])->name('month');
});
