<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ThrottleRequests;
use App\Models\Salon;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use App\Support\Auth\SmsCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴، M1 — کدهای پیامکی وب (فراموشی رمز مرکزی و مشتری، کد ورود، تأیید موبایل، دومرحله‌ای): فقط HMAC در users،
 * hash_equals، و حداکثر ۵ تلاش غلط برای هر کد (بعد کد می‌سوزد). سقف دقیقه‌ای IP عمداً برداشته می‌شود تا خود شمارنده سنجیده شود.
 */
class WebSmsCodeHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function wrongOf(string $code): string
    {
        return $code === '111111' ? '222222' : '111111';
    }

    public function test_staff_password_reset_code_is_stored_hashed_and_burns_after_five_wrong_guesses(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null, 'password' => bcrypt('old-password-1')]);
        $this->post('/forgot-password', ['phone' => $user->phone])->assertRedirect();
        $code = $this->lastSmsCode();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $stored = $user->fresh()->verification_code;
        $this->assertNotSame($code, $stored);
        $this->assertSame(64, strlen($stored));
        $this->assertSame(SmsCode::hash($code), $stored);

        $payload = fn (string $c) => ['token' => $token, 'code' => $c, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'];
        foreach (range(1, 5) as $i) {
            $this->post('/reset-password', $payload($this->wrongOf($code)))->assertSessionHasErrors('code');
        }
        $this->assertNull($user->fresh()->verification_code, 'کد پس از ۵ حدس غلط باید بسوزد');

        $this->post('/reset-password', $payload($code))->assertSessionHasErrors('code');
        $this->assertTrue(password_verify('old-password-1', $user->fresh()->password));
    }

    public function test_four_wrong_guesses_still_allow_the_right_code(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $code = $this->lastSmsCode();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        foreach (range(1, 4) as $i) {
            $this->post('/reset-password', ['token' => $token, 'code' => $this->wrongOf($code), 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1']);
        }
        $this->post('/reset-password', ['token' => $token, 'code' => $code, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertRedirect(route('login'));
        $this->assertTrue(password_verify('new-password-1', $user->fresh()->password));
    }

    public function test_a_new_code_resets_the_attempt_counter(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');
        foreach (range(1, 4) as $i) {
            $this->post('/reset-password', ['token' => $token, 'code' => $this->wrongOf($this->lastSmsCode()), 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1']);
        }

        $this->travel(70)->seconds();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');
        $code = $this->lastSmsCode();
        $this->post('/reset-password', ['token' => $token, 'code' => $this->wrongOf($code), 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1']);

        $this->assertNotNull($user->fresh()->verification_code, 'شمارنده‌ی کد قبلی نباید به کد تازه برسد');
    }

    public function test_customer_password_reset_code_burns_after_five_wrong_guesses(): void
    {
        $salon = Salon::factory()->create(['slug' => 'm1-salon']);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'password' => bcrypt('old-password-1')]);
        $this->post('/s/m1-salon/forgot-password', ['phone' => $customer->phone])->assertRedirect();
        $code = $this->lastSmsCode();
        $token = DB::table('password_reset_tokens')->where('phone', $salon->id.':'.$customer->phone)->value('token');
        $this->assertSame(SmsCode::hash($code), $customer->fresh()->verification_code);

        foreach (range(1, 5) as $i) {
            $this->post('/s/m1-salon/reset-password', ['token' => $token, 'code' => $this->wrongOf($code), 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1']);
        }
        $this->post('/s/m1-salon/reset-password', ['token' => $token, 'code' => $code, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertSessionHasErrors('code');
        $this->assertTrue(password_verify('old-password-1', $customer->fresh()->password));
    }

    public function test_login_code_is_hashed_and_burns_after_five_wrong_guesses(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $code = $this->lastSmsCode();
        $this->assertSame(SmsCode::hash($code), $user->fresh()->login_verification_code);

        foreach (range(1, 5) as $i) {
            $this->post('/login/verify', ['code' => $this->wrongOf($code)])->assertSessionHasErrors('code');
        }
        $this->post('/login/verify', ['code' => $code]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->login_verification_code);
    }

    public function test_two_factor_code_is_hashed_and_burns_after_five_wrong_guesses(): void
    {
        $user = User::factory()->create(['phone_verified_at' => now()]);
        $service = app(TwoFactorAuthService::class);
        $code = $service->generateCode($user);

        $this->assertSame(SmsCode::hash($code), $user->fresh()->two_factor_code);
        foreach (range(1, 5) as $i) {
            $this->assertFalse($service->verify($user, $this->wrongOf($code)));
        }
        $this->assertFalse($service->verify($user, $code));
        $this->assertNull($user->fresh()->two_factor_code);
    }

    public function test_phone_verification_code_is_hashed(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('verification.notice'));
        $code = $this->lastSmsCode();

        $this->assertSame(SmsCode::hash($code), $user->fresh()->verification_code);
        $this->actingAs($user)->postJson(route('verification.verify'), ['code' => $code])->assertOk();
        $this->assertTrue($user->fresh()->hasVerifiedPhone());
    }

    public function test_hash_depends_on_the_app_key(): void
    {
        $a = SmsCode::hash('123456');
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->assertNotSame($a, SmsCode::hash('123456'), 'HMAC با APP_KEY — نشت دیتابیس به‌تنهایی کد را نمی‌دهد');
    }
}
