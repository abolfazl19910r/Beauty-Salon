<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\Booking\BookingPaymentReminderNotification;
use App\Support\Sms\SmsText;
use App\Support\SmsParts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۵): دکمه‌ی «یادآوری پرداخت» برای نوبت آینده‌ی منتظر پیش‌پرداخت — متن تأییدشده (۲ قطعه)،
 * از سهمیه‌ی سالن، هر نوبت هر ۳ ساعت یک بار.
 */
class AdminBookingPaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function awaiting(array $attrs = []): Booking
    {
        return Booking::factory()->create($attrs + ['status' => 'pending_payment', 'payment_status' => 'unpaid',
            'prepayment_amount' => 250000, 'booking_time' => now()->addDay()->setTime(10, 30)]);
    }

    public function test_the_list_offers_the_reminder_and_it_queues_the_sms_once_per_cooldown(): void
    {
        Notification::fake();
        $booking = $this->awaiting();

        $this->actingAs($this->admin)->get(route('admin.bookings.index', ['tab' => 'all']))->assertOk()
            ->assertSee('data-primary-action="remind"', false)
            ->assertSee(route('admin.bookings.payment-reminder', $booking), false);

        $this->actingAs($this->admin)->post(route('admin.bookings.payment-reminder', $booking))->assertRedirect()->assertSessionHas('success');
        Notification::assertSentTo($booking->user, BookingPaymentReminderNotification::class,
            fn ($n, $channels) => $channels === ['database', 'sms']);

        $this->actingAs($this->admin)->post(route('admin.bookings.payment-reminder', $booking))->assertSessionHas('error');
        Notification::assertSentToTimes($booking->user, BookingPaymentReminderNotification::class, 1);
    }

    public function test_only_future_awaiting_bookings_of_this_salon(): void
    {
        Notification::fake();
        $confirmed = Booking::factory()->create(['status' => 'confirmed', 'booking_time' => now()->addDay()]);
        $past = $this->awaiting(['booking_time' => now()->subHour()]);

        $this->actingAs($this->admin)->post(route('admin.bookings.payment-reminder', $confirmed))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.bookings.payment-reminder', $past))->assertSessionHas('error');

        $other = Salon::factory()->create();
        $foreign = $this->awaiting();
        Booking::withoutGlobalScopes()->whereKey($foreign->id)->toBase()->update(['salon_id' => $other->id]);
        $this->actingAs($this->admin)->post(route('admin.bookings.payment-reminder', $foreign->id))->assertNotFound();

        Notification::assertSentTimes(BookingPaymentReminderNotification::class, 0);
    }

    public function test_the_approved_text_is_two_parts_and_has_the_amount_and_link(): void
    {
        $booking = $this->awaiting();
        $text = SmsText::bookingPaymentReminder($booking, 'arisheh.ir/b/k3x9');

        $this->assertStringContainsString('منتظر پیش‌پرداخت 250,000 تومان است؛ بدون پرداخت لغو می‌شود. پرداخت: arisheh.ir/b/k3x9', $text);
        $this->assertStringStartsWith('نوبت '.verta($booking->booking_time)->format('Y/m/d').' ساعت 10:30', $text);
        $this->assertLessThanOrEqual(2, SmsParts::count($text));
    }
}
