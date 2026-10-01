<?php

namespace Tests\Feature\Sms;

use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Models\SmsCreditPurchase;
use App\Models\User;
use App\Notifications\Sms\SmsQuotaWarningNotification;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * بسته‌ی پیامک و اعتبار سالن (تصمیم‌های ۲۰۲۶-۰۹-۳۰): مصرف اول از سهمیه‌ی ماه بعد از اعتبار؛ اعتبار منقضی نمی‌شود؛ قیمت =
 * قطعه × ۳۲۰ بدون تخفیف؛ خرید آنلاین از صورتحساب با درگاه پلتفرم؛ اعطای دستی سوپرادمین؛ هشدار ۸۰٪ یک بار در ماه.
 */
class SmsCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SMSService::class, new SMSService);
    }

    private function salonAt(int $quota, int $used, int $credit): Salon
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => $quota]);
        DB::table('salons')->where('id', $salon->id)->update(['sms_credit' => $credit]);
        SalonSmsUsage::create(['salon_id' => $salon->id, 'period' => app(SmsQuotaService::class)->currentPeriod(), 'used_count' => $used, 'warned_at' => now()]);

        return $salon;
    }

    private function state(Salon $salon): array
    {
        $usage = SalonSmsUsage::where('salon_id', $salon->id)->first();

        return [(int) $usage->used_count, (int) $usage->credit_used, (int) DB::table('salons')->where('id', $salon->id)->value('sms_credit')];
    }

    public function test_the_monthly_quota_is_used_first_then_the_purchased_credit(): void
    {
        $salon = $this->salonAt(10, 9, 5);

        $this->assertTrue(app(SMSService::class)->send('09121234567', str_repeat('س', 140), $salon->id)); // ۳ قطعه: ۱ از ماه، ۲ از اعتبار

        $this->assertSame([10, 2, 3], $this->state($salon));
    }

    public function test_a_message_bigger_than_quota_left_plus_credit_is_blocked_and_nothing_is_taken(): void
    {
        $salon = $this->salonAt(10, 9, 1);

        $this->assertFalse(app(SMSService::class)->send('09121234567', str_repeat('س', 140), $salon->id));

        $this->assertSame([9, 0, 1], $this->state($salon));
    }

    public function test_credit_does_not_expire_with_the_month(): void
    {
        $salon = $this->salonAt(10, 10, 4);
        $this->travel(1)->months();

        app(SMSService::class)->send('09121234567', 'کوتاه', $salon->id);

        $this->assertSame(4, app(SmsQuotaService::class)->credit($salon)); // ماه نو: از سهمیه‌ی تازه، اعتبار دست‌نخورده
    }

    public function test_salon_admins_are_warned_once_when_the_month_passes_eighty_percent(): void
    {
        Notification::fake();
        $salon = Salon::factory()->create(['sms_quota_per_month' => 10]);
        $owner = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        DB::table('salon_admins')->insert(['salon_id' => $salon->id, 'user_id' => $owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);

        foreach (range(1, 7) as $i) {
            app(SMSService::class)->send('09121234567', 'کوتاه', $salon->id);
        }
        Notification::assertNothingSent();

        app(SMSService::class)->send('09121234567', 'کوتاه', $salon->id); // ۸ از ۱۰
        app(SMSService::class)->send('09121234567', 'کوتاه', $salon->id);

        Notification::assertSentToTimes($owner, SmsQuotaWarningNotification::class, 1);
    }

    public function test_pack_prices_are_parts_times_the_part_price_without_any_discount(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $packs = $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()->viewData('smsPacks');

        $this->assertSame([[1000, 320000], [2500, 800000], [5000, 1600000]], array_map(fn ($p) => [$p['parts'], $p['price']], $packs));
    }

    public function test_buying_a_pack_online_adds_the_credit_once(): void
    {
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'SMSAUTH1']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF77']], 200),
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $salon = app(CurrentSalon::class)->get();

        $this->actingAs($admin)->post(route('admin.billing.sms-pack.purchase'), ['parts' => 2500])->assertRedirect();
        $purchase = SmsCreditPurchase::where('salon_id', $salon->id)->firstOrFail();
        $this->assertSame([2500, 800000, 'pending'], [$purchase->parts, $purchase->amount, $purchase->status]);

        $callback = route('admin.billing.sms-pack.callback', ['purchase' => $purchase->id, 'Authority' => 'SMSAUTH1', 'Status' => 'OK']);
        $this->actingAs($admin)->get($callback)->assertRedirect(route('admin.billing.index'));
        $this->actingAs($admin)->get($callback); // بازگشت تکراری

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame(2500, app(SmsQuotaService::class)->credit($salon));
    }

    public function test_only_the_configured_pack_sizes_can_be_bought(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.billing.sms-pack.purchase'), ['parts' => 999999])->assertSessionHasErrors('parts');
        $this->assertSame(0, SmsCreditPurchase::count());
    }

    public function test_a_super_admin_can_grant_credit_and_it_is_recorded(): void
    {
        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $salon = Salon::factory()->create();

        $this->actingAs($super)->post("/superadmin/salons/{$salon->id}/sms-credit", ['parts' => 300, 'note' => 'جبران قطعی'])
            ->assertRedirect(route('superadmin.salons.edit', $salon));

        $this->assertSame(300, app(SmsQuotaService::class)->credit($salon));
        $this->assertDatabaseHas('sms_credit_purchases', ['salon_id' => $salon->id, 'parts' => 300, 'amount' => 0, 'source' => 'grant', 'status' => 'paid', 'created_by' => $super->id]);
    }

    public function test_a_salon_admin_cannot_grant_credit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salon = app(CurrentSalon::class)->get();

        $this->actingAs($admin)->post("/superadmin/salons/{$salon->id}/sms-credit", ['parts' => 300]);

        $this->assertSame(0, app(SmsQuotaService::class)->credit($salon));
    }
}
