<?php

namespace Tests\Feature\Booking;

use App\Exceptions\SalonPausedException;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Salon;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Services\Booking\BookingRescheduleService;
use App\Services\Booking\BookingService;
use App\Services\Courses\CourseService;
use App\Services\Salon\SalonPauseService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو سالن — فقط کار تازه بسته؛ نوبت‌های ثبت‌شده می‌مانند؛ توقف ابرمدیر را مدیر باز نمی‌کند.
 */
class SalonBookingPauseTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        // اولین is_admin ساخته‌شده خودکار owner سالن جاری می‌شود (UserFactory::configure)
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->setUpCourseWorld();
    }

    private function pause(string $by = Salon::PAUSED_BY_OWNER, ?string $reason = null, $until = null): void
    {
        app(SalonPauseService::class)->pause($this->salon, $this->owner, $by, $reason, $until);
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $user = User::factory()->create(['is_admin' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_pause_state_message_and_automatic_reopening(): void
    {
        $this->assertFalse($this->salon->isBookingPaused());

        $this->pause(reason: 'سالن تا پایان تعمیرات بسته است.', until: now()->addDays(3)->endOfDay());
        $this->assertTrue($this->salon->isBookingPaused());
        $message = $this->salon->bookingPauseMessage();
        $this->assertStringStartsWith('رزرو نوبت تازه در این سالن موقتاً متوقف است تا ', $message);
        $this->assertStringEndsWith('. سالن تا پایان تعمیرات بسته است.', $message);

        $this->travel(4)->days();
        $this->assertFalse($this->salon->fresh()->isBookingPaused());
    }

    public function test_new_work_is_refused_but_existing_bookings_stay(): void
    {
        $existing = app(BookingService::class)->createBooking($this->courseCustomer->id, $this->courseService->id, $this->courseSpecialist->id, $this->sessionAt(2));
        $course = $this->paidCourse();
        $package = $this->coursePackage();

        $this->pause();

        $refused = [
            'booking' => fn () => app(BookingService::class)->createBooking($this->courseCustomer->id, $this->courseService->id, $this->courseSpecialist->id, $this->sessionAt(3)),
            'manual' => fn () => app(BookingService::class)->createManualBooking([
                'user_id' => $this->courseCustomer->id, 'service_id' => $this->courseService->id, 'specialist_id' => $this->courseSpecialist->id,
                'booking_time' => $this->sessionAt(3, '12:00'), 'source' => 'phone',
            ]),
            'reschedule' => fn () => app(BookingRescheduleService::class)->reschedule($existing, $this->sessionAt(4)),
            'course purchase' => fn () => app(CourseService::class)->purchase($this->courseCustomer, $package),
            'booking payment' => fn () => app(\App\Services\Payment\BookingPaymentStarter::class)->start($this->courseCustomer, $existing, 0, null),
            'course payment' => fn () => app(\App\Services\Courses\CoursePaymentService::class)->start($this->courseCustomer, $course, 1000, 0, null, 'https://x.test/cb'),
            'course session' => fn () => app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(5)),
        ];
        foreach ($refused as $what => $action) {
            try {
                $action();
                $this->fail("{$what} was not refused");
            } catch (SalonPausedException $e) {
                $this->assertSame(423, $e->getHttpStatus());
                $this->assertStringContainsString('موقتاً متوقف', $e->getUserMessage());
            }
        }

        $this->assertSame(1, Booking::count());
        $this->assertSame($this->sessionAt(2), $existing->fresh()->booking_time->format('Y-m-d H:i:s'));
    }

    public function test_owner_pauses_and_resumes_from_salon_settings_with_banners(): void
    {
        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))->assertOk()
            ->assertSee('data-salon-pause', false)->assertSee('توقف رزرو');

        $this->actingAs($this->owner)->post(route('admin.salon-settings.pause'), ['days' => 7, 'reason' => 'سفر کاری'])
            ->assertRedirect(route('admin.salon-settings.edit'));
        $salon = $this->salon->fresh();
        $this->assertTrue($salon->isBookingPaused());
        $this->assertSame(Salon::PAUSED_BY_OWNER, $salon->paused_by);
        $this->assertSame('سفر کاری', $salon->pause_reason);
        $this->assertTrue($salon->paused_until->isSameDay(now()->addDays(7)));
        $this->assertSame(0, SuperAdminAuditLog::count());

        app(CurrentSalon::class)->set($salon);
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-booking-paused', false)->assertSee('بازکردن رزرو');
        $this->actingAs($this->courseCustomer)->get(route('bookings.create'))->assertOk()
            ->assertSee('data-booking-paused-page', false)->assertSee('سفر کاری')->assertDontSee('data-booking-paused class', false);
        $this->actingAs($this->courseCustomer)->get(route('home'))->assertOk()->assertSee('data-booking-paused', false);

        $this->actingAs($this->owner)->delete(route('admin.salon-settings.resume'))->assertRedirect(route('admin.salon-settings.edit'));
        $this->assertFalse($this->salon->fresh()->isBookingPaused());
    }

    public function test_owner_cannot_lift_or_replace_a_platform_pause(): void
    {
        $this->pause(Salon::PAUSED_BY_PLATFORM, 'بدهی اشتراک');
        app(CurrentSalon::class)->set($this->salon->fresh());

        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))->assertOk()
            ->assertSee('مدیریت ماهرو گذاشته است')->assertDontSee(route('admin.salon-settings.resume'), false);

        $this->actingAs($this->owner)->from(route('admin.salon-settings.edit'))->delete(route('admin.salon-settings.resume'))
            ->assertRedirect(route('admin.salon-settings.edit'))->assertSessionHas('error');
        $this->actingAs($this->owner)->from(route('admin.salon-settings.edit'))->post(route('admin.salon-settings.pause'), ['days' => 1])
            ->assertSessionHas('error');

        $salon = $this->salon->fresh();
        $this->assertTrue($salon->isPausedByPlatform());
        $this->assertNull($salon->paused_until);
    }

    public function test_super_admin_pauses_and_resumes_with_audit_log(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSalon::class)->clear();

        $this->actingAs($admin)->get(route('superadmin.salons.edit', $this->salon))->assertOk()->assertSee('data-salon-pause', false);
        $this->actingAs($admin)->post(route('superadmin.salons.pause', $this->salon), ['days' => 0, 'reason' => 'بررسی شکایت'])
            ->assertRedirect(route('superadmin.salons.edit', $this->salon));

        $salon = $this->salon->fresh();
        $this->assertTrue($salon->isPausedByPlatform());
        $this->assertNull($salon->paused_until);
        $this->actingAs($admin)->get(route('superadmin.salons.edit', $this->salon))->assertSee('از طرف ماهرو');

        $this->actingAs($admin)->delete(route('superadmin.salons.resume', $this->salon))->assertRedirect(route('superadmin.salons.edit', $this->salon));
        $this->assertFalse($this->salon->fresh()->isBookingPaused());
        $this->assertSame(['salon.paused', 'salon.resumed'], SuperAdminAuditLog::orderBy('id')->pluck('action')->all());
    }
}
