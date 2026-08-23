<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\User;
use App\Services\SecurePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard (test-writing session 11): SecurePaymentService used to hardcode a
 * 15-minute checkout window (protected const EXPIRY_MINUTES = 15), completely ignoring the
 * PAYMENT_EXPIRY_MINUTES key .env.example shipped (never actually read anywhere in the app).
 * It now reads services.secure_payment.expiry_minutes for both the persisted expired_at
 * timestamp (createPayment) and the signed-timestamp staleness check (verifyPayment).
 */
class SecurePaymentServiceConfigTest extends TestCase
{
    use RefreshDatabase;

    private SecurePaymentService $service;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SecurePaymentService::class);

        $user = User::factory()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $user->id,
            'prepayment_amount' => 75000,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_created_payment_expires_after_the_default_fifteen_minutes(): void
    {
        $payment = $this->service->createPayment($this->booking);

        $this->assertEqualsWithDelta(
            now()->addMinutes(15)->timestamp,
            $payment->expired_at->timestamp,
            5
        );
    }

    public function test_created_payment_respects_a_configured_expiry_override(): void
    {
        config(['services.secure_payment.expiry_minutes' => 45]);

        $payment = $this->service->createPayment($this->booking);

        $this->assertEqualsWithDelta(
            now()->addMinutes(45)->timestamp,
            $payment->expired_at->timestamp,
            5
        );
    }

    public function test_a_payment_still_within_a_shortened_configured_window_verifies_successfully(): void
    {
        config(['services.secure_payment.expiry_minutes' => 45]);
        $payment = $this->service->createPayment($this->booking);

        // Backdate the row's own expired_at column to simulate time passing, without touching
        // the encrypted card_data blob's internal timestamp (which is what the signed-timestamp
        // staleness check inside verifyPayment() actually reads against expiry_minutes()).
        $payment->update(['expired_at' => now()->addMinutes(45)]);

        $result = $this->service->verifyPayment($payment->reference_id);

        $this->assertTrue($result['success']);
    }

    public function test_a_payment_past_a_shortened_configured_expired_at_column_fails_verification(): void
    {
        config(['services.secure_payment.expiry_minutes' => 45]);
        $payment = $this->service->createPayment($this->booking);
        $payment->update(['expired_at' => now()->subMinute()]);

        $result = $this->service->verifyPayment($payment->reference_id);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $payment->fresh()->status);
    }
}
