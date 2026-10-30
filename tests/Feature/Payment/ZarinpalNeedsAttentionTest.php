<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۵ (۲۰۲۶-۱۰-۳۰): پاسخ verify زرین‌پال ‎-50 (مبلغ ناهمخوان) یا ‎-52 (خطای غیرمنتظره) یعنی شاید پول از مشتری کسر
 * شده باشد ← تراکنش needs_attention (صفحه‌ی «پرداخت‌های نیازمند بررسی»)، نه failed بی‌صدا. ‎-51 (پرداخت ناموفق) همان failed عادی است.
 */
class ZarinpalNeedsAttentionTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->salon->paymentGateways()->delete();
        $this->salon->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => '11111111-2222-3333-4444-555555555555'], 'priority' => 1, 'is_active' => true]);
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    private function payAndComeBack(int $verifyCode): array
    {
        Http::fake([
            '*/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'S000000000000000000000000000000ab12', 'fee' => 0], 'errors' => []]),
            '*/verify.json' => Http::response(['data' => [], 'errors' => ['message' => 'x', 'code' => $verifyCode, 'validations' => []]], 422),
        ]);
        $user = User::factory()->create(['phone' => '0912'.random_int(1000000, 9999999)]);
        $booking = $this->booking($user);

        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect();
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?Authority=S000000000000000000000000000000ab12&Status=OK")->headers->get('Location');
        $page = $this->actingAs($user)->get($location);

        return [$tx->fresh(), $page];
    }

    public function test_amount_mismatch_and_unexpected_error_need_a_human(): void
    {
        foreach ([-50, -52] as $code) {
            [$tx, $page] = $this->payAndComeBack($code);

            $this->assertTrue($tx->needs_attention, "کد {$code}");
            $this->assertSame('failed', $tx->status);
            $page->assertSessionHas('error', fn ($message) => str_contains($message, 'بررسی'));

            $this->actingAs($this->owner)->get(route('admin.payment-attention.index'))->assertOk()->assertSee('#'.$tx->id);
            PaymentTransaction::query()->delete();
        }
    }

    public function test_a_plain_failed_payment_is_not_flagged(): void
    {
        [$tx] = $this->payAndComeBack(-51);

        $this->assertFalse($tx->needs_attention);
        $this->assertSame('failed', $tx->status);
    }

    public function test_the_driver_marks_the_result_for_attention(): void
    {
        Http::fake(['*' => Http::response(['data' => [], 'errors' => ['code' => -52, 'message' => 'Oops', 'validations' => []]], 422)]);

        $result = (new \App\Payments\Drivers\ZarinpalDriver(['merchant_id' => 'x'], true))
            ->verify(new \App\Payments\GatewayVerifyRequest(600000, ['Authority' => 'S1', 'Status' => 'OK']));

        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['needs_attention'] ?? false);
        $this->assertStringContainsString('کسر شده باشد', $result->message);
    }

    public function test_the_reconcile_retry_flags_it_too(): void
    {
        $gateway = $this->salon->paymentGateways()->sole();
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $gateway->id, 'driver' => 'zarinpal', 'purpose' => 'booking',
            'amount_rial' => 600000, 'token' => 'S000000000000000000000000000000ab99', 'callback_url' => 'x', 'status' => 'failed',
            'user_id' => User::factory()->create()->id, 'verify_response' => ['unanswered' => true],
        ]);
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['updated_at' => now()->subMinutes(30)]);
        Http::fake(['*/verify.json' => Http::response(['data' => [], 'errors' => ['code' => -52, 'message' => 'Oops', 'validations' => []]], 422)]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('failed', $tx->status);
        $this->assertTrue($tx->needs_attention);
    }

    public function test_a_platform_subscription_payment_is_flagged_for_the_super_admin(): void
    {
        $invoice = \App\Models\Invoice::factory()->create(['salon_id' => $this->salon->id, 'amount' => 1500000, 'authority' => 'S000000000000000000000000000000sub1']);
        PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'driver' => 'zarinpal', 'purpose' => 'subscription', 'payable_type' => $invoice->getMorphClass(),
            'payable_id' => $invoice->id, 'amount_rial' => 15000000, 'token' => 'S000000000000000000000000000000sub1', 'callback_url' => 'x', 'status' => 'pending',
        ]);
        Http::fake(['*/verify.json' => Http::response(['data' => [], 'errors' => ['code' => -50, 'message' => 'x', 'validations' => []]], 422)]);

        $result = app(\App\Services\Payment\SubscriptionPaymentService::class)->verifyPayment($invoice, 'OK', 'S000000000000000000000000000000sub1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('کسر شده باشد', $result['message']);
        $this->assertTrue(PaymentTransaction::where('purpose', 'subscription')->sole()->needs_attention);
    }
}
