<?php

namespace Tests\Feature\Api\V1\Customer;

/**
 * بسته‌ی ۵د: خانه‌ی اپ مشتری مثل داشبورد وب (نوبت بعدی، تماس سالن، «نظرتان چه بود؟»، «دوباره رزرو کنید»)، تب‌های «نوبت‌های من»
 * با شمار، کار اصلی هر نوبت و «افزودن به تقویم» با نشانی امضاشده. همه‌ی فیلدها افزودنی‌اند (اپ قدیمی همان فیلدهای قبلی را دارد).
 */
class CustomerHomeApiTest extends CustomerApiTestCase
{
    public function test_home_has_the_next_booking_card_and_dashboard_parts(): void
    {
        $this->salon->update(['address' => 'تهران، خیابان ولیعصر', 'phone' => '02112345678']);
        $next = $this->bookingFor($this->customer, ['status' => 'pending', 'payment_status' => 'unpaid', 'prepayment_required' => false, 'booking_time' => now()->addDays(2)->setTime(14, 30)]);
        $done = $this->bookingFor($this->customer, ['status' => 'completed', 'booking_time' => now()->subDays(10)]);

        $this->api()->getJson('/api/v1/customer/home')->assertOk()
            ->assertJsonPath('data.next_booking.id', $next->id)
            ->assertJsonPath('data.next_booking.time_label', '۱۴:۳۰')
            ->assertJsonPath('data.next_booking.primary.0.kind', 'reschedule')
            ->assertJsonPath('data.next_is_today', false)
            ->assertJsonPath('data.can_change', true)
            ->assertJsonPath('data.change_hours', 24)
            ->assertJsonPath('data.salon_contact.address', 'تهران، خیابان ولیعصر')
            ->assertJsonPath('data.salon_contact.phone', '02112345678')
            ->assertJsonPath('data.to_rate.id', $done->id)
            ->assertJsonPath('data.rebook.service.id', $done->service_id)
            ->assertJsonStructure(['data' => ['date_label', 'loyalty_enabled', 'next_bookings', 'suggestion', 'upcoming_bookings', 'wallet_balance']]);
    }

    public function test_home_without_bookings_stays_valid(): void
    {
        $this->api()->getJson('/api/v1/customer/home')->assertOk()
            ->assertJsonPath('data.next_booking', null)
            ->assertJsonPath('data.to_rate', null)
            ->assertJsonPath('data.rebook', null)
            ->assertJsonPath('data.next_bookings', []);
    }

    public function test_bookings_by_tab_with_counts_and_primary_action(): void
    {
        $unpaid = $this->bookingFor($this->customer);
        $this->bookingFor($this->customer, ['status' => 'completed', 'booking_time' => now()->subDays(4)]);
        $this->bookingFor($this->customer, ['status' => 'cancelled']);

        $this->api()->getJson('/api/v1/customer/bookings?tab=upcoming')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unpaid->id)
            ->assertJsonPath('data.0.primary.0', ['kind' => 'pay', 'label' => 'پرداخت'])
            ->assertJsonPath('data.0.cancel_note', 'پولی پرداخت نشده؛ لغو هزینه‌ای ندارد.')
            ->assertJsonPath('meta.counts', ['upcoming' => 1, 'past' => 1, 'cancelled' => 1]);

        $this->api()->getJson('/api/v1/customer/bookings?tab=past')->assertOk()
            ->assertJsonPath('data.0.primary.0.kind', 'review')
            ->assertJsonPath('data.0.primary.1.kind', 'rebook');

        // روش قدیمی (scope) برای اپ‌های نصب‌شده
        $this->api()->getJson('/api/v1/customer/bookings')->assertOk()->assertJsonPath('meta.scope', 'upcoming');
        $this->api()->getJson('/api/v1/customer/bookings?tab=bogus')->assertStatus(422);
    }

    public function test_calendar_link_is_signed_and_only_for_that_booking(): void
    {
        $booking = $this->bookingFor($this->customer, ['status' => 'pending']);
        $url = $this->api()->getJson('/api/v1/customer/bookings/'.$booking->id)->assertOk()->json('data.calendar_url');

        $this->assertNotNull($url);
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('BEGIN:VEVENT', false);
        $other = $this->bookingFor($this->customerOf($this->salon, '09121119999'), ['status' => 'pending']);
        $this->assertContains($this->get(str_replace('/calendar/'.$booking->id.'.ics', '/calendar/'.$other->id.'.ics', $url))->status(), [403, 404], 'امضا مال نوبت دیگری است');
        $this->assertContains($this->get('/s/rasta/calendar/'.$booking->id.'.ics')->status(), [403, 404], 'بی امضا باز نمی‌شود');

        $this->bookingFor($this->customer, ['status' => 'cancelled']);
        $cancelled = $this->api()->getJson('/api/v1/customer/bookings?tab=cancelled')->json('data.0');
        $this->assertNull($cancelled['calendar_url']);
    }
}
