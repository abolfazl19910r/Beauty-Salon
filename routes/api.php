<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    require __DIR__.'/api/public/services.php';
    require __DIR__.'/api/public/specialists.php';

    if (file_exists(__DIR__.'/api/public/bookings.php')) {
        require __DIR__.'/api/public/bookings.php';
    }

    if (file_exists(__DIR__.'/api/public/gallery.php')) {
        require __DIR__.'/api/public/gallery.php';
    }
    if (file_exists(__DIR__.'/api/public/announcements.php')) {
        require __DIR__.'/api/public/announcements.php';
    }

    Route::middleware(app()->environment('production') ? 'auth:sanctum' : [])->group(function () {
        if (file_exists(__DIR__.'/api/auth/security.php')) {
            require __DIR__.'/api/auth/security.php';
        }

        if (file_exists(__DIR__.'/api/user/bookings.php')) {
            require __DIR__.'/api/user/bookings.php';
        }
        if (file_exists(__DIR__.'/api/user/payments.php')) {
            require __DIR__.'/api/user/payments.php';
        }
        if (file_exists(__DIR__.'/api/user/loyalty.php')) {
            require __DIR__.'/api/user/loyalty.php';
        }

        Route::middleware('admin')->prefix('admin')->group(function () {
            if (file_exists(__DIR__.'/api/admin/dashboard.php')) {
                require __DIR__.'/api/admin/dashboard.php';
            }
            if (file_exists(__DIR__.'/api/admin/reports.php')) {
                require __DIR__.'/api/admin/reports.php';
            }
            if (file_exists(__DIR__.'/api/admin/services.php')) {
                require __DIR__.'/api/admin/services.php';
            }
            if (file_exists(__DIR__.'/api/admin/specialists.php')) {
                require __DIR__.'/api/admin/specialists.php';
            }
            if (file_exists(__DIR__.'/api/admin/bookings.php')) {
                require __DIR__.'/api/admin/bookings.php';
            }
            if (file_exists(__DIR__.'/api/admin/loyalty.php')) {
                require __DIR__.'/api/admin/loyalty.php';
            }
        });
    });
});
