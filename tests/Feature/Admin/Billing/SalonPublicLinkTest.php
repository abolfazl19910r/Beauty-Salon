<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Invoice;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ کارت «آدرس اختصاصی رزرو آنلاین سالن شما» (۲۰۲۶-۰۹-۲۳). قبل از این، آدرس عمومی سالن هیچ‌جای
 * پنل به مالکش نشون داده نمی‌شد. سوییت اصلی با CENTRAL_DOMAIN خالی اجرا می‌شه، پس آدرس اصلی
 * همون /s/{slug} است؛ حالت ساب‌دامین با phpunit.subdomain.xml در SubdomainRoutingTest پوشش داده شده.
 */
class SalonPublicLinkTest extends TestCase
{
    use RefreshDatabase;

    private function salonAdmin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_public_url_is_the_slash_s_slug_address_without_a_central_domain(): void
    {
        $salon = app(CurrentSalon::class)->get();

        $this->assertSame(url('/s/'.$salon->slug), $salon->publicUrl());
        $this->assertSame($salon->publicUrl(), $salon->legacyPublicUrl());
    }

    public function test_dashboard_shows_the_salon_public_link_card(): void
    {
        $salon = app(CurrentSalon::class)->get();

        $this->actingAs($this->salonAdmin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('آدرس اختصاصی رزرو آنلاین سالن شما')
            ->assertSee(url('/s/'.$salon->slug), false)
            ->assertSee('data-copy-salon-link', false);
    }

    public function test_billing_page_shows_the_salon_public_link_card(): void
    {
        $salon = app(CurrentSalon::class)->get();

        $this->actingAs($this->salonAdmin())->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee(url('/s/'.$salon->slug), false);
    }

    public function test_expired_salon_card_warns_that_the_link_is_offline(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['subscription_ends_at' => now()->subDay()]);

        $this->actingAs($this->salonAdmin())->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee('غیرفعال تا تمدید اشتراک');
    }

    public function test_trial_salon_card_shows_days_left(): void
    {
        config(['billing.trial_sms_quota' => 300]);
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['trial_ends_at' => now()->addDays(10), 'subscription_ends_at' => now()->addDays(10)]);

        $this->actingAs($this->salonAdmin())->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee('دوره‌ی آزمایشی')
            ->assertSee('دوره‌ی آزمایشی رایگان');
    }

    public function test_successful_payment_message_includes_the_salon_public_url(): void
    {
        Http::fake([
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF1']], 200),
        ]);

        $salon = app(CurrentSalon::class)->get();
        $invoice = Invoice::factory()->create([
            'salon_id' => $salon->id,
            'subscription_type' => '1m',
            'status' => 'pending',
            'authority' => 'AUTHLINK',
        ]);

        $this->actingAs($this->salonAdmin())->get(route('admin.billing.callback', [
            'invoice' => $invoice->id,
            'Authority' => 'AUTHLINK',
            'Status' => 'OK',
        ]))->assertSessionHas('success', fn ($msg) => str_contains($msg, url('/s/'.$salon->slug)));
    }
}
