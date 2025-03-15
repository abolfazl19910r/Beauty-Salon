<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Observers\BookingObserver;
use App\Observers\DiscountCodeObserver;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\SMSService;
use Illuminate\Support\ServiceProvider;

class BookingServiceProvider extends ServiceProvider
{
    /**
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(BookingService::class, function ($app) {
            return new BookingService(
                $app->make(Specialist::class),
                $app->make(Booking::class),
                $app->make(DiscountCode::class)
            );
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            $config = $app['config']['payment'];
            $gateway = $config['default'];
            $gatewayConfig = $config['gateways'][$gateway] ?? [];

            return new PaymentService($gateway, $gatewayConfig);
        });

        $this->app->singleton(SMSService::class, function ($app) {
            $config = $app['config']['sms'];
            $provider = $config['default'];
            $providerConfig = $config['providers'][$provider] ?? [];

            return new SMSService($provider, $providerConfig);
        });
    }

    /**
     *
     * @return void
     */
    public function boot(): void
    {
        view()->composer('bookings.*', function ($view) {
            $user = auth()->user();
            if ($user) {
                $upcomingCount = Booking::where('user_id', $user->id)
                    ->where('booking_time', '>', now())
                    ->whereNotIn('status', ['cancelled'])
                    ->count();

                $view->with('upcomingBookingsCount', $upcomingCount);
            }
        });

        $this->registerPolicies();

        $this->registerObservers();
    }

    /**
     *
     * @return void
     */
    protected function registerPolicies(): void
    {
        $this->app['gate']->define('view-booking', function ($user, $booking) {
            return $user->id === $booking->user_id;
        });

        $this->app['gate']->define('update-booking', function ($user, $booking) {
            return $user->id === $booking->user_id &&
                $booking->status !== 'cancelled' &&
                $booking->booking_time->diffInHours(now()) > 24;
        });

        $this->app['gate']->define('cancel-booking', function ($user, $booking) {
            return $user->id === $booking->user_id &&
                $booking->status !== 'cancelled' &&
                $booking->booking_time->diffInHours(now()) > 24;
        });

        $this->app['gate']->define('rate-booking', function ($user, $booking) {
            return $user->id === $booking->user_id &&
                $booking->status === 'confirmed' &&
                $booking->booking_time->isPast() &&
                !$booking->rating;
        });
    }

    /**
     *
     * @return void
     */
    protected function registerObservers(): void
    {
        Booking::observe(BookingObserver::class);

        DiscountCode::observe(DiscountCodeObserver::class);
    }
}
