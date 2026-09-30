<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: هر اعلان سالنِ رکوردی را که درباره‌ی آن است با خودش دارد و هر پنل فقط اعلان‌های سالن جاری
 * (و اعلان‌های بدون سالن) را نشان می‌دهد و می‌شمارد.
 */
class NotificationSalonIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salonA;

    private Salon $salonB;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->salonB = Salon::factory()->create(['slug' => 'notif-isolation-b']);
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->salonB->admins()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function bookingIn(Salon $salon): Booking
    {
        $current = app(CurrentSalon::class);
        $previous = $current->get();
        $current->set($salon);
        $before = UserNotification::pluck('id');
        $booking = Booking::factory()->create();
        UserNotification::whereNotIn('id', $before)->delete(); // اعلان‌هایی که observer نوبت خودش می‌سازد
        $previous ? $current->set($previous) : $current->clear();

        return $booking;
    }

    private function notifyAbout(Booking $booking): UserNotification
    {
        app(CurrentSalon::class)->clear(); // مثل صف: بدون سالن جاری
        $before = UserNotification::pluck('id');
        $this->owner->notify(new AdminNewBookingNotification($booking));

        return UserNotification::whereNotIn('id', $before)->firstOrFail();
    }

    public function test_a_notification_records_the_salon_of_the_record_it_is_about(): void
    {
        $this->assertSame($this->salonB->id, (int) $this->notifyAbout($this->bookingIn($this->salonB))->salon_id);
        $this->assertSame($this->salonA->id, (int) $this->notifyAbout($this->bookingIn($this->salonA))->salon_id);
    }

    public function test_a_notification_without_the_settings_trait_also_records_its_salon(): void
    {
        $booking = $this->bookingIn($this->salonB);
        app(CurrentSalon::class)->clear();
        $before = UserNotification::pluck('id');
        $booking->user->notify(new CustomerBookingNotification($booking));

        $this->assertSame($this->salonB->id, (int) UserNotification::whereNotIn('id', $before)->firstOrFail()->salon_id);
    }

    public function test_the_admin_panel_lists_and_counts_only_the_current_salons_notifications(): void
    {
        $inA = $this->notifyAbout($this->bookingIn($this->salonA));
        $inB = $this->notifyAbout($this->bookingIn($this->salonB));

        $this->actingAs($this->owner)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($page) => $page->pluck('id')->all() === [$inA->id]);

        $this->actingAs($this->owner)->getJson(route('admin.notifications.count'))->assertJson(['count' => 1]);
        $this->actingAs($this->owner)->get(route('admin.notifications.show', $inB->id))->assertNotFound();
    }

    public function test_read_all_and_delete_all_touch_only_the_current_salon(): void
    {
        $inA = $this->notifyAbout($this->bookingIn($this->salonA));
        $inB = $this->notifyAbout($this->bookingIn($this->salonB));

        $this->actingAs($this->owner)->post(route('admin.notifications.read-all'));
        $this->assertNotNull($inA->fresh()->read_at);
        $this->assertNull($inB->fresh()->read_at);

        $this->actingAs($this->owner)->delete(route('admin.notifications.delete-all'));
        $this->assertNull(UserNotification::find($inA->id));
        $this->assertNotNull(UserNotification::find($inB->id));
    }

    public function test_notifications_without_a_salon_are_still_shown(): void
    {
        $inA = $this->notifyAbout($this->bookingIn($this->salonA));
        UserNotification::whereKey($inA->id)->update(['salon_id' => null]);

        $this->actingAs($this->owner)->getJson(route('admin.notifications.count'))->assertJson(['count' => 1]);
    }
}
