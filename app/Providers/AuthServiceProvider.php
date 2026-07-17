<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Leave;
use App\Models\Review;
use App\Models\Specialist;
use App\Models\SpecialistLeave;
use App\Models\SpecialistWallet;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use App\Policies\BookingPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SpecialistPolicy;
use App\Policies\SpecialistWalletPolicy;
use App\Policies\UserWalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Review::class => ReviewPolicy::class,
        UserWallet::class => UserWalletPolicy::class,
        UserWalletTransaction::class => UserWalletPolicy::class,
        Specialist::class => SpecialistPolicy::class,
// ⭐ Leave replaced SpecialistLeave. Old SpecialistLeave mapping
// Currently held (harmless, dead code) until the R-Cleanup-DeadCode phase
// when the complete SpecialistLeave model is removed.
        Leave::class => SpecialistPolicy::class,
        SpecialistLeave::class => SpecialistPolicy::class,
        SpecialistWallet::class => SpecialistWalletPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
