<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\SalonSmsUsage;
use App\Models\SmsCreditPurchase;
use App\Models\User;
use App\Notifications\Admin\Attention\AttentionRequiredNotification;
use App\Services\Admin\AttentionNotifier;
use App\Services\Sms\SmsQuotaService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ⭐ بخش‌هایی که داده‌شان پیاده شده بود ولی در پنل ابرمدیر دیده نمی‌شد (۲۰۲۶-۱۰-۱۵): پیامک سالن‌ها و خرید بسته‌ها،
 * پرداخت‌های پلتفرمِ نیازمند بررسی (دیگر در پنل مدیر سالن نیستند)، کارهای ناموفق صف، وضعیت ربات، هشدارهای داشبورد.
 */
class PlatformOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['is_admin' => true]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        // factory مدیر is_admin را مالک سالن جاری می‌کند؛ ابرمدیر مال هیچ سالنی نیست و جای مالک واقعی تست را نگیرد
        $this->super->salons()->detach();
    }

    private function platformAttention(string $purpose = 'subscription'): PaymentTransaction
    {
        $tx = PaymentTransaction::create([
            'salon_id' => app(CurrentSalon::class)->get()->id, 'driver' => 'zarinpal', 'purpose' => $purpose, 'amount_rial' => 41500000,
            'token' => (string) random_int(1000, 999999), 'callback_url' => 'x', 'status' => 'failed',
            'verify_response' => ['unanswered' => true],
        ]);
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['needs_attention' => true]);

        return $tx->fresh();
    }

    public function test_sms_page_shows_usage_of_every_salon_and_pack_revenue(): void
    {
        $salon = app(CurrentSalon::class)->get();
        SalonSmsUsage::create(['salon_id' => $salon->id, 'period' => app(SmsQuotaService::class)->currentPeriod(), 'used_count' => 1234, 'credit_used' => 56, 'otp_count' => 78]);
        SmsCreditPurchase::create(['salon_id' => $salon->id, 'parts' => 1000, 'amount' => 320000, 'source' => 'online', 'status' => 'paid', 'ref_id' => 'REF-777', 'paid_at' => now()]);
        SmsCreditPurchase::create(['salon_id' => $salon->id, 'parts' => 200, 'amount' => 0, 'source' => 'grant', 'status' => 'paid', 'paid_at' => now(), 'created_by' => $this->super->id]);

        $this->actingAs($this->super)->get(route('superadmin.sms.index'))->assertOk()
            ->assertSee($salon->name)->assertSee('1,234')->assertSee('REF-777')
            ->assertSee('320,000')->assertSee('اعطای دستی ('.$this->super->name.')', false);

        $this->actingAs($this->super)->get(route('superadmin.sms.index', ['source' => 'grant']))->assertOk()->assertDontSee('REF-777');
    }

    public function test_platform_payments_needing_attention_live_in_the_super_admin_panel_only(): void
    {
        $tx = $this->platformAttention();
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($this->super)->get(route('superadmin.payment-attention.index'))->assertOk()
            ->assertSee('خرید/تمدید اشتراک')->assertSee('4,150,000');
        $this->actingAs($owner)->get(route('admin.payment-attention.index'))->assertOk()->assertDontSee('#'.$tx->id.' ', false);
        $this->actingAs($owner)->post(route('admin.payment-attention.resolve', $tx->id), ['action' => 'resolved', 'note' => 'تست'])->assertNotFound();

        $this->actingAs($this->super)->post(route('superadmin.payment-attention.resolve', $tx->id), ['note' => 'مبلغ گرفته نشده بود'])
            ->assertSessionHas('success');
        $fresh = $tx->fresh();
        $this->assertFalse($fresh->needs_attention);
        $this->assertSame('superadmin', $fresh->verify_response['manual_resolution']['panel']);
        $this->assertSame('مبلغ گرفته نشده بود', $fresh->verify_response['manual_resolution']['note']);
    }

    public function test_platform_payments_notify_super_admins_not_salon_owners(): void
    {
        Notification::fake();
        $tx = $this->platformAttention('sms_pack');
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['attention_notified_at' => null]);

        app(AttentionNotifier::class)->paymentFlagged($tx->id);

        Notification::assertNotSentTo(User::query()->get(), AttentionRequiredNotification::class);
        // به‌جایش «صندوق اعلان» ابرمدیر
        Notification::assertSentTo($this->super, \App\Notifications\SuperAdmin\PlatformAlertNotification::class,
            fn ($n) => $n->kind === 'payment_attention');
    }

    public function test_failed_jobs_are_listed_and_can_be_forgotten(): void
    {
        $uuid = (string) Str::uuid();
        DB::table('failed_jobs')->insert(['uuid' => $uuid, 'connection' => 'database', 'queue' => 'sms',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SendSmsJob']), 'exception' => "RuntimeException: Kavenegar timeout\n#0 trace", 'failed_at' => now()]);

        $this->actingAs($this->super)->get(route('superadmin.failed-jobs.index'))->assertOk()
            ->assertSee('SendSmsJob')->assertSee('Kavenegar timeout')->assertDontSee('#0 trace');
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()->assertSee('کار ناموفق در صف');

        $this->actingAs($this->super)->delete(route('superadmin.failed-jobs.forget', $uuid))->assertSessionHas('success');
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
    }

    public function test_dashboard_shows_month_revenue_and_settings_show_bot_status(): void
    {
        config(['services.bot.enabled' => true]);
        $salon = app(CurrentSalon::class)->get();
        SmsCreditPurchase::create(['salon_id' => $salon->id, 'parts' => 1000, 'amount' => 320000, 'source' => 'online', 'status' => 'paid', 'paid_at' => now()]);
        $this->platformAttention();
        config(['services.bale.bot_token' => 'x', 'services.bale.username' => 'mahru_bot', 'services.telegram.bot_token' => null]);

        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()
            ->assertSee('درآمد بسته‌ی پیامک این ماه')->assertSee('320,000')->assertSee('پرداخت پلتفرم نیازمند بررسی');

        $html = $this->actingAs($this->super)->get(route('superadmin.settings.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('data-bot-status', $html);
        $this->assertStringContainsString('@mahru_bot', $html);
    }

    public function test_salon_admins_cannot_open_the_new_pages(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        foreach (['superadmin.sms.index', 'superadmin.payment-attention.index', 'superadmin.failed-jobs.index'] as $name) {
            $this->assertContains($this->actingAs($owner)->get(route($name))->status(), [302, 403, 404], $name);
        }
    }
}
