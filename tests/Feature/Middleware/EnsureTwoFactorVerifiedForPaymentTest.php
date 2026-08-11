<?php

namespace Tests\Feature\Middleware;

use App\Jobs\Send2faVerificationCodeJob;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnsureTwoFactorVerifiedForPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $booking = Booking::factory()->create();

        $response = $this->get(route('payments.secure.checkout', $booking));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_2fa_enabled_is_redirected_to_2fa_settings(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('payments.secure.checkout', $booking));

        $response->assertRedirect(route('security.2fa'));
    }

    public function test_json_request_without_2fa_enabled_gets_a_403_not_a_redirect(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('api.payments.secure.initiate', $booking));

        $response->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_user_with_2fa_enabled_but_unverified_session_is_sent_an_otp_and_redirected(): void
    {
        Queue::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('payments.secure.checkout', $booking));

        $response->assertRedirect(route('payments.secure.otp'));
        Queue::assertPushed(Send2faVerificationCodeJob::class);

        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
    }

    public function test_a_second_request_before_verifying_does_not_send_a_second_code(): void
    {
        Queue::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('payments.secure.checkout', $booking));
        $this->actingAs($user)->get(route('payments.secure.checkout', $booking));

        Queue::assertPushed(Send2faVerificationCodeJob::class, 1);
    }

    public function test_json_request_without_verified_session_gets_a_428_with_otp_redirect_hint(): void
    {
        Queue::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('api.payments.secure.initiate', $booking));

        $response->assertStatus(428)->assertJson([
            'success' => false,
            'otp_required' => true,
        ]);
    }

    public function test_verified_session_passes_through_to_the_controller(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->get(route('payments.secure.checkout', $booking));

        $response->assertOk();
    }

    public function test_otp_entry_page_redirects_to_intended_url_once_already_verified(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);

        $response = $this->actingAs($user)
            ->withSession([
                '2fa_verified' => true,
                'secure_payment_intended_url' => route('bookings.index'),
            ])
            ->get(route('payments.secure.otp'));

        $response->assertRedirect(route('bookings.index'));
    }
}
