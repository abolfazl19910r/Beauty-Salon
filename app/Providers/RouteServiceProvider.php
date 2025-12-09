<?php

namespace App\Providers;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     *
     * @var string
     */
    public const USER_HOME = '/dashboard';

    public const SPECIALIST_HOME = '/my-dashboard';

    /**
     *
     * @var array
     */
    protected $routes = [
        'web' => [
            'web/public.php',
            'web/auth.php',
            'web/profiles.php',
            'web/services.php',
            'web/bookings.php',
            'web/payments.php',
            'web/security.php',
        ],
        'admin' => [
            'admin/dashboard.php',
            'admin/services.php',
            'admin/specialists.php',
            'admin/bookings.php',
            'admin/reports.php',
            'admin/schedule.php',
            'admin/security.php',
        ],
        'api' => [
            'api/public/services.php',
            'api/public/specialists.php',
            'api/auth/security.php',
            'api/user/bookings.php',
            'api/user/payments.php',
            'api/user/loyalty.php',
            'api/admin/dashboard.php',
            'api/admin/reports.php',
            'api/admin/services.php',
            'api/admin/specialists.php',
        ]
    ];

    public function boot(): void
    {
        $this->configureModelBindings();

        $this->configureRateLimiting();

        $this->configureMiddlewareGroups();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin/reports.php'));
        });
    }

    protected function configureModelBindings(): void
    {
        Route::bind('specialist', function ($value) {
            return Specialist::findOrFail($value);
        });

        Route::bind('service', function ($value) {
            return BeautyService::findOrFail($value);
        });

        Route::bind('booking', function ($value) {
            return Booking::findOrFail($value);
        });

        Route::bind('user', function ($value) {
            return User::findOrFail($value);
        });

        Route::pattern('id', '[0-9a-f-]+');

        Route::pattern('slug', '[a-z0-9-]+');
        Route::pattern('year', '[0-9]{4}');
        Route::pattern('month', '[0-9]{1,2}');
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });
    }

    protected function configureMiddlewareGroups(): void
    {
        Route::middlewareGroup('authenticated', ['auth', 'verified']);

        Route::middlewareGroup('admin-access', ['auth', 'admin']);

        Route::middlewareGroup('enhanced-security', ['auth', 'verified', '2fa']);
    }

    public static function getHomeForUser(User $user): string
    {
        if ($user->hasRole('specialists')) {
            return static::SPECIALIST_HOME;
        }

        if ($user->is_admin) {
            return static::HOME;
        }
        return static::USER_HOME;
    }

    /**
     *
     * @param string $path
     * @return void
     */
    public static function loadRouteFile(string $path): void
    {
        require base_path('routes/' . $path);
    }
}
