<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\Themes\SalonAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۲ب — پوسته‌ی «میز پذیرش»: فهرست نوبت‌ها + پنل جزئیات نوبت انتخاب‌شده که بی بارگذاری صفحه از admin.bookings.panel
 * پر می‌شود؛ کلیدهای J/K/Enter/T/N و «/»؛ T همیشه با پرسش (data-ui-confirm).
 */
class AdminDeskShellTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        app(DefaultPlans::class)->sync();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function shell(string $shell): void
    {
        $this->salon->forceFill([
            'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '1m', 'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => null, 'is_suspended' => false, 'admin_theme' => $shell === 'side' ? null : $shell,
        ])->save();
        SalonAppearance::flush();
    }

    private function pendingBooking(): Booking
    {
        return Booking::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid', 'booking_time' => now()->addHours(3)]);
    }

    public function test_bookings_page_has_the_details_panel_only_in_the_desk_shell(): void
    {
        $booking = $this->pendingBooking();

        $this->shell('dense');
        $html = $this->actingAs($this->owner)->get(route('admin.bookings.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-desk-panel', $html);
        $this->assertStringContainsString('data-panel-url="'.route('admin.bookings.panel', $booking).'"', $html);
        $this->assertStringContainsString('تأیید (با پرسش)', $html);
        $this->assertStringNotContainsString('onclick', $html);

        $this->shell('side');
        $html = $this->actingAs($this->owner)->get(route('admin.bookings.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-desk-panel-slot', $html);
        $this->assertStringNotContainsString('data-desk-row data-panel-url', $html);
    }

    public function test_panel_shows_booking_details_actions_and_customer_history(): void
    {
        $booking = $this->pendingBooking();
        Booking::factory()->create(['user_id' => $booking->user_id, 'status' => 'completed', 'payment_status' => 'paid', 'booking_time' => now()->subDays(10)]);
        Booking::factory()->create(['user_id' => $booking->user_id, 'status' => 'no_show', 'payment_status' => 'unpaid', 'booking_time' => now()->subDays(5)]);

        $html = $this->actingAs($this->owner)->get(route('admin.bookings.panel', $booking))->assertOk()->getContent();

        $this->assertStringContainsString('data-desk-panel-body="'.$booking->id.'"', $html);
        $this->assertStringContainsString(e($booking->user->name), $html);
        $this->assertMatchesRegularExpression('/data-desk-confirm data-ui-confirm="نوبت [^"]+ تأیید شود؟"/', $html);
        $this->assertStringContainsString('جابه‌جایی', $html);
        $this->assertStringContainsString('لغو نوبت…', $html);
        $this->assertStringContainsString('۱ نوبت انجام‌شده، ۱ غیبت', $html);
        $this->assertStringContainsString('آخرین نوبت:', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_panel_has_no_confirm_for_bookings_that_are_not_pending(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed', 'payment_status' => 'paid', 'booking_time' => now()->addDay()]);

        $html = $this->actingAs($this->owner)->get(route('admin.bookings.panel', $booking))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-desk-confirm', $html);
    }

    public function test_panel_of_another_salons_booking_is_not_found(): void
    {
        $other = Salon::factory()->create();
        $booking = $this->pendingBooking();
        Booking::query()->whereKey($booking->id)->toBase()->update(['salon_id' => $other->id]);

        $this->actingAs($this->owner)->get(route('admin.bookings.panel', $booking->id))->assertNotFound();
    }

    public function test_panel_needs_the_view_bookings_permission(): void
    {
        $booking = $this->pendingBooking();
        $staff = User::factory()->create(['is_admin' => false, 'user_type' => 'staff']);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);
        $staff->roles()->sync([\App\Models\Role::where('name', 'staff')->firstOrFail()->id]);

        $response = $this->actingAs($staff)->get(route('admin.bookings.panel', $booking));
        $staff->hasPermission('view-bookings') ? $response->assertOk() : $response->assertForbidden();
    }
}
