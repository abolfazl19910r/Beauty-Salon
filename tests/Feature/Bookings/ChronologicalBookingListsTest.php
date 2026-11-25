<?php

namespace Tests\Feature\Bookings;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۰ (۲۰۲۶-۱۱-۲۴): فهرست نوبت‌ها اول آینده از نزدیک به دور، بعد گذشته از تازه به قدیم — پنل مدیر، پیش‌فرض پنل کارشناس
 * و «نوبت‌های من» مشتری. پیش‌تر مدیر بر اساس زمان ثبت و مشتری بر اساس گروه وضعیت مرتب می‌شد.
 */
class ChronologicalBookingListsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Booking> */
    private array $b = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** ترتیب ساخت عمداً با ترتیب زمانی فرق دارد تا زمان ثبت جواب درست را تصادفی ندهد */
    private function seedBookings(array $attributes = []): void
    {
        foreach (['past_old' => '-10 days', 'future_far' => '+5 days', 'past_recent' => '-1 day', 'future_near' => '+2 hours'] as $key => $offset) {
            $this->b[$key] = Booking::factory()->create($attributes + [
                'status' => str_starts_with($key, 'past') ? 'completed' : 'confirmed',
                'booking_time' => now()->modify($offset),
            ]);
            Carbon::setTestNow(now()->addMinute());
        }
        Carbon::setTestNow('2026-11-24 12:00:00');
    }

    private function expected(): array
    {
        return [$this->b['future_near']->id, $this->b['future_far']->id, $this->b['past_recent']->id, $this->b['past_old']->id];
    }

    public function test_scope_puts_upcoming_first_then_past_newest_first(): void
    {
        $this->seedBookings();

        $this->assertSame($this->expected(), Booking::query()->chronological()->pluck('id')->all());
    }

    public function test_admin_bookings_list_is_chronological(): void
    {
        $this->seedBookings();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.bookings.index', ['tab' => 'all']))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->pluck('id')->all() === $this->expected());
    }

    public function test_specialist_booking_tabs_are_chronological(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $this->seedBookings(['specialist_id' => $specialist->id]);

        // بسته‌ی ۳ ظاهری: «پیش رو» نزدیک به دور، «گذشته» تازه به قدیم
        $this->actingAs($user)->get(route('specialist.bookings.index'))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->pluck('id')->all() === array_slice($this->expected(), 0, 2));
        $this->get(route('specialist.bookings.index', ['tab' => 'past']))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->pluck('id')->all() === array_slice($this->expected(), 2));
    }

    public function test_customer_bookings_are_chronological(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);
        $this->seedBookings(['user_id' => $customer->id]);

        $ids = app(\App\Repositories\Contracts\BookingRepositoryInterface::class)->paginateForUser($customer->id, [], 10)->pluck('id')->all();

        $this->assertSame($this->expected(), $ids);
    }
}
