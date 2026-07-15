<?php

use App\Http\Controllers\User\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/services', [ServiceController::class, 'list'])->name('services.list');
