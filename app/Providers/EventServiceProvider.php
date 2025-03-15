<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Observers\BookingObserver;
use App\Observers\DiscountCodeObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    public function boot(): void
    {
        Booking::observe(BookingObserver::class);
        DiscountCode::observe(DiscountCodeObserver::class);
    }
}
