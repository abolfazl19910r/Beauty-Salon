<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminSearchController;

Route::get('/search', [AdminSearchController::class, 'index'])->name('search.index');

Route::get('/search/api', [AdminSearchController::class, 'apiSearch'])->name('search.api');
