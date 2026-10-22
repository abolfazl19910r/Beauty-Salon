<?php

namespace Tests\Feature\Marketing;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WinbackCampaign;
use App\Models\WinbackCampaignRecipient;
use App\Models\WinbackMessage;
use App\Services\Marketing\WinbackCampaignService;
use App\Services\Plans\FeatureResolver;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * بسته‌ی ج «ارسال دستی به مشتری‌های قدیمی» (تصمیم ۱۳: حداکثر ۲ سال؛ ۱۴: ۲ کمپین در ۳۰ روز، ۵۰۰ گیرنده): انتخاب گیرنده، پیش‌نمایش و
 * قاعده‌ی ۲۰٪، ساعت مجاز، دسته‌ی ۲۰تایی، توقف/ادامه/لغو بدون پیامک تکراری، کد تخفیف فقط بعد از ارسال موفق، نتیجه، امکان پلن.
 */
class WinbackCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private Specialist $specialist;

    /** @var list<array{0: string, 1: string}> */
    private array $sent = [];

    private int $phone = 1000;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-14 08:00:00', 'UTC')); // ۱۱:۳۰ تهران
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->update(['sms_name' => 'رزگلد', 'sms_quota_per_month' => 1000, 'subscription_ends_at' => now()->addMonth(), 'is_suspended' => false]);
        $this->specialist = Specialist::factory()->create();

        $sent = &$this->sent;
        app()->instance(SMSService::class, new class($sent) extends SMSService
        {
            public function __construct(private array &$log)
            {
                parent::__construct();
            }

            public function send(string $mobile, string $message, ?int $salonId = null, ?Feature $feature = null): bool
            {
                $ok = parent::send($mobile, $message, $salonId, $feature);
                if ($ok) {
                    $this->log[] = [$mobile, $message];
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

    private function service(): WinbackCampaignService
    {
        return app(WinbackCampaignService::class);
    }

    /** مشتری با آخرین نوبت انجام‌شده N روز پیش */
    private function customer(int $daysAgo, array $attributes = [], ?int $serviceId = null): User
    {
        $user = User::factory()->create($attributes + ['user_type' => 'customer', 'salon_id' => $this->salon->id, 'phone' => '0912000'.($this->phone++), 'name' => 'مشتری']);
        Booking::factory()->create(array_filter([
            'user_id' => $user->id, 'status' => 'completed', 'specialist_id' => $this->specialist->id,
            'booking_time' => now()->subDays($daysAgo)->subMinutes($this->phone), 'service_id' => $serviceId,
        ]));

        return $user;
    }

    private function filters(array $extra = []): array
    {
        return $extra + ['last_visit_from_days' => 90, 'last_visit_to_days' => 730];
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function campaign(array $data = []): WinbackCampaign
    {
        return $this->service()->create($this->salon, $this->admin(), $this->filters($data) + [
            'name' => 'قدیمی‌ها', 'message' => 'دلمان تنگ شده! {تخفیف}نوبت: {لینک_رزرو} {سالن} لغو: {لینک_انصراف}', 'discount_valid_days' => 14,
        ]);
    }

    private function sendDue(): int
    {
        return array_sum($this->service()->runDue());
    }

    public function test_recipients_follow_the_age_window_cooldown_opt_out_and_future_booking_rules(): void
    {
        $ok = $this->customer(200);
        $this->customer(60);                                   // هنوز در پنجره‌ی پیگیری خودکار
        $this->customer(800);                                  // بیشتر از ۲ سال (تصمیم ۱۳)
        $this->customer(300, ['marketing_sms_opted_out_at' => now()->subDay()]);
        $booked = $this->customer(250);
        Booking::factory()->create(['user_id' => $booked->id, 'status' => 'confirmed', 'specialist_id' => $this->specialist->id, 'booking_time' => now()->addDays(3)]);
        $texted = $this->customer(400);
        WinbackMessage::create(['salon_id' => $this->salon->id, 'user_id' => $texted->id, 'last_visit_at' => now()->subDays(400), 'sent_at' => now()->subDays(30)]);
        $old = $this->customer(500);
        WinbackMessage::create(['salon_id' => $this->salon->id, 'user_id' => $old->id, 'last_visit_at' => now()->subDays(500), 'sent_at' => now()->subDays(120)]);

        $other = Salon::factory()->create(['slug' => 'other-'.uniqid()]);
        app(CurrentSalon::class)->withSalon($other, fn () => Booking::factory()->create([
            'user_id' => User::factory()->create(['user_type' => 'customer', 'salon_id' => $other->id, 'phone' => '09129999999'])->id,
            'status' => 'completed', 'booking_time' => now()->subDays(200),
            'specialist_id' => Specialist::factory()->create(['salon_id' => $other->id])->id,
        ]));

        $ids = $this->service()->candidates($this->salon, $this->filters())->pluck('id')->all();

        $this->assertSame([$ok->id, $old->id], $ids, 'جدیدترها اول؛ خنک‌شدن ۹۰ روزه، انصراف، نوبت آینده، بازه و سالن دیگر بیرون');
    }

    public function test_service_and_minimum_visit_filters(): void
    {
        $laser = BeautyService::factory()->create();
        $a = $this->customer(200, [], $laser->id);
        $hair = BeautyService::factory()->create();
        $b = $this->customer(200, [], $hair->id);
        Booking::factory()->create(['user_id' => $b->id, 'service_id' => $hair->id, 'status' => 'completed', 'specialist_id' => $this->specialist->id, 'booking_time' => now()->subDays(300)]);

        $this->assertSame([$a->id], $this->service()->candidates($this->salon, $this->filters(['service_ids' => [$laser->id]]))->pluck('id')->all());
        $this->assertSame([$b->id], $this->service()->candidates($this->salon, $this->filters(['min_visits' => 2]))->pluck('id')->all());
    }

    public function test_preview_counts_parts_and_keeps_a_fifth_of_the_quota(): void
    {
        foreach (range(1, 5) as $i) {
            $this->customer(100 + $i);
        }
        $this->salon->update(['sms_quota_per_month' => 5]); // ۲۰٪ = ۱ ← فقط ۴ قطعه جا

        $preview = $this->service()->preview($this->salon, $this->filters(), 'سلام {مشتری} {لینک_رزرو} لغو: {لینک_انصراف}');

        $this->assertSame(5, $preview['count']);
        $this->assertSame(1, $preview['reserve']);
        $this->assertStringContainsString('arisheh.ir/b/u9z1ab', $preview['sample']);
        $this->assertSame(intdiv(4, $preview['parts']), $preview['allowed']);
        $this->assertGreaterThan(0, $preview['buy_parts']);
    }

    public function test_limits_one_active_two_per_thirty_days_and_unsubscribe_link_required(): void
    {
        $this->customer(200);
        $this->assertNotNull($this->service()->messageError('بیایید {لینک_رزرو}'));
        $this->assertNotNull($this->service()->messageError('بیایید {لینک_انصراف} {ناشناخته}'));

        $first = $this->campaign();
        $this->assertStringContainsString('کمپین فعال', $this->service()->limitsError($this->salon));

        $this->service()->cancel($first, $this->admin()); // لغو بدون هیچ پیامکی شمرده نمی‌شود
        $this->assertNull($this->service()->limitsError($this->salon));
        $second = $this->campaign();
        $this->sendDue();
        $this->assertSame('done', $second->fresh()->status);

        $this->customer(210);
        $third = $this->campaign();
        $this->sendDue();
        $this->assertStringContainsString('۳۰ روز', $this->service()->limitsError($this->salon));
        $this->assertSame(1, $third->fresh()->sent);
    }

    public function test_sends_in_batches_of_twenty_only_inside_tehran_hours_and_never_twice(): void
    {
        foreach (range(1, 25) as $i) {
            $this->customer(100 + $i);
        }
        $campaign = $this->campaign();
        $this->assertSame(25, $campaign->targeted);

        Carbon::setTestNow(Carbon::parse('2026-10-14 17:00:00', 'UTC')); // ۲۰:۳۰ تهران
        $this->assertSame(0, $this->sendDue());

        Carbon::setTestNow(Carbon::parse('2026-10-15 06:00:00', 'UTC')); // ۰۹:۳۰ تهران
        $this->assertSame(20, $this->sendDue());
        $this->assertSame(5, $this->sendDue());
        $this->assertSame(0, $this->sendDue());

        $campaign->refresh();
        $this->assertSame('done', $campaign->status);
        $this->assertSame(25, $campaign->sent);
        $this->assertCount(25, $this->sent);
        $this->assertCount(25, array_unique(array_column($this->sent, 0)));
    }

    public function test_discount_code_is_created_only_after_a_successful_send(): void
    {
        $customer = $this->customer(200);
        $campaign = $this->campaign(['discount_type' => 'percentage', 'discount_amount' => 20, 'discount_valid_days' => 10]);

        $this->assertSame(0, DiscountCode::count());
        $this->sendDue();

        $code = DiscountCode::sole();
        $this->assertSame($customer->id, (int) $code->user_id);
        $this->assertSame(1, (int) $code->max_uses);
        $this->assertStringContainsString($code->code, $this->sent[0][1]);
        $this->assertSame($code->id, (int) WinbackCampaignRecipient::sole()->discount_code_id);
        $this->assertStringContainsString('20٪', $this->sent[0][1]);
    }

    public function test_rechecks_before_each_text_and_pauses_on_quota(): void
    {
        $a = $this->customer(200);
        $b = $this->customer(210);
        $c = $this->customer(220);
        $campaign = $this->campaign();

        $a->forceFill(['marketing_sms_opted_out_at' => now()])->save();
        Booking::factory()->create(['user_id' => $b->id, 'status' => 'confirmed', 'specialist_id' => $this->specialist->id, 'booking_time' => now()->addDays(2)]);
        $this->salon->update(['sms_quota_per_month' => 1]); // ۲۰٪ = ۱ ← جایی نیست

        $this->assertSame(0, $this->sendDue());
        $campaign->refresh();
        $this->assertSame('paused', $campaign->status);
        $this->assertSame('quota', $campaign->pause_reason);
        $this->assertSame(2, $campaign->skipped);
        $this->assertSame('pending', WinbackCampaignRecipient::where('user_id', $c->id)->value('status'));

        $this->salon->update(['sms_quota_per_month' => 1000]);
        $this->assertSame(0, $this->sendDue(), 'متوقف تا مدیر ادامه دهد');
        $this->assertTrue($this->service()->resume($campaign));
        $this->assertSame(1, $this->sendDue());
        $this->assertSame('done', $campaign->fresh()->status);
        $this->assertSame([$c->phone], array_column($this->sent, 0));
    }

    public function test_cancel_stops_the_rest(): void
    {
        foreach (range(1, 3) as $i) {
            $this->customer(100 + $i);
        }
        $campaign = $this->campaign();
        $this->service()->cancel($campaign, $this->admin());

        $this->assertSame(0, $this->sendDue());
        $this->assertSame(3, $campaign->fresh()->skipped);
        $this->assertSame(3, WinbackCampaignRecipient::where('skip_reason', 'cancelled')->count());
    }

    public function test_results_count_bookings_within_thirty_days_and_unsubscribes(): void
    {
        $back = $this->customer(200);
        $gone = $this->customer(210);
        $campaign = $this->campaign();
        $this->sendDue();

        $this->travel(3)->days();
        $service = BeautyService::factory()->create(['price' => 450000]);
        Booking::factory()->create(['user_id' => $back->id, 'status' => 'confirmed', 'service_id' => $service->id, 'specialist_id' => $this->specialist->id, 'booking_time' => now()->addDays(2)]);
        $gone->forceFill(['marketing_sms_opted_out_at' => now()])->save();

        $results = $this->service()->results($campaign->fresh());
        $this->assertSame(1, $results['booked']);
        $this->assertSame(450000.0, $results['revenue']);
        $this->assertSame(1, $results['opted_out']);
    }

    public function test_campaign_pauses_when_the_plan_loses_the_feature(): void
    {
        $this->customer(200);
        $campaign = $this->campaign();
        $plan = Plan::create(['key' => 'p'.uniqid(), 'name' => 'پایه', 'price_1m' => 990000, 'sms_quota_per_month' => 1000]);
        $plan->syncFeatures(array_diff(Feature::keys(), ['winback_manual']));
        $this->salon->update(['plan_id' => $plan->id]);
        app(FeatureResolver::class)->forget($this->salon->id);

        $this->assertSame(0, $this->sendDue());
        $this->assertSame('feature', $campaign->fresh()->pause_reason);
        $this->assertSame([], $this->sent);
    }

    // ─── پنل مدیر ─────────────────────────────────────────────────

    public function test_admin_previews_then_starts_a_campaign_with_explicit_consent(): void
    {
        $this->customer(200);
        $this->customer(300);
        $admin = $this->admin();
        $form = ['name' => 'پاییز', 'last_visit_from_days' => '۹۰', 'last_visit_to_days' => 730, 'max_recipients' => 500, 'discount_valid_days' => 14,
            'message' => 'برگردید {لینک_رزرو} لغو: {لینک_انصراف}'];

        $this->actingAs($admin)->get(route('admin.winback.campaigns.create'))->assertOk();
        $this->post(route('admin.winback.campaigns.store'), $form + ['action' => 'preview'])->assertOk()->assertSee('پیش‌نمایش')->assertSee('arisheh.ir/b/u9z1ab');
        $this->assertSame(0, WinbackCampaign::count());

        $this->post(route('admin.winback.campaigns.store'), $form + ['action' => 'start'])->assertSessionHasErrors('confirm_marketing');
        $this->post(route('admin.winback.campaigns.store'), $form + ['action' => 'start', 'confirm_marketing' => 1])->assertRedirect();
        $campaign = WinbackCampaign::sole();
        $this->assertSame(2, $campaign->targeted);

        $this->get(route('admin.winback.campaigns.show', $campaign))->assertOk()->assertSee('پاییز');
        $this->post(route('admin.winback.campaigns.pause', $campaign))->assertSessionHas('success');
        $this->assertSame('paused', $campaign->fresh()->status);
        $this->post(route('admin.winback.campaigns.cancel', $campaign))->assertSessionHas('success');
        $this->get(route('admin.winback.campaigns.index'))->assertOk()->assertSee('لغوشده');
    }

    public function test_admin_form_rejects_more_than_two_years_and_the_automatic_window(): void
    {
        $this->actingAs($this->admin())->post(route('admin.winback.campaigns.store'), [
            'name' => 'x', 'last_visit_from_days' => 30, 'last_visit_to_days' => 900, 'max_recipients' => 600, 'discount_valid_days' => 14,
            'message' => 'سلام {لینک_انصراف}', 'action' => 'preview',
        ])->assertSessionHasErrors(['last_visit_from_days', 'last_visit_to_days', 'max_recipients']);
    }

    public function test_pages_are_locked_without_the_feature_and_other_salons_campaigns_are_invisible(): void
    {
        $other = Salon::factory()->create(['slug' => 'o-'.uniqid()]);
        $foreign = app(CurrentSalon::class)->withSalon($other, function () use ($other) {
            $c = new WinbackCampaign(['name' => 'دیگر', 'last_visit_from_days' => 90, 'last_visit_to_days' => 200, 'message' => '{لینک_انصراف}', 'scheduled_at' => now(), 'max_recipients' => 10]);
            $c->salon_id = $other->id;
            $c->save();

            return $c;
        });
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.winback.campaigns.show', $foreign->id))->assertNotFound();

        $plan = Plan::create(['key' => 'p'.uniqid(), 'name' => 'پایه', 'price_1m' => 990000, 'sms_quota_per_month' => 1000]);
        $plan->syncFeatures(array_diff(Feature::keys(), ['winback_manual']));
        $this->salon->update(['plan_id' => $plan->id]);
        app(FeatureResolver::class)->forget($this->salon->id);

        $this->get(route('admin.winback.campaigns.index'))->assertRedirect(route('admin.billing.index'));
        $this->get(route('admin.winback.index'))->assertOk()->assertSee('data-feature-locked="winback_manual"', false);
    }
}
