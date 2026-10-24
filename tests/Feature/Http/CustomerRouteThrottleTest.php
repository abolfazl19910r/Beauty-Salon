<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * محدودیت نرخ در خود برنامه برای مسیرهای حساس وب مشتری (بسته‌ی مجوز 5د). پلن رایگان CDN آروان محدودیت نرخ ندارد و
 * امنیت نباید به CDN وابسته باشد.
 *
 * 🐞 پیش از این: ارسال دوباره‌ی کد دومرحله‌ای (هر بار یک پیامک واقعی)، تأیید کد ۶ رقمی دومرحله‌ای و پرداخت امن، بررسی کد
 * تخفیف (شمارش کدها)، ثبت نظر بی ورود، تغییر رمز و ثبت و لغو نوبت هیچ سقفی نداشتند.
 */
class CustomerRouteThrottleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['two_factor_enabled' => true]);
    }

    /** چند بار می‌زند؛ آخرین وضعیت را برمی‌گرداند و مطمئن می‌شود تا سقف ۴۲۹ نیامده */
    private function hammer(int $allowed, callable $request): int
    {
        for ($i = 1; $i <= $allowed; $i++) {
            $this->assertNotSame(429, $request(), "درخواست {$i} از {$allowed} نباید رد شود");
        }

        return $request();
    }

    public function test_resending_the_two_factor_sms_is_limited_per_user(): void
    {
        $status = $this->hammer(3, fn () => $this->actingAs($this->user)->postJson(route('security.2fa.resend'))->status());

        $this->assertSame(429, $status);
    }

    public function test_guessing_the_six_digit_two_factor_code_is_limited(): void
    {
        $this->user->update(['two_factor_code' => '123456', 'two_factor_code_expires_at' => now()->addMinutes(2)]);

        $status = $this->hammer(5, fn () => $this->actingAs($this->user)->postJson(route('security.2fa.verify'), ['code' => '000000'])->status());

        $this->assertSame(429, $status);
    }

    public function test_discount_codes_cannot_be_enumerated(): void
    {
        $status = $this->hammer(10, fn () => $this->actingAs($this->user)
            ->postJson(route('bookings.check-discount'), ['code' => 'GUESS'.random_int(1000, 9999)])->status());

        $this->assertSame(429, $status);
    }

    public function test_reviews_without_login_are_limited_per_ip(): void
    {
        $status = $this->hammer(10, fn () => $this->postJson(route('reviews.store'), ['token' => 'nope', 'rating' => 5])->status());

        $this->assertSame(429, $status);
    }

    public function test_password_change_is_limited(): void
    {
        $status = $this->hammer(10, fn () => $this->actingAs($this->user)
            ->putJson(route('profile.password'), ['current_password' => 'wrong', 'password' => 'Newpass123!', 'password_confirmation' => 'Newpass123!'])->status());

        $this->assertSame(429, $status);
    }

    public function test_booking_actions_are_limited_but_generous(): void
    {
        $status = $this->hammer(30, fn () => $this->actingAs($this->user)->postJson(route('bookings.store'), [])->status());

        $this->assertSame(429, $status);
    }

    public function test_one_customer_hitting_the_limit_does_not_block_another(): void
    {
        $this->hammer(3, fn () => $this->actingAs($this->user)->postJson(route('security.2fa.resend'))->status());

        $other = User::factory()->create(['two_factor_enabled' => true]);
        $this->assertNotSame(429, $this->actingAs($other)->postJson(route('security.2fa.resend'))->status());
    }
}
