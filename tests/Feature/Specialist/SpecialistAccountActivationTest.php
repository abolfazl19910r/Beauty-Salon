<?php

namespace Tests\Feature\Specialist;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ⭐ آزمون دستی ۲۰۲۶-۱۰-۲۲: متخصصی که مدیر با شماره‌ی تازه اضافه کرده هیچ راهی برای ورود نداشت. تصمیم ابوالفضل: رمز اولیه‌ی اختیاری
 * در فرم مدیر، وگرنه فعال‌سازی با کد (وب و اپ همکار)؛ ثبت‌نام یا ورود با حساب مشتری همان سالن هم حساب متخصص می‌شود؛ و متخصص از پنل
 * خودش برای خودش نوبت می‌گیرد و از سایت سالن به پنلش برمی‌گردد.
 */
class SpecialistAccountActivationTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '09057019161';

    private Specialist $specialist;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'phone' => '09120000001']);
        $this->specialist = Specialist::factory()->create(['phone' => self::PHONE, 'name' => 'سارا رضایی', 'user_id' => null]);
    }

    private function sentCode(): string
    {
        $job = Queue::pushed(SendRegistrationCodeJob::class)->last();
        $this->assertNotNull($job, 'کد فرستاده نشد');

        return $job->code;
    }

    private function adminForm(array $extra = []): array
    {
        return array_merge([
            'name' => 'مینا', 'phone' => '09351112233', 'email' => 'mina@example.com',
            'services' => [BeautyService::factory()->create()->id],
        ], $extra);
    }

    public function test_web_activation_creates_the_specialist_account_and_opens_the_panel(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('specialist.activate'), false)->assertDontSee('ثبت نام کنید');
        $this->get(route('specialist.activate'))->assertOk()->assertSee('data-specialist-activate', false);

        $this->post(route('specialist.activate'), ['phone' => self::PHONE, 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertRedirect(route('specialist.activate.verify.show'));
        $this->assertDatabaseMissing('users', ['phone' => self::PHONE]);
        $this->get(route('specialist.activate.verify.show'))->assertOk()->assertSee(self::PHONE);

        $this->post(route('specialist.activate.verify'), ['code' => $this->sentCode()])->assertRedirect('/my-dashboard');

        $user = User::where('phone', self::PHONE)->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('staff', $user->user_type);
        $this->assertSame('سارا رضایی', $user->name);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame($user->id, $this->specialist->fresh()->user_id);
        $this->get('/my-dashboard')->assertOk();
    }

    public function test_activation_is_only_for_a_specialist_phone_without_an_account(): void
    {
        $this->post(route('specialist.activate'), ['phone' => '09359999999', 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertSessionHasErrors(['phone' => \App\Services\Specialist\SpecialistActivationService::NOT_SPECIALIST]);

        $this->post(route('specialist.activate'), ['phone' => $this->owner->phone, 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertSessionHasErrors(['phone' => \App\Services\Specialist\SpecialistActivationService::HAS_ACCOUNT]);

        Queue::assertNotPushed(SendRegistrationCodeJob::class);
    }

    public function test_staff_app_activation_returns_a_token(): void
    {
        $challenge = $this->postJson('/api/v1/staff/activate', ['phone' => self::PHONE, 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertOk()->json('data.challenge');

        $token = $this->postJson('/api/v1/staff/activate/verify', ['challenge' => $challenge, 'code' => $this->sentCode(), 'device_name' => 'A17'])
            ->assertCreated()->assertJsonPath('data.account.specialist.id', $this->specialist->id)->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/staff/today')->assertOk();

        $this->postJson('/api/v1/staff/activate', ['phone' => '09359999999', 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertStatus(422)->assertJsonPath('error.code', 'not_a_specialist');
    }

    public function test_an_initial_password_from_the_admin_creates_the_account_right_away(): void
    {
        $this->actingAs($this->owner)->post(route('admin.specialists.store'), $this->adminForm([
            'initial_password' => 'first-pass-1', 'initial_password_confirmation' => 'first-pass-1',
        ]))->assertRedirect(route('admin.specialists.index'))->assertSessionHas('success', fn ($m) => str_contains($m, 'حساب او با رمزی که گذاشتید'));

        $user = User::where('phone', '09351112233')->where('user_type', 'staff')->sole();
        $this->assertSame($user->id, Specialist::where('phone', '09351112233')->value('user_id'));

        auth()->logout();
        $this->post('/login', ['phone' => '09351112233', 'password' => 'first-pass-1'])->assertRedirect(route('login.verify.show'));
        $code = Queue::pushed(SendLoginVerificationCodeJob::class)->last() ? $user->fresh()->login_verification_code : null;
        $this->post(route('login.verify'), ['code' => $code])->assertRedirect('/my-dashboard');
        $this->assertNotNull($user->fresh()->phone_verified_at, 'کد ورود شماره را تأیید کرد؛ صفحه‌ی تأیید شماره لازم نیست');
        $this->get('/my-dashboard')->assertOk();
    }

    public function test_without_an_initial_password_the_admin_is_told_how_the_specialist_activates(): void
    {
        $this->actingAs($this->owner)->post(route('admin.specialists.store'), $this->adminForm())
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'فعال‌سازی حساب متخصص'));
        $this->assertDatabaseMissing('users', ['phone' => '09351112233']);
        $this->get(route('admin.specialists.create'))->assertOk()->assertSee('data-initial-password', false);
    }

    public function test_signing_up_on_the_salon_page_with_the_specialist_phone_gives_a_specialist_account(): void
    {
        $this->post('/s/rasta/register', ['name' => 'سارا', 'phone' => self::PHONE, 'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'accept_terms' => '1']);
        $this->post(route('salon.register.verify'), ['code' => $this->sentCode()])->assertRedirect('/my-dashboard');

        $user = User::where('phone', self::PHONE)->sole();
        $this->assertSame('staff', $user->user_type);
        $this->assertSame($user->id, $this->specialist->fresh()->user_id);
    }

    public function test_the_customer_app_points_a_specialist_phone_to_the_staff_app(): void
    {
        $this->postJson('/api/v1/customer/salons/rasta/register', ['name' => 'سارا', 'phone' => self::PHONE, 'password' => 'secret-123', 'password_confirmation' => 'secret-123'])
            ->assertStatus(422)->assertJsonPath('error.code', 'specialist_phone');
        Queue::assertNotPushed(SendRegistrationCodeJob::class);
    }

    public function test_an_existing_customer_of_the_salon_becomes_the_specialist_on_login_and_keeps_bookings(): void
    {
        $salonId = app(CurrentSalon::class)->id();
        $customer = User::factory()->create(['phone' => self::PHONE, 'user_type' => 'customer', 'salon_id' => $salonId, 'password' => 'secret-123']);
        $booking = Booking::factory()->create(['user_id' => $customer->id]);

        $this->post('/s/rasta/login', ['phone' => self::PHONE, 'password' => 'secret-123']);
        $this->post(route('salon.login.verify'), ['code' => $customer->fresh()->login_verification_code])->assertRedirect('/my-dashboard');

        $this->assertSame('staff', $customer->fresh()->user_type);
        $this->assertSame($customer->id, $this->specialist->fresh()->user_id);
        $this->assertSame($customer->id, $booking->fresh()->user_id);
        $this->get('/my-dashboard')->assertOk();
    }

    public function test_a_specialist_books_for_himself_from_the_panel_and_the_salon_site_links_back(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE, 'user_type' => 'staff']);
        $this->specialist->update(['user_id' => $user->id]);
        $other = Specialist::factory()->create();
        $service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $target = now()->addDay()->setTime(10, 0);
        SpecialistSchedule::factory()->create(['specialist_id' => $other->id, 'day_of_week' => $target->dayOfWeek, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);

        $this->actingAs($user)->get('/my-dashboard')->assertOk()->assertSee('data-book-for-myself', false)->assertSee(route('bookings.create'), false);
        $this->get(route('bookings.create'))->assertOk()->assertSee('پنل کاری')->assertSee(route('specialist.my-dashboard'), false);

        $this->post(route('bookings.store'), ['service_id' => $service->id, 'specialist_id' => $other->id, 'booking_time' => $target->format('Y-m-d H:i:s')]);
        $this->assertSame($user->id, Booking::where('specialist_id', $other->id)->value('user_id'));
        $this->get('/my-dashboard')->assertOk();
    }
}
