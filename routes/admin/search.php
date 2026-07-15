<?php

use App\Http\Controllers\Admin\Search\AdminSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [AdminSearchController::class, 'index'])->name('search.index');

Route::get('/search/api', [AdminSearchController::class, 'apiSearch'])->name('search.api');
