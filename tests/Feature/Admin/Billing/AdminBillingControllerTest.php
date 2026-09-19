<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». به docblock های AdminBillingController،
 * SubscriptionPaymentService و InvoiceService نگاه کن.
 *
 * الگوی لاگین: User::factory()->create(['is_admin' => true]) به‌طور خودکار به سالن پیش‌فرض
 * تست ('rasta'، فعال) وصل می‌شود (به docblock UserFactory::admin() نگاه کن) — همان سالنی که
 * TestCase::setUp() به‌عنوان CurrentSalon می‌بندد.
 */
class AdminBillingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function salonAdmin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    // ---------------------------------------------------------------------
    // index()
    // ---------------------------------------------------------------------

    public function test_index_shows_subscription_status_and_price_list(): void
    {
        $admin = $this->salonAdmin();

        $response = $this->actingAs($admin)->get(route('admin.billing.index'));

        $response->assertOk();
        $response->assertViewHas('salon');
        $response->assertViewHas('prices');
    }

    // ---------------------------------------------------------------------
    // purchase() — creates a pending invoice + redirects to the real gateway
    // ---------------------------------------------------------------------

    public function test_purchase_creates_a_pending_invoice_and_redirects_to_the_gateway(): void
    {
        Http::fake([
            '*request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'SUBAUTH1'],
            ], 200),
        ]);

        $admin = $this->salonAdmin();

        $response = $this->actingAs($admin)->post(route('admin.billing.purchase'), [
            'subscription_type' => '3m',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('SUBAUTH1', $response->headers->get('Location'));

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('online', $invoice->payment_method);
        $this->assertSame('3m', $invoice->subscription_type);
        $this->assertSame('SUBAUTH1', $invoice->authority);
        $this->assertSame($admin->id, $invoice->created_by);
    }

    public function test_purchase_uses_the_platform_global_merchant_id_never_the_salons_own(): void
    {
        // ⭐ رگرسیون‌گارد برای تمایز مستندشده: SubscriptionPaymentService (سالن → پلتفرم) باید
        // همیشه merchant_id سراسری را بفرستد، حتی اگر سالن merchant_id مخصوص خودش را هم داشته
        // باشد — برخلاف PaymentService (مشتری → سالن).
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['zarinpal_merchant_id' => 'salon-own-merchant-id']);

        Http::fake([
            '*request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'SUBAUTH2'],
            ], 200),
        ]);

        $admin = $this->salonAdmin();

        $this->actingAs($admin)->post(route('admin.billing.purchase'), ['subscription_type' => '1m']);

        Http::assertSent(function ($request) {
            return $request['merchant_id'] === config('services.zarinpal.merchant_id')
                && $request['merchant_id'] !== 'salon-own-merchant-id';
        });
    }

    public function test_purchase_shows_an_error_when_the_gateway_rejects_the_request(): void
    {
        Http::fake([
            '*request.json' => Http::response(['errors' => ['message' => 'invalid merchant']], 200),
        ]);

        $admin = $this->salonAdmin();

        $response = $this->actingAs($admin)
            ->from(route('admin.billing.index'))
            ->post(route('admin.billing.purchase'), ['subscription_type' => '1m']);

        $response->assertSessionHasErrors();
        $this->assertSame('failed', Invoice::first()->status);
    }

    // ---------------------------------------------------------------------
    // callback() — successful verification extends the salon's subscription
    // ---------------------------------------------------------------------

    public function test_callback_success_marks_invoice_paid_and_extends_subscription(): void
    {
        Http::fake([
            '*verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 'REF999'],
            ], 200),
        ]);

        $admin = $this->salonAdmin();
        $salon = app(CurrentSalon::class)->get();
        $originalEndsAt = $salon->subscription_ends_at->copy();

        $invoice = Invoice::factory()->create([
            'salon_id' => $salon->id,
            'subscription_type' => '1m',
            'status' => 'pending',
            'authority' => 'SUBAUTH3',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.callback', [
            'invoice' => $invoice->id,
            'Authority' => 'SUBAUTH3',
            'Status' => 'OK',
        ]));

        $response->assertRedirect(route('admin.billing.index'));
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('REF999', $invoice->ref_id);
        $this->assertNotNull($invoice->paid_at);

        $salon->refresh();
        $this->assertTrue($salon->subscription_ends_at->isSameDay($originalEndsAt->addMonth()));
    }

    public function test_callback_failure_marks_invoice_failed_without_touching_subscription(): void
    {
        $admin = $this->salonAdmin();
        $salon = app(CurrentSalon::class)->get();
        $originalEndsAt = $salon->subscription_ends_at->copy();

        $invoice = Invoice::factory()->create([
            'salon_id' => $salon->id,
            'status' => 'pending',
            'authority' => 'SUBAUTH4',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.callback', [
            'invoice' => $invoice->id,
            'Authority' => 'SUBAUTH4',
            'Status' => 'NOK',
        ]));

        $response->assertRedirect(route('admin.billing.index'));
        $this->assertSame('failed', $invoice->fresh()->status);
        $this->assertTrue($salon->fresh()->subscription_ends_at->isSameDay($originalEndsAt));
    }

    public function test_callback_rejects_an_authority_that_does_not_match_the_invoice(): void
    {
        $admin = $this->salonAdmin();
        $salon = app(CurrentSalon::class)->get();

        $invoice = Invoice::factory()->create([
            'salon_id' => $salon->id,
            'status' => 'pending',
            'authority' => 'REAL-AUTHORITY',
        ]);

        $this->actingAs($admin)->get(route('admin.billing.callback', [
            'invoice' => $invoice->id,
            'Authority' => 'TAMPERED-AUTHORITY',
            'Status' => 'OK',
        ]));

        $this->assertSame('failed', $invoice->fresh()->status);
    }

    public function test_callback_on_an_already_processed_invoice_does_not_double_extend(): void
    {
        Http::fake(); // any gateway call here would mean the idempotency guard failed

        $admin = $this->salonAdmin();
        $salon = app(CurrentSalon::class)->get();

        $invoice = Invoice::factory()->paid()->create([
            'salon_id' => $salon->id,
            'authority' => 'SUBAUTH5',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.callback', [
            'invoice' => $invoice->id,
            'Authority' => 'SUBAUTH5',
            'Status' => 'OK',
        ]));

        $response->assertRedirect(route('admin.billing.index'));
        Http::assertNothingSent();
    }
}
