<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * پیش‌پرداخت فقط برای نوبت لغونشده، پرداخت‌نشده و آینده (تصمیم ۲۰۲۶-۱۰-۱۱). باگ: صفحه‌ی پرداخت وضعیت نوبت
 * را چک نمی‌کرد و دکمه‌ی «پرداخت نوبت» برای نوبت لغوشده هم دیده می‌شد؛ پرداخت، نوبت لغوشده را زنده می‌کرد.
 */
class PayableBookingTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\EnablesAllGateways;

    private Specialist $specialist;

    private BeautyService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 9]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 600000, 'refNumber' => 3]),
        ]);
        $this->specialist = Specialist::factory()->create();
        $this->service = BeautyService::factory()->create(['price' => 200000]);
        $this->user = User::factory()->create();
    }

    private function booking(array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->user->id, 'service_id' => $this->service->id, 'specialist_id' => $this->specialist->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0), 'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ], $extra));
    }

    public function test_a_cancelled_booking_cannot_be_paid(): void
    {
        $booking = $this->booking(['status' => 'cancelled', 'cancelled_by' => 'system', 'cancelled_at' => now()]);

        $this->actingAs($this->user)->post(route('payment.process', $booking))
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'لغو شده'));
        $this->actingAs($this->user)->get(route('payment.show', $booking))->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(0, PaymentTransaction::count());
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_a_cancelled_booking_is_not_paid_from_the_wallet_either(): void
    {
        $this->user->getOrCreateWallet()->update(['balance' => 100000]);
        $booking = $this->booking(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);

        $this->actingAs($this->user)->post(route('payment.wallet', $booking), ['use_wallet' => 1, 'wallet_amount' => 60000]);

        $this->assertSame(100000.0, (float) $this->user->getOrCreateWallet()->fresh()->balance);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_a_past_booking_cannot_be_paid(): void
    {
        $booking = $this->booking(['booking_time' => now()->subHour()]);

        $this->actingAs($this->user)->post(route('payment.process', $booking))->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_the_pay_button_is_hidden_for_a_cancelled_booking(): void
    {
        $booking = $this->booking(['status' => 'cancelled', 'cancelled_by' => 'system', 'cancelled_at' => now()]);

        $this->actingAs($this->user)->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertDontSee(route('payment.show', $booking));
        $this->actingAs($this->user)->get(route('payments.secure.checkout', $booking))->assertRedirect();
    }

    public function test_a_late_payment_for_a_booking_the_salon_cancelled_is_refunded_not_revived(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->user)->post(route('payment.process', $booking));
        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now(), 'cancellation_reason' => 'بیماری متخصص']);

        $tx = PaymentTransaction::sole();
        $location = $this->get("/payments/return/{$tx->public_id}?trackId=9&success=1&status=2")->headers->get('Location');
        $this->actingAs($this->user)->get($location)
            ->assertRedirect(route('bookings.failed'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'لغو شده بود'));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertStringContainsString('بیماری متخصص', $booking->cancellation_reason);
        $this->assertSame(60000.0, (float) $this->user->getOrCreateWallet()->fresh()->balance);
        Notification::assertSentTo($this->user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'booking_cancelled');
    }
}
