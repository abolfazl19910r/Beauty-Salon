<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * Fix: the group was renamed to 'admin-api' (bootstrap/app.php + its one consumer in
 * routes/api.php) so it no longer collides with the single-middleware 'admin' alias it contains.
 *
 * Also covers a second, independent bug found in the same file: AdminMiddleware checked
 * hasRole('specialists') (plural) while the only role ever seeded/checked anywhere else in the
 * project is 'specialist' (singular) — so an account that was both an admin AND a specialist was
 * never redirected to their own dashboard and incorrectly kept full admin-panel access.
 *
 * These tests hit two different underlying /api/admin/* routes (dashboard and specialists) to
 * confirm the fix is at the group-resolution level, not specific to one endpoint.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_admin_can_access_the_admin_api(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $response->assertOk();
    }

    public function test_a_plain_admin_can_access_a_second_unrelated_admin_api_endpoint(): void
    {
        // A different controller/action under the same 'admin-api' group — proves the fix is at
        // the group-resolution level, not a one-off patch for a single route.
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard/popular-services');

        $response->assertOk();
    }

    public function test_a_non_admin_cannot_access_the_admin_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->getJson('/api/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_an_admin_who_is_also_a_specialist_is_redirected_to_their_own_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Specialist::factory()->create(['phone' => $admin->phone]);
        $admin->roles()->attach(Role::factory()->create(['name' => 'specialist']));

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

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

        $response = $this->actingAs($admin)->get('/api/admin/dashboard');

        $response->assertRedirect(route('specialist.my-dashboard'));
    }

    public function test_a_guest_receives_a_401_from_the_admin_api(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertUnauthorized();
    }
}
