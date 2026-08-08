<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send SMS reminders for upcoming bookings to customers and specialists';

    public function handle()
    {
        // Update (2026-07-25): Previously, this command would run once a day (18:00)
        // and would remind all shifts for the next 24 hours — i.e. for morning shifts
        // that day, there would be no reminder at all (because by the time the command was run, they would have expired).
        // Now this command runs every few minutes (look at the bootstrap schedule) and
        // only picks shifts that are "55-65 minutes away" — i.e. each shift
        // gets a reminder just once, exactly 1 hour before it. The 10-minute interval was intentionally
        // chosen to overlap with the command's execution interval (every 5 or 10 minutes) and
        // no shifts are missed between two consecutive runs.
        $bookings = Booking::where('booking_time', '>=', now()->addMinutes(55))
            ->where('booking_time', '<=', now()->addMinutes(65))
            ->where('status', 'confirmed')
            ->where('reminder_sent', false)
            ->select('id')
            ->get();

        $reminderCount = 0;

        foreach ($bookings as $booking) {
            // reminder_sent is set here (before dispatch), not inside the Job —
            // so that the command is not re-selected/reminded on the next execution,
            // even if the Job is still in the queue waiting to be executed.
            $booking->update(['reminder_sent' => true]);

            SendBookingReminderJob::dispatch($booking->id);
            $reminderCount++;
        }

        $this->info("✅ تعداد {$reminderCount} یادآوری به صف ارسال شد.");

        return 0;
    }
}
