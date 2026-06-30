<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\UserWallet;
use App\Policies\BookingPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SpecialistPolicy;
use App\Policies\SpecialistWalletPolicy;
use App\Policies\UserWalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Specialist::class => SpecialistPolicy::class,
        Review::class => ReviewPolicy::class,
        UserWallet::class => UserWalletPolicy::class,
        SpecialistWallet::class => SpecialistWalletPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
