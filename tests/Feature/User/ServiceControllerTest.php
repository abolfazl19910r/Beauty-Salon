<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ServiceController: index, show, list, specialists.
 *
 * ⭐ (۲۰۲۶-۰۹-۲۷) ۱۵ مسیری که به متدهای ناموجود اشاره می‌کردند (۱۴ در routes/web/services.php، به‌علاوه‌ی
 * payment.failed) حذف شدند — هیچ صفحه یا JS به آن‌ها لینک نمی‌داد؛ test_the_removed_dead_routes_are_gone نگهبان
 * برگشت‌نکردنشان است.
 */
class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_services_with_category_and_supports_pagination(): void
    {
        BeautyService::factory()->count(15)->create();

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $this->assertCount(12, $response->viewData('services'));
    }

    public function test_index_filters_by_category_id_from_the_query_string(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        BeautyService::factory()->create(['category_id' => $categoryA->id]);
        BeautyService::factory()->create(['category_id' => $categoryB->id]);

        $response = $this->get(route('services.index', ['category' => $categoryA->id]));

        $this->assertCount(1, $response->viewData('services'));
    }

    public function test_show_displays_a_service_with_specialists_and_related_services(): void
    {
        $category = Category::factory()->create();
        $service = BeautyService::factory()->create(['category_id' => $category->id]);
        $related = BeautyService::factory()->create(['category_id' => $category->id]);
        $unrelated = BeautyService::factory()->create();

        $response = $this->get(route('services.show', ['service' => $service->id]));

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

        $user = User::factory()->create();

        // ⭐ Fix (real, confirmed cross-tenant data leak, 2026-09-20): this route moved from the
        // unscoped /api/services to the salon-scoped bookings.services-list (routes/web/
        // bookings.php) — see CrossSalonServiceLeakTest for the dedicated cross-salon coverage.
        $response = $this->actingAs($user)->getJson(route('bookings.services-list'));

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

        $response = $this->actingAs($user)->getJson(route('bookings.service-specialists', ['service' => $service->id]));

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($specialist->id));
    }

    public function test_the_removed_dead_routes_are_gone(): void
    {
        foreach (['favorites.index', 'favorites.add', 'favorites.remove', 'payment.failed', 'services.history',
            'services.by-category', 'services.compare', 'services.discounted', 'services.filter', 'services.newest',
            'services.popular', 'services.search', 'services.add-review', 'services.reviews', 'services.similar'] as $name) {
            $this->assertFalse(Route::has($name), "{$name} should not exist");
        }

        $user = User::factory()->create();
        $service = BeautyService::factory()->create();
        foreach (['/favorites', '/service-history', "/services/{$service->id}/similar", "/services/{$service->id}/reviews", '/payment/failed', '/services/search'] as $path) {
            $this->actingAs($user)->get('/s/rasta'.$path)->assertNotFound();
        }
    }
}
