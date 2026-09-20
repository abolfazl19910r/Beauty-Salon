<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * ⭐ Fix (real, confirmed cross-tenant data leak, session 2026-09-20): ServiceController::list()
 * used to be reachable at the unscoped /api/services (routes/api/public/services.php, now
 * deleted), entirely outside /s/{salon_slug} — so CurrentSalon was never bound,
 * BeautyService::all() ignored its own BelongsToSalon scope, and EVERY salon's services came
 * back mixed together in one response. On top of that, the 30-minute Cache::remember() key was
 * the literal global string 'all_beauty_services' — so even a query-level fix alone would have
 * let the first salon to hit the route populate a cache that every OTHER salon's booking page
 * then silently inherited for the next 30 minutes (the same bug HomeController's
 * home_services/home_specialists keys had before being fixed). Both are covered here: the route
 * moved to the salon-scoped bookings.services-list (routes/web/bookings.php), and the cache key
 * is now suffixed with the salon id.
 */
class CrossSalonServiceLeakTest extends TestCase
{
    use RefreshDatabase;

    private function createInOtherSalon(\Closure $factory): array
    {
        $otherSalon = Salon::factory()->create();
        app(CurrentSalon::class)->set($otherSalon);
        $record = $factory();
        app(CurrentSalon::class)->clear();

        return [$record, $otherSalon];
    }

    public function test_services_list_only_returns_the_current_salons_services(): void
    {
        $user = User::factory()->create(); // customer of the default test salon
        $ownService = BeautyService::factory()->create(['name' => 'OWN_SALON_SERVICE']);

        [$otherService] = $this->createInOtherSalon(
            fn () => BeautyService::factory()->create(['name' => 'OTHER_SALON_SERVICE'])
        );

        $response = $this->actingAs($user)->getJson(route('bookings.services-list'));

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();

        $this->assertContains('OWN_SALON_SERVICE', $names);
        $this->assertNotContains('OTHER_SALON_SERVICE', $names);
    }

    public function test_services_list_cache_is_scoped_per_salon_not_shared_globally(): void
    {
        $userA = User::factory()->create();
        BeautyService::factory()->create(['name' => 'SALON_A_SERVICE']);

        // Populate the cache for the default (A) salon first.
        $this->actingAs($userA)->getJson(route('bookings.services-list'))->assertOk();

        // A second salon, with its own customer, hitting the same named route.
        [$serviceB, $salonB] = $this->createInOtherSalon(function () {
            return BeautyService::factory()->create(['name' => 'SALON_B_SERVICE']);
        });
        $userB = User::factory()->create(['salon_id' => $salonB->id]);

        app(CurrentSalon::class)->set($salonB);
        URL::defaults(['salon_slug' => $salonB->slug]);

        $response = $this->actingAs($userB)->getJson(route('bookings.services-list'));
        $names = collect($response->json())->pluck('name')->all();

        $this->assertContains('SALON_B_SERVICE', $names, 'Salon B must see its own service, not salon A\'s cached list');
        $this->assertNotContains('SALON_A_SERVICE', $names);
    }

    public function test_old_unscoped_api_services_route_no_longer_exists(): void
    {
        $this->getJson('/api/services')->assertNotFound();
    }
}
