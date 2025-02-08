<?php

use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\SecurePaymentController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Public Routes
    Route::get('/services', [ServiceController::class, 'list']);
    Route::get('/specialists/{service}', [ServiceController::class, 'specialists']);
    Route::get('/specialists/{specialist}/available-dates', [BookingController::class, 'availableDates']);
    Route::get('/specialists/{specialist}/time-slots/{date}', [BookingController::class, 'timeSlots']);
    Route::get('/available-dates/{specialist}', [BookingController::class, 'getAvailableDates']);
    Route::get('/time-slots/{specialist}/{date}', [BookingController::class, 'getAvailableTimeSlots']);

    // Auth Required Routes
    Route::middleware('auth')->group(function () {
        // Booking Routes
        Route::prefix('bookings')->group(function () {
            Route::get('/user', [BookingController::class, 'getUserBookings']);
            Route::get('/upcoming', [BookingController::class, 'getUpcomingBookings']);
            Route::get('/past', [BookingController::class, 'getPastBookings']);
            Route::get('/{booking}', [BookingController::class, 'show']);
            Route::post('/', [BookingController::class, 'store']);
            Route::post('/{booking}/reschedule', [BookingController::class, 'reschedule']);
            Route::post('/{booking}/cancel', [BookingController::class, 'cancel']);
            Route::post('/{booking}/rate', [BookingController::class, 'rate']);
            Route::post('/{booking}/apply-discount', [BookingController::class, 'applyDiscount']);
        });

        // Specialist Routes
        Route::prefix('specialists')->group(function () {
            Route::get('/{specialist}/next-available', [BookingController::class, 'getNextAvailableSlots']);
            Route::get('/{specialist}/availability/{year_month}', [BookingController::class, 'getMonthlyAvailability']);
            Route::post('/{specialist}/validate-slot', [BookingController::class, 'validateTimeSlot']);
            Route::get('/{specialist}/schedule/check', [WorkScheduleController::class, 'checkAvailability']);
            Route::get('/{specialist}/schedule/slots', [WorkScheduleController::class, 'getAvailableSlots']);
            Route::get('/{specialist}/holidays/check', [HolidayController::class, 'checkDate']);
        });

        // Two Factor Authentication Routes
        Route::prefix('auth')->group(function () {
            Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
            Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
            Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);
            Route::post('/2fa/resend', [TwoFactorController::class, 'resend']);
        });

        // Security Routes
        Route::prefix('security')->group(function () {
            Route::get('/sessions/active', [SecurityController::class, 'getActiveSessions']);
            Route::post('/sessions/{id}/terminate', [SecurityController::class, 'terminateSession']);
            Route::post('/sessions/terminate-all', [SecurityController::class, 'terminateAllSessions']);
            Route::get('/logs', [SecurityController::class, 'getSecurityLogs']);
            Route::get('/login-history', [SecurityController::class, 'getLoginHistory']);
            Route::get('/status', [SecurityController::class, 'getSecurityStatus']);
            Route::post('/password/check', [SecurityController::class, 'checkPasswordStrength']);
        });

        // Secure Payment Routes
        Route::prefix('payments/secure')->middleware('verified.2fa')->group(function () {
            Route::post('/initiate', [SecurePaymentController::class, 'initiate']);
            Route::post('/verify', [SecurePaymentController::class, 'verify']);
            Route::get('/{reference}/status', [SecurePaymentController::class, 'checkStatus']);
        });

        // Loyalty System Routes
        Route::prefix('loyalty')->group(function () {
            Route::get('/overview', [LoyaltyController::class, 'overview']);
            Route::get('/points', [LoyaltyController::class, 'getPoints']);
            Route::get('/points/history', [LoyaltyController::class, 'history']);
            Route::get('/rewards', [LoyaltyController::class, 'getRewards']);
            Route::post('/rewards/{reward}/redeem', [LoyaltyController::class, 'redeemReward']);
            Route::get('/discount-codes', [LoyaltyController::class, 'discountCodes']);
        });

        // Announcements Routes
        Route::prefix('announcements')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [AnnouncementController::class, 'store']);
                Route::put('/{announcement}', [AnnouncementController::class, 'update']);
                Route::delete('/{announcement}', [AnnouncementController::class, 'destroy']);
            });
        });

        // Blog Routes
        Route::prefix('blog')->group(function () {
            Route::get('/posts', [BlogController::class, 'index']);
            Route::get('/categories', [BlogController::class, 'getCategories']);
            Route::get('/posts/{post}', [BlogController::class, 'show']);
            Route::middleware('admin')->group(function () {
                Route::post('/posts', [BlogController::class, 'store']);
                Route::put('/posts/{post}', [BlogController::class, 'update']);
                Route::delete('/posts/{post}', [BlogController::class, 'destroy']);
            });
        });

        // Gallery Routes
        Route::prefix('gallery')->group(function () {
            Route::get('/', [GalleryController::class, 'index']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [GalleryController::class, 'store']);
                Route::post('/reorder', [GalleryController::class, 'reorder']);
                Route::put('/{image}', [GalleryController::class, 'update']);
                Route::delete('/{image}', [GalleryController::class, 'destroy']);
            });
        });

        // Admin Routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'getData']);

            // Reports Routes
            Route::prefix('reports')->group(function () {
                Route::get('/monthly-revenue', [ReportsController::class, 'monthlyRevenue']);
                Route::get('/specialist-performance', [ReportsController::class, 'specialistPerformance']);
                Route::get('/customer-satisfaction', [ReportsController::class, 'customerSatisfaction']);
                Route::get('/financial', [ReportsController::class, 'financialReport']);
            });
        });
    });
});
