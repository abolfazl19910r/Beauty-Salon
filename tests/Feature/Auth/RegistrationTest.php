<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendRegistrationCodeJob;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ثبت‌نام مشتری در صفحه‌ی سالن (/s/{slug}/register). از ۲۰۲۶-۱۰-۱۱ حساب فقط بعد از تأیید کد ساخته می‌شود و
 * ثبت‌نام نیمه‌کاره‌ی تأییدنشده جلوی صاحب واقعی شماره را نمی‌گیرد. POST مرکزی /register حذف شد.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'name' => 'علی محمدی',
        'phone' => '09121234567',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function sentCode(): string
    {
        $job = Queue::pushed(SendRegistrationCodeJob::class)->last();
        $this->assertNotNull($job, 'کد ثبت‌نام فرستاده نشد');

        return $job->code;
    }

    public function test_the_central_register_page_redirects_to_the_default_salon(): void
    {
        $this->get('/register')->assertRedirect(route('salon.register', ['salon_slug' => 'rasta']));
    }

    public function test_the_central_register_form_endpoints_are_gone(): void
    {
        $this->post('/register', $this->form)->assertStatus(405);
        $this->post('/register/verify', ['code' => '123456'])->assertNotFound();
        $this->post('/register/resend')->assertNotFound();
        $this->assertDatabaseMissing('users', ['phone' => '09121234567']);
        Queue::assertNothingPushed();
    }

    public function test_registration_sends_a_code_but_creates_no_account_before_it_is_confirmed(): void
    {
        $this->get('/s/rasta/register')->assertOk();

        $this->post('/s/rasta/register', $this->form)->assertRedirect(route('salon.register.verify.show'));

        $this->assertDatabaseMissing('users', ['phone' => '09121234567']);
        $this->assertGuest();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sentCode());
        Queue::assertPushedOn('otp', SendRegistrationCodeJob::class);
        $this->get('/s/rasta/register/verify')->assertOk()->assertSee('09121234567');
    }

    public function test_the_right_code_creates_a_verified_customer_of_this_salon_and_logs_in(): void
    {
        $this->post('/s/rasta/register', $this->form);

        $this->post('/s/rasta/register/verify', ['code' => $this->sentCode()])
            ->assertRedirect(route('home', ['salon_slug' => 'rasta']));

        $user = User::where('phone', '09121234567')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('علی محمدی', $user->name);
        $this->assertSame('customer', $user->user_type);
        $this->assertSame(app(CurrentSalon::class)->id(), $user->salon_id);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->password));
        $this->assertNull(session('customer_register_challenge'));
    }

    public function test_a_wrong_code_shows_an_error_and_creates_nothing(): void
    {
        $this->post('/s/rasta/register', $this->form);

        $this->post('/s/rasta/register/verify', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['phone' => '09121234567']);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $this->post('/s/rasta/register', $this->form);
        $code = $this->sentCode();
        $this->travel(3)->minutes();

        $this->post('/s/rasta/register/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_wrong_codes_burn_the_registration(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class); // سقف خود challenge، نه throttle آی‌پی
        $this->post('/s/rasta/register', $this->form);
        $code = $this->sentCode();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/s/rasta/register/verify', ['code' => '000000']);
        }

        $this->post('/s/rasta/register/verify', ['code' => $code])->assertRedirect(route('salon.register'));
        $this->assertDatabaseMissing('users', ['phone' => '09121234567']);
    }

    public function test_the_phone_format_is_validated(): void
    {
        $this->post('/s/rasta/register', ['phone' => '12345'] + $this->form)->assertSessionHasErrors('phone');
        Queue::assertNothingPushed();
    }

    public function test_a_verified_customer_of_the_same_salon_blocks_registration(): void
    {
        User::factory()->create(['phone' => '09121234567', 'salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone_verified_at' => now()]);

        $this->post('/s/rasta/register', $this->form)->assertSessionHasErrors('phone');
        Queue::assertNothingPushed();
    }

    public function test_the_same_phone_can_register_at_another_salon(): void
    {
        $other = Salon::factory()->create(['slug' => 'golbahar']);
        User::factory()->create(['phone' => '09121234567', 'salon_id' => $other->id, 'user_type' => 'customer', 'phone_verified_at' => now()]);

        $this->post('/s/rasta/register', $this->form)->assertRedirect(route('salon.register.verify.show'));
        $this->post('/s/rasta/register/verify', ['code' => $this->sentCode()]);

        $this->assertSame(2, User::where('phone', '09121234567')->count());
    }

    public function test_an_old_unverified_signup_does_not_block_the_real_owner_and_is_taken_over(): void
    {
        $squatter = User::factory()->create([
            'phone' => '09121234567', 'name' => 'کسی دیگر', 'password' => bcrypt('squatter-pass'),
            'salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone_verified_at' => null,
        ]);

        $this->post('/s/rasta/register', $this->form)->assertRedirect(route('salon.register.verify.show'));
        $this->assertSame('کسی دیگر', $squatter->fresh()->name, 'پیش از تأیید کد چیزی عوض نمی‌شود');

        $this->post('/s/rasta/register/verify', ['code' => $this->sentCode()]);

        $squatter->refresh();
        $this->assertAuthenticatedAs($squatter);
        $this->assertSame('علی محمدی', $squatter->name);
        $this->assertNotNull($squatter->phone_verified_at);
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('squatter-pass', $squatter->password));
        $this->assertSame(1, User::where('phone', '09121234567')->count());
    }

    public function test_resend_waits_a_minute_then_sends_a_new_code(): void
    {
        $this->post('/s/rasta/register', $this->form);
        $first = $this->sentCode();

        $this->post('/s/rasta/register/resend')->assertSessionHasErrors('error');
        $this->travel(61)->seconds();
        $this->post('/s/rasta/register/resend')->assertSessionHas('success');
        $second = $this->sentCode();

        Queue::assertPushed(SendRegistrationCodeJob::class, 2);
        if ($first !== $second) {
            $this->post('/s/rasta/register/verify', ['code' => $first])->assertSessionHasErrors('code');
        }
        $this->post('/s/rasta/register/verify', ['code' => $second])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_verify_and_resend_without_a_registration_in_progress_fail_gracefully(): void
    {
        $this->get('/s/rasta/register/verify')->assertRedirect(route('salon.register'));
        $this->post('/s/rasta/register/resend')->assertSessionHasErrors('error');
        $this->post('/s/rasta/register/verify', ['code' => '123456'])->assertRedirect(route('salon.register'));
    }
}
