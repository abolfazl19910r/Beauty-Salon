<?php

use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 4). Required from
 * routes/web.php inside a group that already applies prefix('superadmin')->name('superadmin.')
 * ->middleware(['auth', 'super_admin']) — this file follows the same convention as
 * routes/admin/*.php (e.g. dashboard.php): no re-declared prefix/name/middleware in here, since
 * the wrapping group already supplies all of it. See EnsureSuperAdmin's docblock for why
 * 'super_admin' here means hasRole('super-admin'), not hasPermission('super_admin').
 *
 * SuperAdminController doesn't exist yet (commit 5) — safe to require this file now anyway,
 * since PHP only resolves the controller class when a request actually dispatches to one of
 * these routes, not at route-registration time.
 */
Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

Route::get('/salons', [SuperAdminController::class, 'index'])->name('salons.index');
Route::get('/salons/create', [SuperAdminController::class, 'create'])->name('salons.create');
Route::post('/salons', [SuperAdminController::class, 'store'])->name('salons.store');
Route::get('/salons/{salon}/edit', [SuperAdminController::class, 'edit'])->name('salons.edit');
Route::put('/salons/{salon}', [SuperAdminController::class, 'update'])->name('salons.update');
Route::post('/salons/{salon}/renew', [SuperAdminController::class, 'renewSubscription'])->name('salons.renew');
Route::post('/salons/{salon}/toggle-suspend', [SuperAdminController::class, 'toggleSuspend'])->name('salons.toggle-suspend');
