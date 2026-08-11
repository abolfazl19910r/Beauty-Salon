<?php

namespace Tests\Feature\Console;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendBookingRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(int $minutesFromNow, string $status = 'confirmed', bool $reminderSent = false): Booking
    {
        return Booking::factory()->create([
            'booking_time' => now()->addMinutes($minutesFromNow),
            'status' => $status,
            'reminder_sent' => $reminderSent,
        ]);
    }

    public function test_a_booking_exactly_one_hour_away_is_reminded(): void
    {
        Queue::fake();

        $booking = $this->makeBooking(60);

        $this->artisan('bookings:send-reminders');

        Queue::assertPushed(SendBookingReminderJob::class, fn ($job) => $this->jobBookingId($job) === $booking->id);
        $this->assertTrue($booking->fresh()->reminder_sent);
    }

    public function test_a_booking_just_inside_the_window_boundaries_is_reminded(): void
    {
        Queue::fake();

        $lower = $this->makeBooking(55);
        $upper = $this->makeBooking(65);

        $this->artisan('bookings:send-reminders');

        Queue::assertPushed(SendBookingReminderJob::class, 2);
        $this->assertTrue($lower->fresh()->reminder_sent);
        $this->assertTrue($upper->fresh()->reminder_sent);
    }

    public function test_a_booking_outside_the_window_is_not_reminded(): void
    {
        Queue::fake();

        $tooSoon = $this->makeBooking(30);
        $tooLate = $this->makeBooking(120);

        $this->artisan('bookings:send-reminders');

        Queue::assertNotPushed(SendBookingReminderJob::class);
        $this->assertFalse($tooSoon->fresh()->reminder_sent);
        $this->assertFalse($tooLate->fresh()->reminder_sent);
    }

    public function test_a_booking_that_already_had_its_reminder_sent_is_not_reminded_again(): void
    {
        Queue::fake();

        $booking = $this->makeBooking(60, reminderSent: true);

        $this->artisan('bookings:send-reminders');

        Queue::assertNotPushed(SendBookingReminderJob::class);
    }

    public function test_a_pending_or_cancelled_booking_in_the_window_is_not_reminded(): void
    {
        Queue::fake();

        $pending = $this->makeBooking(60, status: 'pending');
        $cancelled = $this->makeBooking(60, status: 'cancelled');

        $this->artisan('bookings:send-reminders');

        Queue::assertNotPushed(SendBookingReminderJob::class);
        $this->assertFalse($pending->fresh()->reminder_sent);
        $this->assertFalse($cancelled->fresh()->reminder_sent);
    }

    public function test_reminder_sent_is_flipped_synchronously_before_dispatch_even_though_the_job_itself_never_ran(): void
    {
        // reminder_sent must be true right after the command runs (queue not processed yet),
        // proving the flag is set by the command itself, not inside the job's handle().
        Queue::fake();

        $booking = $this->makeBooking(60);

        $this->artisan('bookings:send-reminders');

        $this->assertTrue($booking->fresh()->reminder_sent);
        Queue::assertPushed(SendBookingReminderJob::class, 1);
    }

    private function jobBookingId(SendBookingReminderJob $job): int
    {
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('bookingId');
        $property->setAccessible(true);

        return $property->getValue($job);
    }
}
