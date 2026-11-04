<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ThrottleRequests;
use App\Jobs\SendPasswordResetCodeJob;
use App\Models\Salon;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\Links\ShortLinkService;
use App\Support\Auth\PasswordLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۵ — L3 (هیچ فرمی پیش از کد پیامکی نمی‌گوید کدام شماره حساب دارد)، L4 (کوتاه‌لینک ۸ نویسه)،
 * L5 (قفل موقت ورود با رمز برای هر شماره). سقف‌های دقیقه‌ای IP برداشته می‌شوند تا خود قاعده سنجیده شود.
 */
class AccountEnumerationAndLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_staff_forgot_password_answers_the_same_for_known_and_unknown_phones(): void
    {
        Bus::fake();
        $staff = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);

        $known = $this->post('/forgot-password', ['phone' => $staff->phone]);
        $unknown = $this->post('/forgot-password', ['phone' => '09120000000']);

        foreach ([$known, $unknown] as $response) {
            $response->assertSessionHasNoErrors()->assertSessionHas('success', 'کد تایید ارسال شد.');
            $this->assertStringContainsString('/reset-password/', (string) $response->headers->get('Location'));
        }
        Bus::assertDispatchedTimes(SendPasswordResetCodeJob::class, 1);
    }

    public function test_customer_forgot_password_answers_the_same_for_known_and_unknown_phones(): void
    {
        Bus::fake();
        $salon = Salon::factory()->create(['slug' => 'l3-salon']);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);

        $known = $this->post('/s/l3-salon/forgot-password', ['phone' => $customer->phone]);
        $unknown = $this->post('/s/l3-salon/forgot-password', ['phone' => '09120000001']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame(
            preg_replace('#/[A-Za-z0-9]{60}$#', '', (string) $known->headers->get('Location')),
            preg_replace('#/[A-Za-z0-9]{60}$#', '', (string) $unknown->headers->get('Location')),
        );
        $token = DB::table('password_reset_tokens')->where('phone', $salon->id.':09120000001')->value('token');
        $this->post('/s/l3-salon/reset-password', ['token' => $token, 'code' => '123456', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertSessionHasErrors(['code' => 'کد تایید اشتباه یا منقضی شده است.']);
        Bus::assertDispatchedTimes(SendPasswordResetCodeJob::class, 1);
    }

    public function test_api_password_reset_decoy_behaves_like_a_real_challenge(): void
    {
        $salon = Salon::factory()->create(['slug' => 'l3-api']);
        $real = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '09125550010']);

        $realBody = $this->postJson('/api/v1/customer/salons/l3-api/password/forgot', ['phone' => $real->phone])->assertOk()->json('data');
        $decoyBody = $this->postJson('/api/v1/customer/salons/l3-api/password/forgot', ['phone' => '09125550011'])->assertOk()->json('data');

        $this->assertSame(array_keys($realBody), array_keys($decoyBody));
        $this->assertSame('0912***0011', $decoyBody['phone_hint']);

        // کد غلط: همان خطا و همان شمارنده؛ پنجمی همان ۴۲۹
        foreach (range(1, 4) as $i) {
            $this->postJson('/api/v1/customer/salons/l3-api/password/reset', ['challenge' => $decoyBody['challenge'], 'code' => '123456', 'password' => 'new-password-9', 'password_confirmation' => 'new-password-9'])
                ->assertStatus(422)->assertJsonPath('error.code', 'invalid_code')->assertJsonPath('meta.attempts_left', 5 - $i);
        }
        $this->postJson('/api/v1/customer/salons/l3-api/password/reset', ['challenge' => $decoyBody['challenge'], 'code' => '123456', 'password' => 'new-password-9', 'password_confirmation' => 'new-password-9'])
            ->assertStatus(429)->assertJsonPath('error.code', 'too_many_code_attempts');
    }

    public function test_api_decoy_resend_keeps_the_same_cooldown(): void
    {
        $this->postJson('/api/v1/staff/password/forgot', ['phone' => '09125550012'])->assertOk();
        $challenge = $this->postJson('/api/v1/staff/password/forgot', ['phone' => '09125550012'])->json('data.challenge');

        $this->postJson('/api/v1/staff/password/resend', ['challenge' => $challenge])->assertStatus(429)->assertJsonPath('error.code', 'resend_too_soon');
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/staff/password/resend', ['challenge' => $challenge])->assertOk()->assertJsonPath('data.challenge', $challenge);
    }

    public function test_short_links_use_eight_characters(): void
    {
        $url = app(ShortLinkService::class)->shorten('https://example.test/x');

        $this->assertMatchesRegularExpression('#/b/[a-z2-9]{8}$#', $url);
        $this->assertSame(8, strlen(ShortLink::query()->latest('id')->value('code')));
    }

    public function test_staff_password_login_locks_after_ten_wrong_passwords_even_with_the_right_one(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff', 'password' => Hash::make('right-password-1')]);

        foreach (range(1, 10) as $i) {
            $this->post('/login', ['phone' => $staff->phone, 'password' => 'wrong-'.$i])
                ->assertSessionHasErrors(['phone' => 'اطلاعات وارد شده صحیح نمی‌باشد.']);
        }
        $this->post('/login', ['phone' => $staff->phone, 'password' => 'right-password-1'])
            ->assertSessionHasErrors(['phone' => PasswordLockout::MESSAGE]);
        $this->assertGuest();

        // همان قفل در اپ همکار
        $this->postJson('/api/v1/staff/login', ['phone' => $staff->phone, 'password' => 'right-password-1', 'device_name' => 't'])
            ->assertStatus(429)->assertJsonPath('error.code', 'too_many_password_attempts');

        $this->travel(16)->minutes();
        $this->post('/login', ['phone' => $staff->phone, 'password' => 'right-password-1'])->assertRedirect(route('login.verify.show'));
    }

    public function test_an_unknown_phone_locks_the_same_way(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post('/login', ['phone' => '09120000099', 'password' => 'x'.$i]);
        }

        $this->post('/login', ['phone' => '09120000099', 'password' => 'y'])->assertSessionHasErrors(['phone' => PasswordLockout::MESSAGE]);
    }

    public function test_customer_lock_is_per_salon_and_cleared_by_a_successful_login_or_reset(): void
    {
        $a = Salon::factory()->create(['slug' => 'lock-a']);
        $b = Salon::factory()->create(['slug' => 'lock-b']);
        $phone = '09125550020';
        $inA = User::factory()->create(['user_type' => 'customer', 'salon_id' => $a->id, 'phone' => $phone, 'password' => Hash::make('pass-a-123')]);
        User::factory()->create(['user_type' => 'customer', 'salon_id' => $b->id, 'phone' => $phone, 'password' => Hash::make('pass-b-123')]);

        foreach (range(1, 9) as $i) {
            $this->post('/s/lock-a/login', ['phone' => $phone, 'password' => 'bad'.$i]);
        }
        // نهمین غلط و بعد ورود درست: شمارنده صفر می‌شود
        $this->post('/s/lock-a/login', ['phone' => $phone, 'password' => 'pass-a-123'])->assertSessionHasNoErrors();
        $this->assertFalse(PasswordLockout::locked(PasswordLockout::customer($a->id, $phone)));

        foreach (range(1, 10) as $i) {
            $this->post('/s/lock-a/login', ['phone' => $phone, 'password' => 'bad'.$i]);
        }
        $this->assertTrue(PasswordLockout::locked(PasswordLockout::customer($a->id, $phone)));
        $this->assertFalse(PasswordLockout::locked(PasswordLockout::customer($b->id, $phone)), 'سالن دیگر جداست');

        // بازیابی رمز با کد پیامکی قفل را برمی‌دارد
        $this->post('/s/lock-a/forgot-password', ['phone' => $phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $a->id.':'.$phone)->value('token');
        $this->post('/s/lock-a/reset-password', ['token' => $token, 'code' => $this->lastSmsCode(), 'password' => 'new-pass-a-1', 'password_confirmation' => 'new-pass-a-1'])
            ->assertSessionHasNoErrors();
        $this->assertFalse(PasswordLockout::locked(PasswordLockout::customer($a->id, $phone)));
        $this->assertTrue(Hash::check('new-pass-a-1', $inA->fresh()->password));
    }
}
