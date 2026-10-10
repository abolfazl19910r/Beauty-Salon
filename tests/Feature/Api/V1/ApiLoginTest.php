<?php

namespace Tests\Feature\Api\V1;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ورود دومرحله‌ای اپ (بسته‌ی ۱ اپلیکیشن): مشتری با slug سالن، کادر فقط متخصص؛ challenge، تلاش‌های کد،
 * ارسال دوباره، توکن با ability اپ.
 */
class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = app(CurrentSalon::class)->get();
    }

    protected function customer(?Salon $salon = null, string $password = 'secret123'): User
    {
        $salon ??= $this->salon;

        return User::factory()->create([
            'user_type' => 'customer',
            'salon_id' => $salon->id,
            'password' => Hash::make($password),
        ]);
    }

    protected function specialistUser(?Salon $salon = null): User
    {
        $salon ??= $this->salon;

        return app(CurrentSalon::class)->withSalon($salon, function () {
            $specialist = Specialist::factory()->create();
            $user = User::find($specialist->user_id);
            $user->forceFill(['password' => Hash::make('secret123')])->save();

            return $user;
        });
    }

    protected function customerLogin(User $user, ?string $slug = null, string $password = 'secret123')
    {
        return $this->postJson('/api/v1/customer/salons/'.($slug ?? $this->salon->slug).'/login', [
            'phone' => $user->phone,
            'password' => $password,
        ]);
    }

    protected function customerVerify(string $challenge, string $code, ?string $slug = null)
    {
        return $this->postJson('/api/v1/customer/salons/'.($slug ?? $this->salon->slug).'/login/verify', [
            'challenge' => $challenge,
            'code' => $code,
            'device_name' => 'Redmi Note 8',
        ]);
    }

    protected function wrongCode(User $user): string
    {
        return $user->refresh()->login_verification_code === '111111' ? '222222' : '111111';
    }

    // ── مشتری ─────────────────────────────────────────────────────────────

    public function test_customer_full_login_issues_a_customer_token(): void
    {
        $user = $this->customer();

        $login = $this->customerLogin($user);
        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['challenge', 'expires_in', 'code_expires_in', 'resend_after', 'phone_hint']]);
        $this->assertNotNull($user->refresh()->login_verification_code);

        $verify = $this->customerVerify($login->json('data.challenge'), $user->login_verification_code);

        $verify->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.account.user.id', $user->id)
            ->assertJsonPath('data.account.user.type', 'customer')
            ->assertJsonPath('data.account.salon.slug', $this->salon->slug)
            ->assertJsonMissingPath('data.account.specialist');

        $token = $user->tokens()->sole();
        $this->assertSame(['customer'], $token->abilities);
        $this->assertSame('Redmi Note 8', $token->name);
        $this->assertTrue($token->expires_at->between(now()->addDays(89), now()->addDays(91)));
        $this->assertNull($user->refresh()->login_verification_code, 'code is single-use');

        $this->withToken($verify->json('data.token'))->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_wrong_password_and_unknown_phone_get_the_same_answer_and_no_code(): void
    {
        $user = $this->customer();

        $this->customerLogin($user, null, 'nope-nope')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_credentials');

        $this->postJson('/api/v1/customer/salons/'.$this->salon->slug.'/login', ['phone' => '09990000000', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_credentials');

        $this->assertNull($user->refresh()->login_verification_code);
        $this->assertDatabaseHas('security_logs', ['event' => 'login_attempt', 'user_id' => $user->id]);
    }

    public function test_customer_of_another_salon_cannot_log_in_through_this_salon(): void
    {
        $other = Salon::factory()->create();
        $foreigner = $this->customer($other);

        $this->customerLogin($foreigner)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_unknown_or_suspended_salon_is_404_like_the_web(): void
    {
        $suspended = Salon::factory()->suspended()->create();
        $user = $this->customer($suspended);

        $this->customerLogin($user, $suspended->slug)->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->customerLogin($user, 'no-such-salon')->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_challenge_is_bound_to_its_salon(): void
    {
        $other = Salon::factory()->create();
        $user = $this->customer();

        $challenge = $this->customerLogin($user)->json('data.challenge');

        $this->customerVerify($challenge, $user->refresh()->login_verification_code, $other->slug)
            ->assertStatus(410)
            ->assertJsonPath('error.code', 'challenge_expired');
    }

    public function test_five_wrong_codes_burn_the_challenge_and_the_code(): void
    {
        $user = $this->customer();
        $challenge = $this->customerLogin($user)->json('data.challenge');
        $wrong = $this->wrongCode($user);

        foreach ([4, 3, 2, 1] as $left) {
            $this->customerVerify($challenge, $wrong)
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'invalid_code')
                ->assertJsonPath('meta.attempts_left', $left);
        }

        $this->customerVerify($challenge, $wrong)
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_code_attempts');

        $this->assertNull($user->refresh()->login_verification_code);
        // بعد از پنجره‌ی limiter هم challenge سوخته می‌ماند
        $this->travel(61)->seconds();
        $this->customerVerify($challenge, '123456')->assertStatus(410);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_expired_code_is_reported_separately_and_not_counted(): void
    {
        $user = $this->customer();
        $challenge = $this->customerLogin($user)->json('data.challenge');
        $code = $user->refresh()->login_verification_code;

        $this->travel(3)->minutes();

        $this->customerVerify($challenge, $code)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'code_expired');
    }

    public function test_challenge_expires_after_its_ttl(): void
    {
        $user = $this->customer();
        $challenge = $this->customerLogin($user)->json('data.challenge');

        $this->travel(11)->minutes();

        $this->postJson('/api/v1/customer/salons/'.$this->salon->slug.'/login/resend', ['challenge' => $challenge])
            ->assertStatus(410)
            ->assertJsonPath('error.code', 'challenge_expired');
    }

    public function test_resend_respects_cooldown_and_limit_and_the_new_code_works(): void
    {
        $user = $this->customer();
        $challenge = $this->customerLogin($user)->json('data.challenge');
        $resend = fn () => $this->postJson('/api/v1/customer/salons/'.$this->salon->slug.'/login/resend', ['challenge' => $challenge]);

        $tooSoon = $resend();
        $tooSoon->assertStatus(429)->assertJsonPath('error.code', 'resend_too_soon')->assertHeader('Retry-After');
        $this->assertGreaterThan(0, $tooSoon->json('meta.retry_after'));

        foreach (range(1, 3) as $i) {
            $this->travel(61)->seconds();
            $resend()->assertOk()->assertJsonPath('data.challenge', $challenge);
        }

        $this->travel(61)->seconds();
        $resend()->assertStatus(429)->assertJsonPath('error.code', 'resend_limit_reached');

        $this->customerVerify($challenge, $user->refresh()->login_verification_code)->assertCreated();
    }

    // ── کادر ──────────────────────────────────────────────────────────────

    public function test_specialist_full_login_issues_a_staff_token(): void
    {
        $user = $this->specialistUser();

        $login = $this->postJson('/api/v1/staff/login', ['phone' => $user->phone, 'password' => 'secret123']);
        $login->assertOk();

        $verify = $this->postJson('/api/v1/staff/login/verify', [
            'challenge' => $login->json('data.challenge'),
            'code' => $user->refresh()->login_verification_code,
            'device_name' => 'Galaxy A17',
        ]);

        $specialist = app(CurrentSalon::class)->allSalons(fn () => $user->specialist()->first());
        $verify->assertCreated()
            ->assertJsonPath('data.account.user.type', 'staff')
            ->assertJsonPath('data.account.salon.id', $this->salon->id)
            ->assertJsonPath('data.account.specialist.id', $specialist->id);
        $this->assertSame(['staff'], $user->tokens()->sole()->abilities);
    }

    public function test_staff_without_a_specialist_record_gets_wrong_app_and_no_sms(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'staff',
            'salon_id' => null,
            'is_admin' => true,
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/staff/login', ['phone' => $admin->phone, 'password' => 'secret123'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'wrong_app');

        $this->assertNull($admin->refresh()->login_verification_code);
    }

    public function test_customer_credentials_do_not_work_on_staff_login(): void
    {
        $user = $this->customer();

        $this->postJson('/api/v1/staff/login', ['phone' => $user->phone, 'password' => 'secret123'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_specialist_of_an_inactive_salon_is_stopped_before_the_sms(): void
    {
        $expired = Salon::factory()->expired()->create();
        $user = $this->specialistUser($expired);

        $this->postJson('/api/v1/staff/login', ['phone' => $user->phone, 'password' => 'secret123'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'salon_inactive');

        $this->assertNull($user->refresh()->login_verification_code);
    }

    public function test_staff_challenge_cannot_be_used_on_the_customer_endpoint(): void
    {
        $user = $this->specialistUser();
        $challenge = $this->postJson('/api/v1/staff/login', ['phone' => $user->phone, 'password' => 'secret123'])->json('data.challenge');

        $this->customerVerify($challenge, $user->refresh()->login_verification_code)
            ->assertStatus(410)
            ->assertJsonPath('error.code', 'challenge_expired');
    }

    // ── محدودیت نرخ ───────────────────────────────────────────────────────

    public function test_login_is_limited_per_phone_and_separately_per_ip(): void
    {
        config([
            'api.rate_limits.login_per_subject_per_minute' => 2,
            // درخواستی که سقف شماره را رد کرده به شمارنده‌ی آی‌پی اضافه نمی‌شود (ThrottleRequests اول همه را چک می‌کند)
            'api.rate_limits.login_per_ip_per_minute' => 3,
        ]);
        $url = '/api/v1/staff/login';

        $this->postJson($url, ['phone' => '09120000001', 'password' => 'x'])->assertStatus(422);
        $this->postJson($url, ['phone' => '09120000001', 'password' => 'x'])->assertStatus(422);
        $this->postJson($url, ['phone' => '09120000001', 'password' => 'x'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_attempts');

        // شماره‌ی دیگر از همان آی‌پی (CGNAT) هنوز می‌تواند، تا سقف خود آی‌پی
        $this->postJson($url, ['phone' => '09120000002', 'password' => 'x'])->assertStatus(422);
        $this->postJson($url, ['phone' => '09120000003', 'password' => 'x'])->assertStatus(429);
    }

    public function test_api_login_does_not_spend_the_web_login_budget(): void
    {
        config(['api.rate_limits.login_per_subject_per_minute' => 50, 'api.rate_limits.login_per_ip_per_minute' => 50]);

        foreach (range(1, 6) as $i) {
            $this->postJson('/api/v1/staff/login', ['phone' => '09120000009', 'password' => 'x']);
        }

        // limiter وب 'auth' (پیش‌فرض ۵ در دقیقه) دست‌نخورده
        $this->post('/login', ['phone' => '09120000009', 'password' => 'x'])->assertStatus(302)->assertSessionHasErrors('phone');
    }
}
