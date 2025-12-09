<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SMSService;
use Carbon\Carbon;
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
            ->with(['user', 'specialist', 'service'])
            ->get();

        $smsService = new SMSService();
        $reminderCount = 0;

        foreach ($bookings as $booking) {
            $customerMessage = sprintf(
                "%s عزیز، سلام 👋\n\n".
                "⏰ یادآوری نوبت فردا:\n\n".
                "📋 سرویس: %s\n".
                "📅 تاریخ: %s\n".
                "🕐 ساعت: %s\n".
                "👤 متخصص: %s\n".
                "🔢 کد پیگیری: #%s\n\n".
                "⚠️ لطفاً 15 دقیقه قبل حضور داشته باشید.\n\n".
                "📞 برای هرگونه تغییر با ما تماس بگیرید.",
                $booking->user->name,
                $booking->service->name,
                verta($booking->booking_time)->format('Y/m/d'),
                verta($booking->booking_time)->format('H:i'),
                $booking->specialist->name,
                $booking->id
            );

            $smsService->send($booking->user->phone, $customerMessage);

            $specialistMessage = sprintf(
                "%s عزیز، سلام 👋\n\n".
                "⏰ یادآوری نوبت فردا:\n\n".
                "👤 مشتری: %s\n".
                "📱 تماس: %s\n".
                "📋 سرویس: %s\n".
                "📅 تاریخ: %s\n".
                "🕐 ساعت: %s\n".
                "🔢 کد پیگیری: #%s\n\n".
                "🙏 منتظر حضور شما در زمان مقرر هستیم.",
                $booking->specialist->name,
                $booking->user->name,
                $booking->user->phone,
                $booking->service->name,
                verta($booking->booking_time)->format('Y/m/d'),
                verta($booking->booking_time)->format('H:i'),
                $booking->id
            );

            $smsService->send($booking->specialist->phone, $specialistMessage);

            $booking->update(['reminder_sent' => true]);
            $reminderCount++;
        }

        $this->info("✅ تعداد {$reminderCount} یادآوری ارسال شد.");
        return 0;
    }
}
