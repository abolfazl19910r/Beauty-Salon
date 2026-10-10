<?php

namespace Tests\Feature\Api\V1;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Middleware\Api\ForceJsonResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * پایه‌ی /api/v1 (بسته‌ی ۱ اپلیکیشن): JSON اجباری، قالب یکسان موفق/خطا، رندرکننده‌ی خطا و محدودیت نرخ.
 * مسیرهای آزمایشی زیر api/v1/_probe فقط داخل همین تست ثبت می‌شوند.
 */
class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api/v1/_probe')->middleware(['api', ForceJsonResponse::class])->group(function () {
            Route::post('validate', fn (Request $request) => $request->validate(['phone' => ['required', 'regex:/^09[0-9]{9}$/']]));
            Route::get('auth', fn () => 'ok')->middleware('auth:sanctum');
            Route::get('domain', fn () => throw BookingNotAvailableException::slotTaken('slot 10:00 taken'));
            Route::get('boom', fn () => throw new \RuntimeException('SQLSTATE secret table users_internal'));
            Route::get('abort-fa', fn () => abort(403, 'فقط مالک سالن.'));
            Route::get('abort-en', fn () => abort(403));
            Route::get('model', fn () => \App\Models\Salon::findOrFail(999999));
        });
    }

    public function test_status_returns_success_envelope_with_unescaped_unicode(): void
    {
        $response = $this->get('/api/v1/status');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure(['success', 'data' => ['api_version', 'server_time']]);
    }

    public function test_unknown_v1_route_is_json_404_without_framework_text(): void
    {
        $response = $this->get('/api/v1/does-not-exist');

        $response->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'مورد درخواستی پیدا نشد.']]);
    }

    public function test_wrong_method_is_method_not_allowed(): void
    {
        $this->post('/api/v1/status')
            ->assertStatus(405)
            ->assertJsonPath('error.code', 'method_not_allowed');
    }

    public function test_validation_error_is_422_with_fields_even_without_accept_header(): void
    {
        // بدون Accept: application/json، وب redirect می‌کرد؛ ForceJsonResponse آن را JSON می‌کند
        $response = $this->post('/api/v1/_probe/validate', ['phone' => '123']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['message', 'fields' => ['phone']]]);
    }

    public function test_every_v1_route_forces_json(): void
    {
        // کنترلرهایی که روی expectsJson() شاخه می‌زنند (redirect در برابر JSON) برای اپی که هدر Accept
        // نمی‌فرستد هم مسیر JSON را بروند. شکل خطاها به این وابسته نیست (رندرکننده با مسیر تشخیص می‌دهد)،
        // پس اینجا خودِ حضور middleware روی همه‌ی مسیرهای واقعی v1 چک می‌شود.
        $v1Routes = collect(Route::getRoutes()->getRoutesByName())
            ->filter(fn ($route, $name) => str_starts_with($name, 'api.v1.'));

        $this->assertNotEmpty($v1Routes);
        foreach ($v1Routes as $name => $route) {
            $this->assertContains(ForceJsonResponse::class, $route->gatherMiddleware(), $name);
        }
    }

    public function test_missing_token_is_401_not_a_redirect_to_web_login(): void
    {
        $this->get('/api/v1/_probe/auth')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_domain_exception_keeps_its_status_and_gets_a_snake_case_code(): void
    {
        $this->get('/api/v1/_probe/domain')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'booking_not_available')
            ->assertJsonPath('error.message', 'زمان انتخابی شما دیگر در دسترس نیست. لطفاً زمان دیگری را انتخاب کنید.');
    }

    public function test_unexpected_error_is_500_without_leaking_details_even_in_debug(): void
    {
        config(['app.debug' => true]);

        $response = $this->get('/api/v1/_probe/boom');

        $response->assertStatus(500)
            ->assertJsonPath('error.code', 'server_error');
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('trace', $response->getContent());
    }

    public function test_persian_abort_message_is_kept_and_framework_english_is_replaced(): void
    {
        $this->get('/api/v1/_probe/abort-fa')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.message', 'فقط مالک سالن.');

        $this->get('/api/v1/_probe/abort-en')
            ->assertForbidden()
            ->assertJsonPath('error.message', 'اجازه‌ی این کار را ندارید.');

        $model = $this->get('/api/v1/_probe/model');
        $model->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->assertStringNotContainsString('App\\\\Models', $model->getContent());
    }

    public function test_authenticated_limiter_returns_429_with_retry_after(): void
    {
        config(['api.rate_limits.authenticated_per_minute' => 2]);

        $this->get('/api/v1/status')->assertOk();
        $this->get('/api/v1/status')->assertOk();
        $response = $this->get('/api/v1/status');

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_attempts')
            ->assertHeader('Retry-After');
        $this->assertGreaterThan(0, $response->json('meta.retry_after'));
    }

    public function test_legacy_api_routes_keep_their_old_format(): void
    {
        // /api/bookings (بیرون از v1) تا بسته‌ی ۳ دست‌نخورده می‌ماند
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401)->assertJsonMissingPath('error.code');
    }
}
