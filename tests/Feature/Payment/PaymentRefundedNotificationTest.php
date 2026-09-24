<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Support\CurrentSalon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * ⭐ پیامک «پول شما برگشت داده شد» (تصمیم ابوالفضل ۲۰۲۶-۰۹-۲۶): متن، شرایط ارسال، و مسیر مبلغ ناهمخوان سامان.
 */
class PaymentRefundedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_message_names_the_salon_the_booking_and_where_each_amount_went_in_persian_digits(): void
    {
        $salon = Salon::factory()->create(['name' => 'سالن نمونه']);

        $both = new PaymentRefundedNotification('slot_taken', 40000, 20000, $salon, 17);
        $this->assertSame(
            "سالن نمونه\nساعت انتخابی هنگام پرداخت رزرو شده بود و پرداخت شما برای نوبت #17 ثبت نشد.\n"
            ."۴۰٬۰۰۰ تومان به کارت بانکی شما برگشت داده شد (طبق روال بانک معمولاً تا ۷۲ ساعت).\n"
            .'۲۰٬۰۰۰ تومان به کیف پول شما در همین سالن برگشت داده شد و برای رزرو بعدی قابل استفاده است.',
            $both->text,
        );

        $this->assertStringContainsString('تایید پرداخت شما از بانک دریافت نشد', (new PaymentRefundedNotification('verify_unanswered', 5000, 0))->text);
        $this->assertStringStartsWith(config('brand.name'), (new PaymentRefundedNotification('amount_mismatch', 5000, 0))->text);
        $this->assertInstanceOf(ShouldQueue::class, $both, 'صف‌دار — درخواست مشتری منتظر سرویس پیامک نمی‌مونه');
    }

    public function test_it_is_only_sent_by_sms_to_a_customer_with_a_phone_and_a_real_amount(): void
    {
        $withPhone = User::factory()->create(['phone' => '09121234567']);
        $withoutPhone = User::factory()->make(['phone' => null]);

        $this->assertSame(['sms'], (new PaymentRefundedNotification('slot_taken', 1000, 0))->via($withPhone));
        $this->assertSame([], (new PaymentRefundedNotification('slot_taken', 1000, 0))->via($withoutPhone));
        $this->assertSame([], (new PaymentRefundedNotification('slot_taken', 0, 0))->via($withPhone));
    }

    public function test_a_saman_amount_mismatch_reversed_on_the_spot_sends_the_sms(): void
    {
        Notification::fake();
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => '13012345'], 'priority' => 1]);
        $detail = ['RefNum' => 'RM', 'TerminalNumber' => 13012345, 'OrginalAmount' => 10000, 'RRN' => '1'];
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'TK']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/*' => Http::response(['ResultCode' => 0, 'Success' => true, 'TransactionDetail' => $detail]),
        ]);
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create()->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'status' => 'pending_payment', 'payment_status' => 'unpaid', 'prepayment_amount' => 60000,
        ]);

        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();
        $location = $this->post("/payments/return/{$tx->public_id}", [
            'Status' => '2', 'State' => 'OK', 'RefNum' => 'RM', 'ResNum' => (string) $tx->id, 'Token' => 'TK', 'TerminalId' => '13012345',
        ])->headers->get('Location');
        $this->actingAs($user)->get($location);

        $this->assertSame('unpaid', $booking->fresh()->payment_status);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'amount_mismatch'
            && $n->cardToman === 1000 && $n->bookingId === $booking->id);
    }
}
