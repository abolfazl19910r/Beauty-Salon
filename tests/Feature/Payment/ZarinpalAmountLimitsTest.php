<?php

namespace Tests\Feature\Payment;

use App\Models\BookingPolicy;
use App\Models\User;
use App\Models\WalletSetting;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\GatewayStartRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۶ (۲۰۲۶-۱۰-۳۰): واحد صریح `currency: "IRR"` در درخواست، و حد مبلغ زرین‌پال — حداقل ۱۰٬۰۰۰ ریال (SDK رسمی)
 * و حداکثر ۱۰۰ میلیون تومان (‎-41) — پیش از شروع پرداخت با پیام روشن، و در «تنظیمات مالی»/«نحوه‌ی رزرو» برای حداقل پیش‌پرداخت.
 */
class ZarinpalAmountLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): ZarinpalDriver
    {
        return new ZarinpalDriver(['merchant_id' => '11111111-2222-3333-4444-555555555555'], sandbox: true);
    }

    private function start(int $rial)
    {
        return $this->driver()->start(new GatewayStartRequest($rial, 'https://rasta.arisheh.ir/payments/return/x', 'تست'));
    }

    public function test_request_sends_the_rial_currency_explicitly(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'S0001'], 'errors' => []])]);

        $this->assertTrue($this->start(10000)->success);

        Http::assertSent(fn ($request) => $request['currency'] === 'IRR' && $request['amount'] === 10000);
    }

    public function test_amounts_outside_the_gateway_limits_are_refused_before_any_request(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'S0002'], 'errors' => []])]);

        $low = $this->start(9990);
        $this->assertFalse($low->success);
        $this->assertFalse($low->retryable);
        $this->assertStringContainsString('کمتر از حداقل', $low->message);
        $this->assertStringContainsString('۱٬۰۰۰ تومان', $low->message);

        $high = $this->start(1_000_000_010);
        $this->assertFalse($high->success);
        $this->assertStringContainsString('۱۰۰ میلیون تومان', $high->message);

        $this->assertTrue($this->start(1_000_000_000)->success, 'مرز ۱۰۰ میلیون تومان خودش مجاز است');
        Http::assertSentCount(1); // دو مبلغ بیرون از حد اصلاً به زرین‌پال نرفتند
    }

    private function owner(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function settings(array $overrides): array
    {
        return $overrides + [
            'withdrawal_fee_percentage' => 3, 'minimum_withdrawal_amount' => 50000, 'maximum_withdrawal_amount' => 10000000,
            'instant_withdrawal_enabled' => 0, 'instant_withdrawal_fee' => 5000, 'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20, 'specialist_cancellation_penalty_percentage' => 10,
            'specialist_cancellation_before_hours' => 24, 'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_window_days' => 30, 'specialist_repeat_cancellation_extra_percentage' => 0,
            'settlement_delay_days' => 3, 'admin_commission_percentage' => 15, 'prepayment_percentage' => 30,
            'minimum_prepayment_amount' => 50000,
        ];
    }

    public function test_financial_settings_keep_the_minimum_prepayment_inside_the_gateway_limits(): void
    {
        WalletSetting::get();
        $owner = $this->owner();

        $this->actingAs($owner)->put('/admin/wallet/settings', $this->settings(['minimum_prepayment_amount' => 500]))
            ->assertSessionHasErrors(['minimum_prepayment_amount' => 'حداقل پیش‌پرداخت باید دست‌کم ۱٬۰۰۰ تومان باشد؛ زرین‌پال مبلغ کمتر از ۱۰٬۰۰۰ ریال را نمی‌پذیرد.']);
        $this->actingAs($owner)->put('/admin/wallet/settings', $this->settings(['minimum_prepayment_amount' => 0]))
            ->assertSessionHasErrors('minimum_prepayment_amount');
        $this->actingAs($owner)->put('/admin/wallet/settings', $this->settings(['minimum_prepayment_amount' => 100_000_001]))
            ->assertSessionHasErrors('minimum_prepayment_amount');

        $this->actingAs($owner)->put('/admin/wallet/settings', $this->settings(['minimum_prepayment_amount' => 1000]))->assertSessionHasNoErrors();
        $this->actingAs($owner)->put('/admin/wallet/settings', $this->settings(['prepayment_percentage' => 0, 'minimum_prepayment_amount' => 0]))
            ->assertSessionHasNoErrors();
        $this->assertSame(0.0, (float) WalletSetting::get()->fresh()->minimum_prepayment_amount);
    }

    public function test_booking_policy_prepay_mode_needs_a_minimum_the_gateway_accepts(): void
    {
        $owner = $this->owner();
        app(CurrentSalon::class)->get()->paymentGateways()->delete();
        app(CurrentSalon::class)->get()->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => '11111111-2222-3333-4444-555555555555'], 'priority' => 1, 'is_active' => true]);

        $this->actingAs($owner)->put(route('admin.booking-policy.mode'), [
            'payment_mode' => 'prepay', 'prepayment_percentage' => 30, 'minimum_prepayment_amount' => 0,
        ])->assertSessionHasErrors('minimum_prepayment_amount');

        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);
    }
}
