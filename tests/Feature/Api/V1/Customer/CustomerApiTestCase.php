<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پایه‌ی تست‌های API اپ «ماهرو» (مشتری، بسته‌ی ۳): مشتری سالن پیش‌فرض با توکن، و یک سالن دوم برای جداسازی.
 */
abstract class CustomerApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Salon $salon;

    protected User $customer;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = app(CurrentSalon::class)->get();
        $this->customer = $this->customerOf($this->salon, '09121110000');
        $this->token = $this->tokenFor($this->customer);
    }

    protected function customerOf(Salon $salon, string $phone): User
    {
        return User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer', 'phone' => $phone, 'phone_verified_at' => now()]);
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;
    }

    protected function api(?string $token = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token ?? $this->token);
    }

    /** خدمت + متخصصی که آن را انجام می‌دهد و هر روز ۹ تا ۱۸ کار می‌کند. */
    protected function bookable(?Salon $salon = null, array $service = [], array $specialist = []): array
    {
        $salon ??= $this->salon;
        $previous = app(CurrentSalon::class)->get();
        app(CurrentSalon::class)->set($salon);

        $svc = BeautyService::factory()->create(array_merge(['price' => 200000, 'duration' => 60], $service));
        $spec = Specialist::factory()->create(array_merge(['auto_confirm_bookings' => false], $specialist));
        $spec->services()->attach($svc->id);
        foreach (range(0, 6) as $day) {
            SpecialistSchedule::query()->updateOrCreate(
                ['specialist_id' => $spec->id, 'day_of_week' => $day],
                ['start_time' => '09:00', 'end_time' => '18:00', 'is_active' => true],
            );
        }

        app(CurrentSalon::class)->set($previous);

        return [$svc, $spec];
    }

    protected function bookingFor(User $user, array $extra = []): Booking
    {
        [$svc, $spec] = $this->bookable();

        return Booking::factory()->create(array_merge([
            'user_id' => $user->id, 'service_id' => $svc->id, 'specialist_id' => $spec->id,
            'booking_time' => now()->addDays(3)->setTime(10, 0), 'status' => 'pending_payment', 'payment_status' => 'unpaid',
            'prepayment_amount' => 60000, 'rating' => null,
        ], $extra));
    }
}
