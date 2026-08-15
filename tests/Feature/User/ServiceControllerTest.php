<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Only covers the routes actually backed by real, reachable ServiceController methods
 * (index, show, list, specialists). routes/web/services.php references roughly a dozen
 * additional methods on this controller (search, filter, byCategory, popular, newest,
 * discounted, compare, favorites/addToFavorites/removeFromFavorites, history, similar,
 * addReview, getReviews) that do not exist on the class at all — an entire half-built
 * feature set (service search/filter/compare, favorites, service-level reviews) that was
 * never implemented. Confirmed via full-project grep that nothing in resources/views or
 * resources/js links to any of these routes, so nothing currently relies on them.
 *
 * Two failure modes coexist here, both confirmed with direct HTTP calls:
 * - Everything under /services/{something} (search, filter, popular, newest, discounted,
 *   compare, history) is additionally shadowed by the earlier, more generic
 *   `/services/{service}` route (registered in routes/web/public.php, loaded before
 *   routes/web/services.php) — Laravel's implicit route-model-binding on {service} tries
 *   to resolve e.g. "search" as a BeautyService, fails, and returns a plain 404 before the
 *   route-shadowing issue itself would even matter.
 * - /favorites and /services/{id}/review are NOT shadowed by that pattern (different path
 *   shape) and instead genuinely fatal with "Call to undefined method" (500), since
 *   ServiceController::favorites()/addToFavorites()/addReview()/etc. simply don't exist.
 *
 * This is a large, undocumented gap — not something to silently build out during a
 * test-writing pass. Flagging it here as a major finding (candidate for either implementing
 * the feature set or removing the dead routes in routes/web/services.php), not fixing it.
 */
class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_services_with_category_and_supports_pagination(): void
    {
        BeautyService::factory()->count(15)->create();

        $response = $this->get('/services');

        $response->assertOk();
        $this->assertCount(12, $response->viewData('services'));
    }

    public function test_index_filters_by_category_id_from_the_query_string(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        BeautyService::factory()->create(['category_id' => $categoryA->id]);
        BeautyService::factory()->create(['category_id' => $categoryB->id]);

        $response = $this->get('/services?category='.$categoryA->id);

        $this->assertCount(1, $response->viewData('services'));
    }

    public function test_show_displays_a_service_with_specialists_and_related_services(): void
    {
        $category = Category::factory()->create();
        $service = BeautyService::factory()->create(['category_id' => $category->id]);
        $related = BeautyService::factory()->create(['category_id' => $category->id]);
        $unrelated = BeautyService::factory()->create();

        $response = $this->get("/services/{$service->id}");

        $response->assertOk();
        $relatedIds = $response->viewData('relatedServices')->pluck('id');
        $this->assertTrue($relatedIds->contains($related->id));
        $this->assertFalse($relatedIds->contains($unrelated->id));
        $this->assertFalse($relatedIds->contains($service->id));
    }

    public function test_api_list_computes_prepayment_amount_from_current_admin_settings(): void
    {
        BeautyService::factory()->create(['price' => 500000]);
        WalletSetting::query()->delete();
        WalletSetting::create(['prepayment_percentage' => 40, 'minimum_prepayment_amount' => 50000]);

        $response = $this->getJson('/api/services');

        $response->assertOk();
        $this->assertSame(200000, $response->json()[0]['prepayment_amount']); // 500000 * 40%
    }

    public function test_api_specialists_returns_specialists_attached_to_the_service(): void
    {
        // Regression guard: Route::bind('service', ...) in RouteServiceProvider globally
        // resolves any {service}-named route parameter into an already-loaded BeautyService
        // instance before the controller runs. BookingAvailabilityController::
        // getSpecialistsByService() used to call BeautyService::findOrFail($serviceId)
        // directly, assuming a raw id — since $serviceId was actually already a model object,
        // this always threw "No query results", even for a perfectly valid, existing service.
        // This exercises the actual route (web/bookings.php, which uses {service}) rather than
        // the sibling route registered with {serviceId} which was never affected.
        $user = User::factory()->create();
        $service = BeautyService::factory()->create();
        $specialist = \App\Models\Specialist::factory()->create();
        $service->specialists()->attach($specialist->id);

        $response = $this->actingAs($user)->getJson("/bookings/services/{$service->id}/specialists");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($specialist->id));
    }

    public function test_the_documented_dead_favorites_route_currently_fatals(): void
    {
        $user = User::factory()->create();

        // Regression/documentation guard: this asserts the CURRENT (broken) behavior so a
        // future fix to ServiceController::favorites() is a visible, deliberate change to
        // this test, not a silent behavior shift nobody notices.
        $response = $this->actingAs($user)->get('/favorites');

        $response->assertStatus(500);
    }

    public function test_the_documented_shadowed_search_route_currently_404s(): void
    {
        $user = User::factory()->create();

        // Regression/documentation guard for the route-shadowing issue described in this
        // class's docblock: /services/{service} (registered earlier) intercepts this request
        // before /services/search (registered later) is ever reached.
        $response = $this->actingAs($user)->get('/services/search');

        $response->assertStatus(404);
    }
}
