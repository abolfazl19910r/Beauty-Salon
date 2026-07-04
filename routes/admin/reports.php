<?php

use App\Http\Controllers\Admin\AdminReportExportController;
use App\Http\Controllers\Admin\AdminReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->middleware(['auth', 'admin'])->group(function () {

    Route::get('/', [AdminReportsController::class, 'index'])->name('index');

    Route::get('/export', [AdminReportExportController::class, 'export'])->name('export');
});
