<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ باگ (۲۰۲۶-۰۹-۲۷، پیدا‌شده در ممیزی): صفحه‌ی «پرداخت ناموفق» (bookings.failed) دکمه‌ی «تلاش مجدد» را با نوبتِ
 * session('booking_id') می‌ساخت، ولی هیچ‌کدام از مسیرهای برگشت ناموفق از درگاه booking_id را در session نمی‌گذاشتند —
 * پس مشتری‌ای که پرداختش ناموفق بود به‌جای این صفحه خطای ۵۰۰ می‌دید (و باز کردن مستقیم صفحه هم ۵۰۰ بود).
 * تست‌های قبلی فقط redirect را چک می‌کردند، نه خود صفحه را.
 */
class BookingFailedPageTest extends TestCase
{
    use RefreshDatabase;

    private function zibalBooking(User $user): Booking
    {
        Sleep::fake();
        Notification::fake();
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 7])]);

        return Booking::factory()->create([
            'user_id' => $user->id,
            'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0),
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    /** مشتری در صفحه‌ی بانک «انصراف» می‌زند. */
    private function cancelAtTheBank(User $user): \Illuminate\Testing\TestResponse
    {
        $tx = PaymentTransaction::sole();
        $callback = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 7, 'success' => 0, 'status' => 3]))
            ->headers->get('Location');

        return $this->actingAs($user)->get($callback);
    }

    public function test_a_failed_bank_return_lands_on_a_working_failure_page(): void
    {
        $user = User::factory()->create();
        $booking = $this->zibalBooking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));

        $failed = $this->cancelAtTheBank($user)->assertRedirect(route('bookings.failed'));

        $page = $this->actingAs($user)->get($failed->headers->get('Location'))->assertOk();
        $page->assertViewHas('booking', fn ($b) => $b?->id === $booking->id);
        $page->assertSee(route('bookings.index'));
    }

    public function test_cancelling_at_the_bank_refunds_the_wallet_share_of_a_split_payment(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 20000]);
        $booking = $this->zibalBooking($user);
        $this->actingAs($user)->post(route('payment.wallet', $booking), ['use_wallet' => true, 'wallet_amount' => 20000]);
        $this->assertSame(0.0, (float) $wallet->fresh()->balance, 'سهم کیف پول قبل از رفتن به بانک کسر می‌شود');

        $this->cancelAtTheBank($user)->assertRedirect(route('bookings.failed'))
            ->assertSessionHas('booking_id', $booking->id);

        $this->assertSame(20000.0, (float) $wallet->fresh()->balance);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_opening_the_failure_page_directly_does_not_crash(): void
    {
        $this->actingAs(User::factory()->create())->get(route('bookings.failed'))
            ->assertOk()->assertSee(route('bookings.index'));
    }

    public function test_the_retry_button_is_offered_only_for_a_booking_that_can_still_be_paid(): void
    {
        $user = User::factory()->create();
        $payable = Booking::factory()->create(['user_id' => $user->id, 'status' => 'pending_payment', 'payment_status' => 'unpaid']);
        $cancelled = Booking::factory()->create(['user_id' => $user->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);

        $this->actingAs($user)->withSession(['booking_id' => $payable->id])->get(route('bookings.failed'))
            ->assertOk()->assertSee(route('payment.show', $payable));
        $this->actingAs($user)->withSession(['booking_id' => $cancelled->id])->get(route('bookings.failed'))
            ->assertOk()->assertDontSee(route('payment.show', $cancelled));
    }
}
