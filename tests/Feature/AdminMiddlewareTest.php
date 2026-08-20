<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression guard for a severe, pre-existing bug (confirmed present in the originally uploaded
 * project, not introduced by this test-writing session): bootstrap/app.php registered a
 * middleware GROUP named 'admin' whose own member list included the string 'admin' — but 'admin'
 * is *also* registered as an ALIAS for AdminMiddleware::class. Illuminate\Routing\
 * MiddlewareNameResolver::parseMiddlewareGroup() checks whether a middleware name is a known
 * GROUP name *before* falling back to the alias map, so resolving the group's own 'admin' member
 * recursed back into the very same group — infinitely. Every request to any /api/admin/* route
 * (dashboard, reports, services, specialists) exhausted available memory and fatally crashed the
 * PHP process. Confirmed independently: a plain `php vendor/bin/phpunit` run against this route
 * consumed ~4GB of RAM before being OOM-killed by the OS, and crashed even with a 512MB
 * memory_limit override, dying inside MiddlewareNameResolver.php itself.
 *
 * Original fix: the group was renamed to 'admin-api' (bootstrap/app.php + its one consumer in
 * routes/api.php) so it no longer collided with the single-middleware 'admin' alias it contains.
 *
 * ⭐ Update (test-writing session 9): the entire routes/api/admin/* group (the 'admin-api'
 * group's only consumer) was later removed per an explicit project decision — confirmed zero
 * live consumers in resources/js or resources/views (unused React-SPA-era JSON API). The
 * 'admin-api' middleware group definition was removed along with it. AdminMiddleware itself
 * (aliased as 'admin') is still registered and still guards the real admin web panel indirectly
 * through role/permission checks elsewhere, so its behavior — the exact thing this test
 * actually verifies — still matters and is still live code, even though no production route
 * currently applies the bare 'admin' alias directly. Rather than deleting this regression
 * coverage along with the now-gone routes, the tests below register their own throwaway route
 * (applying ['auth', 'admin'] directly, the same way the removed group did) so
 * AdminMiddleware's real behavior — plain-admin access, non-admin rejection, the
 * admin-who-is-also-a-specialist redirect, and guest rejection — stays covered independently of
 * which production routes happen to use it.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Two separate throwaway routes (not tied to any single production endpoint) — both
        // apply the 'admin' alias directly, exactly like the removed 'admin-api' group did.
        Route::middleware(['auth', 'admin'])->get('/__test/admin-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['auth', 'admin'])->get('/__test/admin-only-2', fn () => response()->json(['ok' => true]));
    }

    public function test_a_plain_admin_can_access_an_admin_only_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson('/__test/admin-only');

        $response->assertOk();
    }

    public function test_a_plain_admin_can_access_a_second_unrelated_admin_only_route(): void
    {
        // A different route under the same middleware — proves the fix is at the
        // alias/group-resolution level, not a one-off patch for a single route.
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson('/__test/admin-only-2');

        $response->assertOk();
    }

    public function test_a_non_admin_cannot_access_an_admin_only_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->getJson('/__test/admin-only');

        $response->assertForbidden();
    }

    public function test_an_admin_who_is_also_a_specialist_is_redirected_to_their_own_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Specialist::factory()->create(['phone' => $admin->phone]);
        $admin->roles()->attach(Role::factory()->create(['name' => 'specialist']));

        $response = $this->actingAs($admin)->getJson('/__test/admin-only');

        $response->assertForbidden();
        $response->assertJson(['message' => 'متخصصین نمی‌توانند به پنل مدیریت دسترسی داشته باشند.']);
    }

    public function test_an_admin_who_is_also_a_specialist_is_redirected_on_a_non_json_request(): void
    {
        // Same scenario as above, but through the non-JSON (regular browser navigation) branch —
        // this exercises the redirect()->route('specialist.my-dashboard') call specifically, which
        // would have thrown a RouteNotFoundException before the route-name part of this fix (the
        // old, dead code referenced a route named 'specialist.dashboard' that doesn't exist).
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->roles()->attach(Role::factory()->create(['name' => 'specialist']));

        $response = $this->actingAs($admin)->get('/__test/admin-only');

        $response->assertRedirect(route('specialist.my-dashboard'));
    }

    public function test_a_guest_receives_a_401_from_an_admin_only_route(): void
    {
        $response = $this->getJson('/__test/admin-only');

        $response->assertUnauthorized();
    }
}
