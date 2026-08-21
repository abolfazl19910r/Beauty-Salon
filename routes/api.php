<?php

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

    Route::middleware('auth:sanctum')->group(function () {
        if (file_exists(__DIR__.'/api/auth/security.php')) {
            require __DIR__.'/api/auth/security.php';
        }

        if (file_exists(__DIR__.'/api/user/bookings.php')) {
            require __DIR__.'/api/user/bookings.php';
        }
        if (file_exists(__DIR__.'/api/user/payments.php')) {
            require __DIR__.'/api/user/payments.php';
        }
    });
});
