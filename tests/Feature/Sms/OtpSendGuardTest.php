<?php

namespace Tests\Feature\Sms;

use App\Contracts\Sms\OtpJob;
use App\Jobs\SendPasswordResetCodeJob;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\SuperAdmin\PlatformAlertNotification;
use App\Services\Sms\OtpSendGuard;
use App\Support\Queues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴، H4 — «پمپاژ پیامک». سقف‌ها (تصمیم ابوالفضل): هر شماره ۳ در ۱۰ دقیقه و ۱۰ در روز، هر IP ‏۳۰ در ساعت،
 * کل پلتفرم ۲٬۰۰۰ در روز با اعلان ابرمدیر (۸۰٪ و پر شدن) و توقف.
 */
class OtpSendGuardTest extends TestCase
{
    use RefreshDatabase;

    private OtpSendGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->guard = app(OtpSendGuard::class);
    }

    public function test_one_phone_gets_three_codes_per_ten_minutes(): void
    {
        foreach (range(1, 3) as $i) {
            $this->assertTrue($this->guard->allow('09121110001', "10.0.0.$i"), "کد $i");
        }
        $this->assertFalse($this->guard->allow('09121110001', '10.0.0.9'), 'چهارمی در ۱۰ دقیقه — حتی از IP دیگر');
        $this->assertTrue($this->guard->allow('09121110002', '10.0.0.9'), 'شماره‌ی دیگر آزاد است');

        $this->travel(601)->seconds();
        $this->assertTrue($this->guard->allow('09121110001', '10.0.0.9'));
    }

    public function test_one_phone_gets_ten_codes_per_day_and_formats_count_as_the_same_phone(): void
    {
        $formats = ['09121110003', '+989121110003', '00989121110003', '۰۹۱۲۱۱۱۰۰۰۳', '9121110003'];
        $sent = 0;
        for ($i = 0; $i < 12; $i++) {
            if ($this->guard->allow($formats[$i % count($formats)], "10.0.1.$i")) {
                $sent++;
            }
            $this->travel(11)->minutes();
        }

        $this->assertSame(10, $sent);
    }

    public function test_one_ip_gets_thirty_codes_per_hour(): void
    {
        $sent = 0;
        for ($i = 0; $i < 35; $i++) {
            $sent += $this->guard->allow('0912200'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), '203.0.113.7') ? 1 : 0;
        }
        $this->assertSame(30, $sent);
        $this->assertTrue($this->guard->allow('09123334444', '203.0.113.8'));

        $this->travel(61)->minutes();
        $this->assertTrue($this->guard->allow('09123334445', '203.0.113.7'));
    }

    public function test_platform_daily_cap_stops_all_codes_and_tells_the_super_admin_once(): void
    {
        Notification::fake();
        $super = User::factory()->create(['is_admin' => true, 'phone' => '09120009999']);
        $super->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        config(['services.kavenegar.otp_limits.platform_per_day' => 10]);

        $sent = 0;
        for ($i = 0; $i < 14; $i++) {
            $sent += $this->guard->allow('0913000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), "198.51.100.$i") ? 1 : 0;
        }

        $this->assertSame(10, $sent);
        $this->assertSame(10, $this->guard->platformCountToday());
        Notification::assertSentToTimes($super, PlatformAlertNotification::class, 2);
        Notification::assertSentTo($super, PlatformAlertNotification::class, fn ($n) => $n->kind === 'sms_provider'
            && str_contains($n->message, 'به 8 از سقف روزانه‌ی 10') && $n->link === '/superadmin/sms');
        Notification::assertSentTo($super, PlatformAlertNotification::class, fn ($n) => str_contains($n->message, 'ارسال کد پیامکی متوقف شد'));

        $this->travelTo(now()->addDay()->startOfDay()->addMinute());
        $this->assertTrue($this->guard->allow('09130009999', '198.51.100.200'), 'روز تازه');
    }

    public function test_a_blocked_request_still_gets_the_normal_response_but_no_sms_is_queued(): void
    {
        config(['queue.work_via_scheduler' => false]);
        Bus::fake();
        $salon = Salon::factory()->create(['slug' => 'otp-guard']);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '09125550001']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/s/otp-guard/forgot-password', ['phone' => $customer->phone])->assertRedirect()->assertSessionHasNoErrors();
            $this->travel(70)->seconds();
        }

        Bus::assertDispatchedTimes(SendPasswordResetCodeJob::class, 3);
    }

    public function test_salon_signup_code_requests_from_one_ip_are_capped_per_hour(): void
    {
        config(['queue.work_via_scheduler' => false, 'services.kavenegar.otp_limits.per_ip_hour' => 2]);
        Bus::fake();

        foreach (['09127770001', '09127770002', '09127770003'] as $phone) {
            Queues::dispatchOtp(new SendRegistrationCodeJob($phone, '123456', null));
        }

        Bus::assertDispatchedTimes(SendRegistrationCodeJob::class, 2);
    }

    public function test_every_job_sent_through_dispatch_otp_is_guarded(): void
    {
        $classes = [];
        foreach ((new Finder)->files()->in(app_path())->name('*.php') as $file) {
            preg_match_all('/dispatchOtp\(new\s+\\\\?([A-Za-z0-9_\\\\]+)\(/', $file->getContents(), $m);
            foreach ($m[1] as $short) {
                preg_match('/^use\s+([A-Za-z0-9_\\\\]+\\\\'.preg_quote($short, '/').');/m', $file->getContents(), $use);
                $classes[] = $use[1] ?? 'App\\Jobs\\'.$short;
            }
        }

        $this->assertGreaterThanOrEqual(10, count($classes), 'call siteهای dispatchOtp پیدا نشدند');
        foreach (array_unique($classes) as $class) {
            $this->assertTrue(is_subclass_of($class, OtpJob::class), "$class باید OtpJob باشد (نگهبان پیامک H4)");
        }
    }

    public function test_user_based_jobs_resolve_the_recipient_phone(): void
    {
        $user = User::factory()->create(['phone' => '09126660001']);

        $this->assertSame('09126660001', (new SendPasswordResetCodeJob($user->id, '123456', null))->otpRecipient());
        $this->assertNull((new SendPasswordResetCodeJob(999999, '123456', null))->otpRecipient());
        $this->assertSame(0, DB::table('jobs')->count());
    }
}
