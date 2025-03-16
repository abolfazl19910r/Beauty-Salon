<?php

use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {

    require __DIR__.'/api/public/services.php';
    require __DIR__.'/api/public/specialists.php';
    require __DIR__.'/api/public/gallery.php';
    require __DIR__.'/api/public/announcements.php';
    require __DIR__.'/api/public/blog.php';


    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/api/auth/security.php';

        require __DIR__.'/api/user/bookings.php';
        require __DIR__.'/api/user/payments.php';
        require __DIR__.'/api/user/loyalty.php';

        Route::middleware('admin')->prefix('admin')->group(function () {
            require __DIR__.'/api/admin/dashboard.php';
            require __DIR__.'/api/admin/reports.php';
            require __DIR__.'/api/admin/services.php';
            require __DIR__.'/api/admin/specialists.php';
            require __DIR__.'/api/admin/bookings.php';
        });
    });
});
