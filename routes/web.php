<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/web/public.php';

require __DIR__.'/web/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/web/profiles.php';

    require __DIR__.'/web/services.php';
    require __DIR__.'/web/bookings.php';
    require __DIR__.'/web/gallery.php';
    require __DIR__.'/web/payments.php';

    require __DIR__.'/web/security.php';
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:access_admin_panel'])->group(function () {

    Route::get('/', [App\Http\Controllers\Admin\AdminDashboardController::class, 'dashboard'])->name('home');

        require __DIR__.'/admin/dashboard.php';
        require __DIR__.'/admin/profile.php';

        require __DIR__.'/admin/services.php';
        require __DIR__.'/admin/specialists.php';
        require __DIR__.'/admin/users.php';

        require __DIR__.'/admin/search.php';

        require __DIR__.'/admin/bookings.php';
        require __DIR__.'/admin/payments.php';
        require __DIR__.'/admin/categories.php';
        require __DIR__.'/admin/schedule.php';
        require __DIR__.'/admin/gallery.php';
        require __DIR__.'/admin/loyalty.php';
        require __DIR__.'/admin/blog.php';
        require __DIR__.'/admin/announcements.php';

        require __DIR__.'/admin/notifications.php';

        require __DIR__.'/admin/reports.php';
        require __DIR__.'/admin/security.php';
        require __DIR__.'/admin/roles.php';
        require __DIR__.'/admin/permissions.php';
    });
