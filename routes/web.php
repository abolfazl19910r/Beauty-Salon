<?php

use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminSpecialistController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SecurePaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');

/*
|--------------------------------------------------------------------------
| Auth Required Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/update', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');

    Route::middleware('check.booking.ownership')->group(function () {
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::get('/bookings/{booking}/reschedule', [BookingController::class, 'showReschedule'])->name('bookings.reschedule');
        Route::post('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
        Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/rate', [BookingController::class, 'rate'])->name('bookings.rate');
        Route::post('/bookings/{booking}/apply-discount', [BookingController::class, 'applyDiscount'])->name('bookings.apply-discount');
    });

    Route::get('/payment/{booking}', [PaymentController::class, 'show'])
        ->name('payment.show')
        ->middleware('check.booking.ownership');

    Route::post('/payment/{booking}/process', [PaymentController::class, 'process'])
        ->name('payment.process')
        ->middleware('check.booking.ownership');

    Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::get('/', [LoyaltyController::class, 'index'])->name('index');
        Route::get('/points', [LoyaltyController::class, 'getPoints'])->name('points');
        Route::get('/history', [LoyaltyController::class, 'getHistory'])->name('history');
        Route::get('/rewards', [LoyaltyController::class, 'getRewards'])->name('rewards');
        Route::get('/progress', [LoyaltyController::class, 'getProgress'])->name('progress');
        Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward'])->name('redeem');
        Route::post('/points/earn', [LoyaltyController::class, 'earnPoints'])->name('earn');
    });

    Route::get('/announcements', [AnnouncementController::class, 'userIndex'])->name('announcements.index');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/api/dashboard', [AdminDashboardController::class, 'getData'])->name('dashboard.data');

        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

        Route::get('/services', [AdminServiceController::class, 'index'])->name('services.index');
        Route::get('/services/create', [AdminServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [AdminServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{id}/edit', [AdminServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{id}', [AdminServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{id}', [AdminServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/specialists', [AdminSpecialistController::class, 'index'])->name('specialists.index');
        Route::get('/specialists/create', [AdminSpecialistController::class, 'create'])->name('specialists.create');
        Route::post('/specialists', [AdminSpecialistController::class, 'store'])->name('specialists.store');
        Route::get('/specialists/{specialist}', [AdminSpecialistController::class, 'show'])->name('specialists.show');
        Route::get('/specialists/{specialist}/edit', [AdminSpecialistController::class, 'edit'])->name('specialists.edit');
        Route::put('/specialists/{specialist}', [AdminSpecialistController::class, 'update'])->name('specialists.update');
        Route::delete('/specialists/{id}', [AdminSpecialistController::class, 'destroy'])->name('specialists.destroy');

        Route::get('/specialists/{specialist}/schedule', [AdminSpecialistController::class, 'schedule'])->name('specialists.schedule');
        Route::post('/specialists/{specialist}/toggle-status', [AdminSpecialistController::class, 'toggleStatus'])->name('specialists.toggle-status');
        Route::get('/specialists/{specialist}/bookings', [AdminSpecialistController::class, 'bookings'])->name('specialists.bookings');
        Route::get('/specialists/{specialist}/reviews', [AdminSpecialistController::class, 'reviews'])->name('specialists.reviews');

        Route::resource('rewards', RewardController::class);
        Route::get('/loyalty/statistics', [LoyaltyController::class, 'statistics'])->name('loyalty.statistics');

        Route::resource('announcements', AnnouncementController::class);
        Route::resource('blog/categories', BlogCategoryController::class);
        Route::resource('blog/posts', BlogController::class);
        Route::post('blog/posts/{post}/publish', [BlogController::class, 'publish'])->name('blog.posts.publish');
        Route::post('blog/posts/{post}/unpublish', [BlogController::class, 'unpublish'])->name('blog.posts.unpublish');

        Route::get('gallery', [GalleryController::class, 'adminIndex'])->name('gallery.index');
        Route::post('gallery', [GalleryController::class, 'store'])->name('gallery.store');
        Route::put('gallery/{image}', [GalleryController::class, 'update'])->name('gallery.update');
        Route::delete('gallery/{image}', [GalleryController::class, 'destroy'])->name('gallery.destroy');
        Route::post('gallery/reorder', [GalleryController::class, 'reorder'])->name('gallery.reorder');

        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportsController::class, 'index'])->name('reports');
            Route::get('/monthly-revenue', [ReportsController::class, 'monthlyRevenue'])->name('reports.monthly-revenue');
            Route::get('/specialist-performance', [ReportsController::class, 'specialistPerformance'])->name('reports.specialist-performance');
            Route::get('/customer-satisfaction', [ReportsController::class, 'customerSatisfaction'])->name('reports.customer-satisfaction');
            Route::get('/financial', [ReportsController::class, 'financialReport'])->name('reports.financial');
            Route::get('/loyalty', [ReportsController::class, 'loyaltyReport'])->name('reports.loyalty');
            Route::get('/blog', [ReportsController::class, 'blogReport'])->name('reports.blog');
            Route::get('/gallery', [ReportsController::class, 'galleryReport'])->name('reports.gallery');
        });

        Route::prefix('specialists/{specialist}')->group(function () {
            Route::get('/schedule', [WorkScheduleController::class, 'index'])->name('schedule.index');
            Route::post('/schedule', [WorkScheduleController::class, 'store'])->name('schedule.store');
            Route::get('/schedule/check', [WorkScheduleController::class, 'checkAvailability'])->name('schedule.check');
            Route::get('/schedule/slots', [WorkScheduleController::class, 'getAvailableSlots'])->name('schedule.slots');
            Route::put('/schedule/{schedule}', [WorkScheduleController::class, 'update'])->name('schedule.update');
            Route::delete('/schedule/{schedule}', [WorkScheduleController::class, 'destroy'])->name('schedule.destroy');

            Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
            Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
            Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
            Route::get('/holidays/upcoming', [HolidayController::class, 'upcomingHolidays'])->name('holidays.upcoming');
            Route::post('/holidays/check', [HolidayController::class, 'checkDate'])->name('holidays.check');

            Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
            Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
            Route::put('/leaves/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
            Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
        });

        Route::get('/leaves/pending', [LeaveController::class, 'pendingLeaves'])->name('leaves.pending');
    });

/*
|--------------------------------------------------------------------------
| Admin Reports Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/reports')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/financial', [ReportsController::class, 'financialReport']);
    Route::get('/daily', [ReportsController::class, 'dailyRevenue'])->name('admin.reports.daily');
    Route::get('/weekly', [ReportsController::class, 'weeklyRevenue'])->name('admin.reports.weekly');
    Route::get('/monthly', [ReportsController::class, 'monthlyRevenue'])->name('admin.reports.monthly');
    Route::get('/specialists', [ReportsController::class, 'specialistPerformance'])->name('admin.reports.specialists');
    Route::get('/customer-satisfaction', [ReportsController::class, 'customerSatisfaction'])->name('admin.reports.satisfaction');
    Route::get('/popular-services', [ReportsController::class, 'popularServices'])->name('admin.reports.services');
    Route::get('/export', [ReportsController::class, 'exportReport'])->name('admin.reports.export');
    Route::get('/services', [ReportsController::class, 'servicesReport'])
        ->name('admin.reports.services');
});

/*
|--------------------------------------------------------------------------
| Security Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/security/2fa', [TwoFactorController::class, 'show'])->name('security.2fa');
    Route::get('/security/2fa/setup', [TwoFactorController::class, 'showSetup'])->name('security.2fa.setup');
    Route::get('/security/2fa/confirm', [TwoFactorController::class, 'showConfirmation'])->name('security.2fa.confirm');

    Route::get('/security/dashboard', [SecurityController::class, 'dashboard'])->name('security.dashboard');
    Route::get('/security/sessions', [SecurityController::class, 'sessions'])->name('security.sessions');
    Route::get('/security/activity', [SecurityController::class, 'activity'])->name('security.activity');

    Route::middleware(['2fa.enabled'])->group(function () {
        Route::get('/payments/secure/checkout/{booking}', [SecurePaymentController::class, 'showCheckout'])->name('payments.secure.checkout');
        Route::get('/payments/secure/verify/{reference}', [SecurePaymentController::class, 'showVerification'])->name('payments.secure.verify');
        Route::get('/payments/secure/result/{reference}', [SecurePaymentController::class, 'showResult'])->name('payments.secure.result');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Security Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/security/logs', [SecurityController::class, 'adminLogs'])->name('admin.security.logs');
    Route::get('/security/users', [SecurityController::class, 'adminUsers'])->name('admin.security.users');
    Route::get('/security/settings', [SecurityController::class, 'adminSettings'])->name('admin.security.settings');
    Route::post('/security/settings', [SecurityController::class, 'updateSettings'])->name('admin.security.settings.update');
});

require __DIR__.'/auth.php';
