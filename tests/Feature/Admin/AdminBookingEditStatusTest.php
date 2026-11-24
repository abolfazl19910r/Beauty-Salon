<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ۲۰۲۶-۱۱-۲۴ (پس از بسته‌ی ۰): فرم ویرایش نوبت مدیر «انجام‌شده» را نشان می‌داد ولی UpdateAdminBookingRequest ردش می‌کرد، و برای نوبت
 * «نیامد» یا «منتظر پرداخت» گزینه‌ای نبود — اولین گزینه («منتظر تأیید») انتخاب می‌شد و ذخیره‌ی هر ویرایش دیگری وضعیت را بی‌صدا عوض می‌کرد.
 */
class AdminBookingEditStatusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(Booking $booking, string $status): array
    {
        return [
            'user_id' => $booking->user_id,
            'service_id' => $booking->service_id,
            'specialist_id' => $booking->specialist_id,
            'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
            'status' => $status,
            'payment_status' => $booking->payment_status,
            'notes' => 'ویرایش یادداشت',
        ];
    }

    public function test_the_form_offers_only_statuses_the_server_accepts(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending', 'booking_time' => now()->addDay()]);

        $html = $this->actingAs($this->admin())->get(route('admin.bookings.edit', $booking))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<select id="status"[^>]*>(.*?)<\/select>/s', $html, $m));
        preg_match_all('/<option value="(\w+)"/', $m[1], $values);
        $this->assertSame(['pending', 'confirmed', 'cancelled'], $values[1]);
    }

    public function test_a_no_show_booking_keeps_its_status_when_other_fields_are_edited(): void
    {
        $admin = $this->admin();
        $booking = Booking::factory()->create(['status' => 'no_show', 'booking_time' => now()->subDay()]);

        $html = $this->actingAs($admin)->get(route('admin.bookings.edit', $booking))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="no_show"\s+selected/', $html);

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), $this->payload($booking, 'no_show'))
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(['no_show', 'ویرایش یادداشت'], [$booking->fresh()->status, $booking->fresh()->notes]);
    }

    public function test_completed_cannot_be_set_from_the_edit_form(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed', 'booking_time' => now()->addDay()]);

        $this->actingAs($this->admin())->put(route('admin.bookings.update', $booking), $this->payload($booking, 'completed'))
            ->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_the_admin_dashboard_has_no_dead_recent_bookings_renderer(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('updateRecentBookings', $html);
        $this->assertStringContainsString('id="recent-bookings-container"', $html);
    }
}
