<?php

namespace Tests\Feature\Factories;

use App\Models\Booking;
use App\Models\Specialist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ریشه‌ی شکست ناپایدار AdminDashboardControllerTest (۲۰۲۶-۱۰-۰۱، حین وریفای ادغام migrationها): BookingFactory ساعتی می‌کشد که
 * با نوبت فعال دیگری از همان متخصص یکی نباشد (قید یکتای active_slot_key)، ولی دو حفره داشت:
 * - count(n)->create() اول definition همه را می‌سازد و بعد درج می‌کند؛ چک دیتابیس نوبت‌های همان دسته را نمی‌بیند.
 * - اگر وضعیت قرعه «cancelled» بود چک انجام نمی‌شد، ولی create(['status' => 'pending']) وضعیت را بعداً عوض می‌کند.
 * Faker با seed ثابت؛ با یک متخصص و ~۵۰۰ ساعت ممکن، بدون رفع هر دو تست با UniqueConstraintViolationException می‌شکنند.
 */
class BookingFactorySlotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Specialist::factory()->create();
        fake()->seed(20261001);
    }

    public function test_one_batch_never_draws_the_same_active_slot_twice(): void
    {
        Booking::factory()->count(120)->create(['status' => 'confirmed']);

        $this->assertSame(120, Booking::query()->distinct()->count('booking_time'));
    }

    public function test_a_status_override_cannot_turn_an_unchecked_slot_into_a_duplicate(): void
    {
        for ($i = 0; $i < 120; $i++) {
            Booking::factory()->create(['status' => 'pending']);
        }

        $this->assertSame(120, Booking::query()->distinct()->count('booking_time'));
    }
}
