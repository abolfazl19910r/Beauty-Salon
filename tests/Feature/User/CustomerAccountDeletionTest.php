<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * حذف حساب مشتری (تصمیم ۲۰۲۶-۱۰-۱۱ «الف»). باگ: حذف حساب برای هر مشتری‌ای که نوبت داشت ۵۰۰ می‌داد
 * (کلید خارجی bookings.user_id بدون cascade).
 */
class CustomerAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09120000001']);
    }

    private function booking(User $user, array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $user->id,
            'service_id' => BeautyService::factory()->create()->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'booking_time' => now()->subDays(3),
            'status' => 'completed',
            'payment_status' => 'paid',
        ], $extra));
    }

    public function test_a_customer_with_past_bookings_is_anonymised_and_the_bookings_stay(): void
    {
        $user = $this->customer();
        $booking = $this->booking($user);
        $user->createToken('phone', ['customer']);
        DB::table('bot_links')->insert(['user_id' => $user->id, 'salon_id' => $user->salon_id, 'messenger' => 'bale', 'chat_id' => '42', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('salon.login'));

        $this->assertGuest();
        $user->refresh();
        $this->assertSame('مشتری حذف‌شده', $user->name);
        $this->assertSame('deleted-'.$user->id, $user->phone);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, DB::table('bot_links')->where('user_id', $user->id)->count());
        $this->assertNotNull($booking->fresh(), 'نوبت برای حساب‌وکتاب سالن می‌ماند');
    }

    public function test_the_same_phone_can_register_again_after_deletion(): void
    {
        $user = $this->customer();
        $this->booking($user);
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

        $again = User::factory()->create(['salon_id' => $user->salon_id, 'user_type' => 'customer', 'phone' => '09120000001']);

        $this->assertNotSame($user->id, $again->id);
    }

    public function test_an_upcoming_active_booking_blocks_deletion(): void
    {
        $user = $this->customer();
        $this->booking($user, ['booking_time' => now()->addDay(), 'status' => 'confirmed']);

        $this->actingAs($user)->from(route('profile.edit'))->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertAuthenticated();
        $this->assertSame('09120000001', $user->fresh()->phone);
    }

    public function test_a_wallet_balance_blocks_deletion(): void
    {
        $user = $this->customer();
        $user->getOrCreateWallet()->update(['balance' => 15000]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertNotNull($user->fresh());
    }
}
