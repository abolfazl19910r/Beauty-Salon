<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send SMS reminders for upcoming bookings';

    public function handle()
    {
        $bookings = Booking::where('booking_time', '>', now())
            ->where('booking_time', '<', now()->addDay())
            ->where('status', 'confirmed')
            ->where('reminder_sent', false)
            ->get();

        $smsService = new SMSService();

        foreach ($bookings as $booking) {
            $message = sprintf(
                'یادآوری: نوبت شما در تاریخ %s با متخصص %s',
                verta($booking->booking_time)->format('Y/m/d H:i'),
                $booking->specialist->name
            );

            $smsService->send($booking->user->phone, $message);
            $booking->update(['reminder_sent' => true]);
        }
    }
}
