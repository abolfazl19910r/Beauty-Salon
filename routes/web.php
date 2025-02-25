<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/web/public.php';

require __DIR__.'/web/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/web/profiles.php';

    require __DIR__.'/web/services.php';
    require __DIR__.'/web/bookings.php';
    require __DIR__.'/web/payments.php';

    require __DIR__.'/web/security.php';
});

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        require __DIR__.'/admin/dashboard.php';

        require __DIR__.'/admin/services.php';
        require __DIR__.'/admin/specialists.php';

        require __DIR__.'/admin/bookings.php';
        require __DIR__.'/admin/schedule.php';

        require __DIR__.'/admin/reports.php';
        require __DIR__.'/admin/security.php';
    });
