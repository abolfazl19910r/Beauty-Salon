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
        $bookings = Booking::where('booking_time', '>', now())
            ->where('booking_time', '<', now()->addDay())
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
