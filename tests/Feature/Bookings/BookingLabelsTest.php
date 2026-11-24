<?php

namespace Tests\Feature\Bookings;

use App\Models\Booking;
use App\Models\User;
use App\Support\Bookings\BookingLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۰: یک منبع برای برچسب وضعیت نوبت و پرداخت (۲۰۲۶-۱۱-۲۴). پیش‌تر `pending_payment` در فهرست نوبت‌های مدیر و «نوبت‌های
 * اخیر» داشبورد خام انگلیسی بود و فیلتر وضعیت گزینه‌ی «منتظر پرداخت» نداشت.
 */
class BookingLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_booking_status_has_an_approved_persian_label(): void
    {
        $this->assertSame([
            'pending_payment' => 'منتظر پرداخت',
            'pending' => 'منتظر تأیید',
            'confirmed' => 'تأییدشده',
            'completed' => 'انجام‌شده',
            'cancelled' => 'لغوشده',
            'no_show' => 'نیامد',
        ], BookingLabels::statusOptions());

        $this->assertSame('منتظر تأیید سالن', BookingLabels::status('pending', BookingLabels::AUDIENCE_CUSTOMER));
        $this->assertSame('تأییدشده', BookingLabels::status('confirmed', BookingLabels::AUDIENCE_CUSTOMER));
        $this->assertSame('نامشخص', BookingLabels::status('weird'));
        $this->assertSame('muted', BookingLabels::statusTone('weird'));
    }

    public function test_payment_label_follows_prepayment_and_the_amount_left_for_the_salon(): void
    {
        $atSalon = Booking::factory()->create(['payment_status' => 'unpaid', 'prepayment_required' => false]);
        $unpaid = Booking::factory()->create(['payment_status' => 'unpaid', 'prepayment_required' => true]);
        $prepaid = Booking::factory()->create(['status' => 'cancelled', 'payment_status' => 'paid', 'prepayment_required' => true, 'prepayment_amount' => 50000, 'discount_amount' => 0]);
        $prepaid->service->forceFill(['price' => 300000])->save();
        $full = Booking::factory()->create(['payment_status' => 'paid', 'prepayment_required' => true, 'prepayment_amount' => 300000, 'discount_amount' => 0]);
        $full->service->forceFill(['price' => 300000])->save();

        $this->assertSame('پرداخت در سالن', BookingLabels::payment($atSalon->fresh('service')));
        $this->assertSame('پرداخت‌نشده', BookingLabels::payment($unpaid->fresh('service')));
        $this->assertSame('پیش‌پرداخت شده', BookingLabels::payment($prepaid->fresh('service')));
        $this->assertSame('پرداخت‌شده', BookingLabels::payment($full->fresh('service')));
    }

    public function test_admin_bookings_show_pending_payment_in_persian_and_can_filter_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Booking::factory()->create(['status' => 'pending_payment', 'booking_time' => now()->addDay()]);
        Booking::factory()->create(['status' => 'confirmed', 'booking_time' => now()->addDays(2)]);

        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()
            ->assertSee('data-booking-status="pending_payment"', false)
            ->assertSee('منتظر پرداخت')
            ->assertDontSee('>pending_payment<', false)
            ->assertSee(route('admin.bookings.index', ['status' => 'pending_payment']), false);

        $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'pending_payment']))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('>pending_payment<', false);
    }
}
