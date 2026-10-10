<?php

namespace Tests\Feature\Api\V1;

use App\Http\Middleware\Api\ForceJsonResponse;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * توکن‌های اپ (بسته‌ی ۱ اپلیکیشن): /me، خروج، «دستگاه‌های من»، جدایی دو اپ، عمر لغزان ۹۰ روزه.
 */
class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // مسیر نمونه‌ی «فقط اپ همکار» — بسته‌ی ۲ مسیرهای واقعی‌اش را با همین middleware می‌سازد
        Route::prefix('api/v1/_probe')->middleware(['api', ForceJsonResponse::class, 'auth:sanctum', 'api.audience:staff'])
            ->get('staff-only', fn () => ['ok' => true, 'salon' => app(CurrentSalon::class)->id()]);
    }

    protected function customer(): User
    {
        return User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
    }

    protected function specialistUser(): User
    {
        return User::find(Specialist::factory()->create()->user_id);
    }

    protected function tokenFor(User $user, string $audience, string $name = 'phone', int $days = 90): string
    {
        return $user->createToken($name, [$audience], now()->addDays($days))->plainTextToken;
    }

    /** گارد sanctum کاربر را بین درخواست‌های یک تست نگه می‌دارد؛ هر درخواست باید از نو احراز شود */
    protected function fresh(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    public function test_me_requires_a_bearer_token_and_ignores_the_web_session(): void
    {
        $user = $this->customer();

        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        // کوکی session وب (TransientToken که هر abilityای را «دارد») کافی نیست
        $this->actingAs($user)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_returns_account_and_current_token(): void
    {
        $user = $this->customer();
        $plain = $this->tokenFor($user, 'customer', 'Galaxy A17');

        $this->withToken($plain)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.type', 'customer')
            ->assertJsonPath('data.salon.id', $user->salon_id)
            ->assertJsonPath('data.token.name', 'Galaxy A17');
    }

    public function test_customer_token_cannot_use_staff_routes_and_staff_token_can(): void
    {
        $customerToken = $this->tokenFor($this->customer(), 'customer');
        $specialistUser = $this->specialistUser();
        $staffToken = $this->tokenFor($specialistUser, 'staff');

        $this->withToken($customerToken)->getJson('/api/v1/_probe/staff-only')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'wrong_app');

        $this->fresh()->withToken($staffToken)->getJson('/api/v1/_probe/staff-only')
            ->assertOk()
            ->assertJsonPath('salon', app(CurrentSalon::class)->allSalons(fn () => $specialistUser->specialist()->first())->salon_id);
    }

    public function test_token_without_an_app_ability_is_rejected(): void
    {
        $user = $this->customer();
        $plain = $user->createToken('legacy', ['*'])->plainTextToken;

        $this->withToken($plain)->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('error.code', 'wrong_app');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->customer();
        $phone = $this->tokenFor($user, 'customer', 'phone');
        $tablet = $this->tokenFor($user, 'customer', 'tablet');

        $this->withToken($phone)->postJson('/api/v1/logout')->assertOk()->assertJsonPath('success', true);

        $this->fresh()->withToken($phone)->getJson('/api/v1/me')->assertUnauthorized();
        $this->fresh()->withToken($tablet)->getJson('/api/v1/me')->assertOk();
    }

    public function test_device_list_destroy_and_revoke_others(): void
    {
        $user = $this->customer();
        $current = $this->tokenFor($user, 'customer', 'current');
        $this->tokenFor($user, 'customer', 'old phone');
        $this->tokenFor($user, 'customer', 'stale', -1); // منقضی — در فهرست نمی‌آید
        $strangerTokenId = $this->customer()->createToken('x', ['customer'])->accessToken->id;

        $list = $this->withToken($current)->getJson('/api/v1/tokens');
        $list->assertOk()->assertJsonCount(2, 'data');
        $this->assertTrue(collect($list->json('data'))->firstWhere('name', 'current')['current']);
        $this->assertFalse(collect($list->json('data'))->firstWhere('name', 'old phone')['current']);

        $oldId = collect($list->json('data'))->firstWhere('name', 'old phone')['id'];

        $this->fresh()->withToken($current)->deleteJson('/api/v1/tokens/'.$strangerTokenId)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $strangerTokenId]);

        $this->fresh()->withToken($current)->deleteJson('/api/v1/tokens/'.$oldId)->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldId]);

        $this->tokenFor($user, 'customer', 'another');
        $this->fresh()->withToken($current)->postJson('/api/v1/tokens/revoke-others')
            ->assertOk()
            ->assertJsonPath('data.revoked', 2); // «another» و «stale»
        $this->assertSame(['current'], $user->tokens()->pluck('name')->all());
    }

    public function test_use_slides_the_expiry_and_idle_tokens_die(): void
    {
        $user = $this->customer();
        $active = $this->tokenFor($user, 'customer', 'active', 10);

        $this->withToken($active)->getJson('/api/v1/me')->assertOk();
        $expiresAt = $user->tokens()->where('name', 'active')->value('expires_at');
        $this->assertTrue(\Illuminate\Support\Carbon::parse($expiresAt)->between(now()->addDays(89), now()->addDays(91)));

        $idle = $this->tokenFor($user, 'customer', 'idle', 90);
        $this->travel(91)->days();

        $this->fresh()->withToken($idle)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_expired_tokens_are_pruned_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('sanctum:prune-expired --hours=24')
            ->assertSuccessful();
    }
}
