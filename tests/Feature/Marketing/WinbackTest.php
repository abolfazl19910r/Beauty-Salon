<?php

namespace Tests\Feature\Marketing;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WinbackMessage;
use App\Models\WinbackSetting;
use App\Services\Marketing\WinbackService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Sms\SmsTemplateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * پیگیری خودکار مشتریانی که مدتی نیامده‌اند (تصمیم ۲۰۲۶-۱۰-۱۴): پیامک + کد تخفیف اختیاری، یک بار در هر دوره‌ی غیبت، از سهمیه‌ی
 * سالن، انصراف با لینک امضاشده‌ی پیامک یا پروفایل.
 */
class WinbackTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    /** @var list<array{0: string, 1: string, 2: ?int}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 11:00:00');
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->update(['sms_name' => 'رزگلد', 'sms_quota_per_month' => 1000, 'subscription_ends_at' => now()->addMonth(), 'is_suspended' => false]);

        $sent = &$this->sent;
        app()->instance(SMSService::class, new class($sent) extends SMSService
        {
            public function __construct(private array &$log)
            {
                parent::__construct();
            }

            public function send(string $mobile, string $message, ?int $salonId = null): bool
            {
                $ok = parent::send($mobile, $message, $salonId);
                if ($ok) {
                    $this->log[] = [$mobile, $message, $salonId];
                }

                return $ok;
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enable(array $attributes = []): WinbackSetting
    {
        return WinbackSetting::create($attributes + ['salon_id' => $this->salon->id, 'enabled' => true, 'lapse_days' => 60]);
    }

    private function customer(string $phone = '09121234567', ?Salon $salon = null): User
    {
        $salon ??= $this->salon;

        return User::factory()->create(['phone' => $phone, 'user_type' => 'customer', 'salon_id' => $salon->id, 'name' => 'مریم']);
    }

    private function visit(User $customer, string $when, string $status = 'completed', ?Salon $salon = null): Booking
    {
        $salon ??= $this->salon;

        return app(CurrentSalon::class)->withSalon($salon, fn () => Booking::factory()->create([
            'salon_id' => $salon->id, 'user_id' => $customer->id, 'status' => $status,
            'specialist_id' => Specialist::factory()->create(['salon_id' => $salon->id])->id,
            'booking_time' => Carbon::parse($when),
        ]));
    }

    private function runWinback(): array
    {
        $this->artisan('customers:winback')->assertSuccessful();

        return $this->sent;
    }

    public function test_a_lapsed_customer_gets_one_message_per_absence(): void
    {
        $this->enable();
        $c = $this->customer();
        $this->visit($c, '2026-07-01 10:00');   // ۱۰۵ روز پیش؛ بیرون از پنجره
        $this->visit($c, '2026-08-10 10:00');   // آخرین مراجعه، ۶۵ روز پیش

        $this->runWinback();
        $this->assertCount(1, $this->sent);
        [$phone, $text, $salonId] = $this->sent[0];
        $this->assertSame(['09121234567', $this->salon->id], [$phone, $salonId]);
        $this->assertStringStartsWith('مدتی است شما را ندیده‌ایم! نوبت: ', $text);
        $this->assertMatchesRegularExpression('#لغو: \S+/b/[a-z0-9]{6}$#u', $text);
        $this->assertStringContainsString('رزگلد', $text);
        $this->assertSame('2026-08-10 10:00:00', WinbackMessage::sole()->last_visit_at->format('Y-m-d H:i:s'));

        // فردا دوباره نه
        Carbon::setTestNow('2026-10-15 11:00:00');
        $this->runWinback();
        $this->assertCount(1, $this->sent);

        // برگشت، دوباره غایب شد → دوره‌ی تازه
        $this->visit($c, '2026-10-20 10:00');
        Carbon::setTestNow('2026-12-21 11:00:00');
        $this->salon->update(['subscription_ends_at' => now()->addMonth()]);
        $this->runWinback();
        $this->assertCount(2, $this->sent);
        $this->assertSame(2, WinbackMessage::count());
    }

    public function test_who_does_not_get_a_message(): void
    {
        $this->enable();
        $this->visit($this->customer('09120000001'), '2026-09-20 10:00');               // هنوز ۶۰ روز نشده
        $this->visit($this->customer('09120000002'), '2026-05-01 10:00');               // خیلی قدیمی، بیرون از پنجره‌ی ۳۰ روزه
        $upcoming = $this->customer('09120000003');
        $this->visit($upcoming, '2026-08-01 10:00');
        $this->visit($upcoming, '2026-10-20 10:00', 'confirmed');                        // نوبت آینده دارد
        $out = $this->customer('09120000004');
        $out->forceFill(['marketing_sms_opted_out_at' => now()])->save();
        $this->visit($out, '2026-08-01 10:00');                                          // انصراف داده
        $this->visit($this->customer('09120000005'), '2026-08-01 10:00', 'cancelled');  // هرگز نیامده

        $other = Salon::factory()->create(['slug' => 'other-wb', 'subscription_ends_at' => now()->addMonth()]);
        $this->visit($this->customer('09120000006', $other), '2026-08-01 10:00', 'completed', $other); // سالنی که روشن نکرده

        $this->runWinback();
        $this->assertSame([], $this->sent);
    }

    public function test_nothing_is_sent_while_disabled_or_suspended(): void
    {
        $setting = $this->enable(['enabled' => false]);
        $this->visit($this->customer(), '2026-08-01 10:00');
        $this->runWinback();
        $this->assertSame([], $this->sent);

        $setting->update(['enabled' => true]);
        $this->salon->update(['is_suspended' => true]);
        $this->runWinback();
        $this->assertSame([], $this->sent);
    }

    public function test_optional_personal_discount_code_without_the_separate_code_sms(): void
    {
        $this->enable(['discount_type' => 'percentage', 'discount_amount' => 15, 'discount_max_amount' => 200000, 'discount_valid_days' => 14]);
        $c = $this->customer();
        $this->visit($c, '2026-08-01 10:00');

        $this->runWinback();

        $code = DiscountCode::sole();
        $this->assertSame([$c->id, 1, 'percentage', '200000.00'], [$code->user_id, $code->max_uses, $code->type, $code->max_amount]);
        $this->assertSame('2026-10-28 23:59:59', $code->expires_at->format('Y-m-d H:i:s'));
        $this->assertCount(1, $this->sent, 'پیامک جدای «کد تخفیف صادر شد» نباید برود');
        $this->assertStringContainsString("کد {$code->code}: 15٪ تخفیف تا 1405/08/06.", $this->sent[0][1]);
        $this->assertSame($code->id, WinbackMessage::sole()->discount_code_id);
    }

    public function test_the_salons_own_text_is_used(): void
    {
        $this->enable(['discount_type' => 'fixed', 'discount_amount' => 50000]);
        SmsTemplateStore::save($this->salon->id, 'winback', 'دلمان تنگ شده! {تخفیف}{سالن} انصراف: {لینک_انصراف}');
        $this->visit($this->customer(), '2026-08-01 10:00');

        $this->runWinback();
        $this->assertMatchesRegularExpression('#^دلمان تنگ شده! کد BK[A-Z0-9]{5}: 50,000 تومان تخفیف تا 1405/08/06\. رزگلد انصراف: \S+/b/[a-z0-9]{6}$#u', $this->sent[0][1]);
    }

    public function test_twenty_percent_of_the_month_is_kept_for_booking_sms(): void
    {
        $this->enable();
        $this->salon->update(['sms_quota_per_month' => 10, 'sms_credit' => 0]);
        app(\App\Services\Sms\SmsQuotaService::class)->recordUsage($this->salon, 7); // ۳ مانده؛ پیامک ۲ قطعه‌ای جا ندارد (۲ تا باید بماند)
        $this->visit($this->customer('09120000001'), '2026-08-01 10:00');
        $this->visit($this->customer('09120000002'), '2026-08-02 10:00');

        $this->runWinback();
        $this->assertSame([], $this->sent);
        $this->assertSame(0, WinbackMessage::count());
        $this->assertSame(0, DiscountCode::count());
    }

    public function test_unsubscribe_link_from_the_sms(): void
    {
        $c = $this->customer();
        $url = WinbackService::unsubscribeUrl($this->salon, $c);

        $this->get($url)->assertOk()->assertSee('دیگر پیامک نفرست');
        $this->assertNull($c->fresh()->marketing_sms_opted_out_at, 'باز کردن لینک به‌تنهایی انصراف نیست');

        $this->get($url.'x')->assertForbidden();
        $this->post(\Illuminate\Support\Facades\URL::signedRoute('sms.unsubscribe.store', ['salon_slug' => $this->salon->slug, 'customer' => $c->id]))
            ->assertOk()->assertSee('انصراف ثبت شد');
        $this->assertNotNull($c->fresh()->marketing_sms_opted_out_at);

        $other = Salon::factory()->create(['slug' => 'other-unsub', 'subscription_ends_at' => now()->addMonth()]);
        $this->get(\Illuminate\Support\Facades\URL::signedRoute('sms.unsubscribe', ['salon_slug' => $other->slug, 'customer' => $c->id]))->assertNotFound();
    }

    public function test_customer_toggles_it_from_the_profile(): void
    {
        $c = $this->customer();
        $this->actingAs($c)->get(route('profile.edit'))->assertOk()->assertSee('پیامک‌های پیگیری و پیشنهاد سالن');

        $this->actingAs($c)->patch(route('profile.marketing-sms'), ['marketing_sms' => '0'])->assertRedirect(route('profile.edit'));
        $this->assertNotNull($c->fresh()->marketing_sms_opted_out_at);

        $this->actingAs($c)->patch(route('profile.marketing-sms'), ['marketing_sms' => '1']);
        $this->assertNull($c->fresh()->marketing_sms_opted_out_at);
    }

    public function test_admin_page_saves_and_validates_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);

        $this->actingAs($admin)->get(route('admin.winback.index'))->assertOk()->assertSee('پیگیری خودکار مشتریان غایب');

        $this->actingAs($admin)->put(route('admin.winback.update'), ['enabled' => '1', 'lapse_days' => 45, 'discount_type' => 'percentage', 'discount_amount' => '۱۲۰', 'discount_valid_days' => 14])
            ->assertSessionHasErrors('discount_amount');

        $this->actingAs($admin)->put(route('admin.winback.update'), ['enabled' => '1', 'lapse_days' => 45, 'discount_type' => 'percentage', 'discount_amount' => '۲۰', 'discount_max_amount' => '۱۰۰٬۰۰۰', 'discount_valid_days' => 10])
            ->assertRedirect(route('admin.winback.index'));

        $s = WinbackSetting::sole();
        $this->assertSame([true, 45, 'percentage', '20.00', '100000.00', 10], [$s->enabled, $s->lapse_days, $s->discount_type, $s->discount_amount, $s->discount_max_amount, $s->discount_valid_days]);

        $this->visit($this->customer(), '2026-08-15 10:00');
        $page = $this->actingAs($admin)->get(route('admin.winback.index'))->assertOk();
        $this->assertSame(1, $page->viewData('dueToday'));
        $page->assertSee('20٪ تخفیف تا');

        $this->actingAs($admin)->put(route('admin.winback.update'), ['enabled' => '0', 'lapse_days' => 60, 'discount_type' => '', 'discount_valid_days' => 14]);
        $this->assertSame([false, null, null], [WinbackSetting::sole()->enabled, WinbackSetting::sole()->discount_type, WinbackSetting::sole()->discount_amount]);
    }
}
