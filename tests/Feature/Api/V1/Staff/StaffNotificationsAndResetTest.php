<?php

namespace Tests\Feature\Api\V1\Staff;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * اعلان‌های داخلی و بازیابی رمز اپ همکار (بسته‌ی ۲).
 */
class StaffNotificationsAndResetTest extends TestCase
{
    use RefreshDatabase;

    protected Specialist $specialist;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specialist = Specialist::factory()->create();
        $this->user = User::find($this->specialist->user_id);
    }

    protected function api(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    protected function notification(object $notifiable, ?int $salonId, string $message, bool $read = false): UserNotification
    {
        return UserNotification::create([
            'type' => 'App\\Notifications\\Booking\\BookingNotification',
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->id,
            'salon_id' => $salonId,
            'data' => ['message' => $message, 'booking_id' => 7],
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_inbox_merges_account_and_specialist_notifications_of_this_salon_only(): void
    {
        $salonId = app(CurrentSalon::class)->id();
        $other = Salon::factory()->create();
        $mine = $this->notification($this->user, $salonId, 'برای حساب');
        $this->notification($this->specialist, $salonId, 'برای رکورد متخصص', true);
        $this->notification($this->user, $other->id, 'سالن دیگر');
        $this->notification(User::factory()->create(), $salonId, 'کاربر دیگر');
        // نوع دیگری با همان شناسه‌ی رکورد متخصص (نوع notifiable هم باید بخواند، نه فقط id)
        UserNotification::create([
            'type' => 'x', 'notifiable_type' => (new Salon)->getMorphClass(), 'notifiable_id' => $this->specialist->id,
            'salon_id' => $salonId, 'data' => ['message' => 'هم‌شناسه'],
        ]);
        $token = $this->user->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;

        $list = $this->api($token)->getJson('/api/v1/staff/notifications');
        $list->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('meta.unread_count', 1);
        $this->assertEqualsCanonicalizing(['برای حساب', 'برای رکورد متخصص'], array_column($list->json('data'), 'message'));
        $this->assertSame('booking', $list->json('data.0.category'));

        $this->api($token)->getJson('/api/v1/staff/notifications?unread=1')->assertJsonPath('meta.total', 1);

        $this->api($token)->postJson('/api/v1/staff/notifications/'.$mine->id.'/read')->assertOk()->assertJsonPath('data.read', true);
        $foreign = $this->notification(User::factory()->create(), $salonId, 'نه مال من');
        $this->api($token)->postJson('/api/v1/staff/notifications/'.$foreign->id.'/read')->assertNotFound();
        $this->assertNull($foreign->fresh()->read_at);

        $this->notification($this->specialist, $salonId, 'تازه');
        $this->api($token)->postJson('/api/v1/staff/notifications/read-all')->assertOk()->assertJsonPath('data.marked', 1);
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_password_reset_changes_the_staff_password_and_logs_every_device_out(): void
    {
        $this->user->forceFill(['password' => Hash::make('old-pass-11')])->save();
        $token = $this->user->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;
        // مشتری یک سالن با همان شماره (رفع findStaffByPhone)
        User::factory()->create(['phone' => $this->user->phone, 'user_type' => 'customer', 'password' => Hash::make('cust-pass-1')]);

        $challenge = $this->postJson('/api/v1/staff/password/forgot', ['phone' => $this->user->phone])
            ->assertOk()->json('data.challenge');
        $code = $this->user->fresh()->verification_code;
        $this->assertNotNull($code);

        $this->postJson('/api/v1/staff/password/reset', [
            'challenge' => $challenge, 'code' => $code, 'password' => 'NewStaff-123', 'password_confirmation' => 'NewStaff-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewStaff-123', $this->user->fresh()->password));
        $this->assertNull($this->user->fresh()->verification_code);
        $this->assertSame(0, $this->user->tokens()->count());
        $this->api($token)->getJson('/api/v1/me')->assertUnauthorized();

        // challenge یک‌بارمصرف است
        $this->postJson('/api/v1/staff/password/reset', [
            'challenge' => $challenge, 'code' => $code, 'password' => 'Again-12345', 'password_confirmation' => 'Again-12345',
        ])->assertStatus(410)->assertJsonPath('error.code', 'challenge_expired');
    }

    public function test_reset_and_login_challenges_are_not_interchangeable(): void
    {
        $challenge = $this->postJson('/api/v1/staff/password/forgot', ['phone' => $this->user->phone])->json('data.challenge');

        $this->postJson('/api/v1/staff/login/verify', [
            'challenge' => $challenge, 'code' => $this->user->fresh()->verification_code, 'device_name' => 'x',
        ])->assertStatus(410)->assertJsonPath('error.code', 'challenge_expired');
    }

    public function test_reset_code_attempts_are_capped_like_login(): void
    {
        $challenge = $this->postJson('/api/v1/staff/password/forgot', ['phone' => $this->user->phone])->json('data.challenge');
        $wrong = $this->user->fresh()->verification_code === '111111' ? '222222' : '111111';
        $body = fn () => ['challenge' => $challenge, 'code' => $wrong, 'password' => 'NewStaff-123', 'password_confirmation' => 'NewStaff-123'];

        foreach (range(1, 4) as $i) {
            $this->postJson('/api/v1/staff/password/reset', $body())->assertStatus(422)->assertJsonPath('error.code', 'invalid_code');
        }
        $this->postJson('/api/v1/staff/password/reset', $body())->assertStatus(429)->assertJsonPath('error.code', 'too_many_code_attempts');

        $this->assertNull($this->user->fresh()->verification_code);
    }

    public function test_reset_is_only_for_specialist_staff_accounts(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'salon_id' => null, 'is_admin' => true]);
        $customer = User::factory()->create(['user_type' => 'customer']);

        $this->postJson('/api/v1/staff/password/forgot', ['phone' => $admin->phone])
            ->assertForbidden()->assertJsonPath('error.code', 'wrong_app');
        $this->postJson('/api/v1/staff/password/forgot', ['phone' => $customer->phone])
            ->assertStatus(422)->assertJsonPath('error.code', 'account_not_found');

        $this->assertNull($admin->fresh()->verification_code);
        $this->assertNull($customer->fresh()->verification_code);
    }
}
