<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\Salon;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Auth\TrustedDeviceService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * «مرا به خاطر بسپار» ورود وب (بسته‌ی ۶، ۲۰۲۶-۱۰-۲۰): تیک قبلاً در فرم بود ولی هیچ کاری نمی‌کرد. حالا نشست ۹۰ روزه و دستگاه شناخته‌شده
 * (ورود بعدی روی همان مرورگر فقط با رمز، بدون کد پیامکی). رمز همیشه لازم است؛ تغییر رمز همه را باطل می‌کند.
 */
class RememberedDeviceTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '09127770000';

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SendLoginVerificationCodeJob::class]);
        $this->salon = app(CurrentSalon::class)->get();
    }

    private function customer(): User
    {
        return User::factory()->create([
            'salon_id' => $this->salon->id, 'user_type' => 'customer', 'phone' => self::PHONE, 'password' => Hash::make('secret-123'),
        ]);
    }

    private function lastCode(): string
    {
        $code = null;
        Bus::assertDispatched(SendLoginVerificationCodeJob::class, function ($job) use (&$code) {
            $code = (new \ReflectionProperty($job, 'code'))->getValue($job);

            return true;
        });

        return $code;
    }

    /** ورود کامل مشتری (رمز + کد)؛ پاسخ مرحله‌ی کد برمی‌گردد */
    private function loginWithCode(bool $remember)
    {
        $this->post(route('salon.login', ['salon_slug' => $this->salon->slug]), array_filter([
            'phone' => self::PHONE, 'password' => 'secret-123', 'remember' => $remember ? '1' : null,
        ]))->assertRedirect(route('salon.login.verify.show', ['salon_slug' => $this->salon->slug]));

        return $this->post(route('salon.login.verify', ['salon_slug' => $this->salon->slug]), ['code' => $this->lastCode()]);
    }

    private function logoutKeepingCookies(): void
    {
        Auth::logout();
        $this->flushSession();
        Bus::fake([SendLoginVerificationCodeJob::class]);
    }

    public function test_remember_keeps_the_session_for_90_days_and_trusts_the_device(): void
    {
        $user = $this->customer();

        $response = $this->loginWithCode(remember: true)->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $rememberCookie = collect($response->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie);
        $this->assertEqualsWithDelta(now()->addDays(90)->timestamp, $rememberCookie->getExpiresTime(), 120);

        $deviceCookie = $response->getCookie(TrustedDeviceService::cookieName($user));
        $this->assertNotNull($deviceCookie);
        $this->assertEqualsWithDelta(now()->addDays(90)->timestamp, $deviceCookie->getExpiresTime(), 120);
        $this->assertTrue($deviceCookie->isHttpOnly());
        $device = TrustedDevice::sole();
        $this->assertSame($user->id, $device->user_id);
        $this->assertNotSame($deviceCookie->getValue(), $device->token_hash);
    }

    public function test_trusted_device_logs_in_with_password_only(): void
    {
        $user = $this->customer();
        $token = $this->loginWithCode(remember: true)->getCookie(TrustedDeviceService::cookieName($user))->getValue();
        $this->logoutKeepingCookies();

        $this->withCookie(TrustedDeviceService::cookieName($user), $token)
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => self::PHONE, 'password' => 'secret-123'])
            ->assertRedirect(route('home', ['salon_slug' => $this->salon->slug]));
        $this->assertAuthenticatedAs($user);
        Bus::assertNotDispatched(SendLoginVerificationCodeJob::class);

        // رمز غلط با همان کوکی: ورود نه
        $this->logoutKeepingCookies();
        $this->withCookie(TrustedDeviceService::cookieName($user), $token)
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => self::PHONE, 'password' => 'wrong'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_without_remember_no_device_is_trusted(): void
    {
        $user = $this->customer();
        $this->loginWithCode(remember: false)->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, TrustedDevice::count());

        // کوکی ساختگی (توکنی که در جدول نیست)
        $this->logoutKeepingCookies();
        $this->withCookie(TrustedDeviceService::cookieName($user), str_repeat('a', 64))
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => self::PHONE, 'password' => 'secret-123'])
            ->assertRedirect(route('salon.login.verify.show', ['salon_slug' => $this->salon->slug]));
        $this->assertGuest();
    }

    public function test_another_users_token_does_not_skip_the_code(): void
    {
        $user = $this->customer();
        $token = $this->loginWithCode(remember: true)->getCookie(TrustedDeviceService::cookieName($user))->getValue();
        $this->logoutKeepingCookies();

        $other = User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer', 'phone' => '09120001111', 'password' => Hash::make('secret-123')]);
        $this->withCookie(TrustedDeviceService::cookieName($other), $token)
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => '09120001111', 'password' => 'secret-123'])
            ->assertRedirect(route('salon.login.verify.show', ['salon_slug' => $this->salon->slug]));
        $this->assertGuest();
    }

    public function test_expired_device_needs_the_code_again(): void
    {
        $user = $this->customer();
        $token = $this->loginWithCode(remember: true)->getCookie(TrustedDeviceService::cookieName($user))->getValue();
        TrustedDevice::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);
        $this->logoutKeepingCookies();

        $this->withCookie(TrustedDeviceService::cookieName($user), $token)
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => self::PHONE, 'password' => 'secret-123'])
            ->assertRedirect(route('salon.login.verify.show', ['salon_slug' => $this->salon->slug]));
        $this->assertGuest();
    }

    public function test_password_change_revokes_trusted_devices_and_remember_token(): void
    {
        $user = $this->customer();
        $token = $this->loginWithCode(remember: true)->getCookie(TrustedDeviceService::cookieName($user))->getValue();
        $rememberToken = $user->fresh()->remember_token;
        $this->assertNotEmpty($rememberToken);

        $user->fresh()->update(['password' => Hash::make('new-secret-456')]);

        $this->assertSame(0, TrustedDevice::count());
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);

        $this->logoutKeepingCookies();
        $this->withCookie(TrustedDeviceService::cookieName($user), $token)
            ->post(route('salon.login', ['salon_slug' => $this->salon->slug]), ['phone' => self::PHONE, 'password' => 'new-secret-456'])
            ->assertRedirect(route('salon.login.verify.show', ['salon_slug' => $this->salon->slug]));
    }

    public function test_staff_login_remembers_too(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff', 'is_admin' => true, 'phone' => '09125550000', 'password' => Hash::make('secret-123')]);

        $this->post(route('login'), ['phone' => '09125550000', 'password' => 'secret-123', 'remember' => '1'])->assertRedirect(route('login.verify.show'));
        $response = $this->post(route('login.verify'), ['code' => $this->lastCode()])->assertRedirect();
        $this->assertAuthenticatedAs($staff);
        $token = $response->getCookie(TrustedDeviceService::cookieName($staff))->getValue();

        $this->logoutKeepingCookies();
        $this->withCookie(TrustedDeviceService::cookieName($staff), $token)
            ->post(route('login'), ['phone' => '09125550000', 'password' => 'secret-123'])
            ->assertRedirect($staff->fresh()->homePath());
        $this->assertAuthenticatedAs($staff);
        Bus::assertNotDispatched(SendLoginVerificationCodeJob::class);
    }

    public function test_login_forms_offer_remember_and_default_it_on_when_installed(): void
    {
        $this->get(route('salon.login', ['salon_slug' => $this->salon->slug]))->assertOk()
            ->assertSee('name="remember"', false)->assertSee('display-mode: standalone', false);
        $this->get(route('login'))->assertOk()->assertSee('name="remember"', false);
    }
}
