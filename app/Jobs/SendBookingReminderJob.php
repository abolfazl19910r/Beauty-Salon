<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Replaces the synchronous SMS loop in SendBookingReminders (bookings:send-reminders).
 *
 * Previously, the entire loop (for all tomorrow's appointments) was executed inside the command itself, without a queue;
 * meaning that the command would make synchronous calls to Kavenegar for the number of appointments × 2 (without a separate
 * timeout), and if SMSService::send threw for one appointment, the entire
 * command (and reminders for the remaining appointments) would stop. Now each appointment has a separate and independent Job:
 * An error/timeout on one appointment does not prevent reminders for the remaining appointments from arriving.
 *
 * Note: `reminder_sent` is still set by the command itself (SendBookingReminders), before
 * dispatching this Job — not inside the handle() of this Job. Same pattern
 * SendLoginVerificationCodeJob (fast save/sync + just HTTP send in queue).
 */
class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // Job's own time limit — even if Kavenegar responds slowly, the Worker will not wait for this Job    public int $timeout = 15;

    public function __construct(protected int $bookingId) {}

    public function handle(SMSService $smsService): void
    {
        $booking = Booking::with(['user', 'specialist', 'service'])->find($this->bookingId);

        if (! $booking || ! $booking->user || ! $booking->specialist || ! $booking->service) {
            Log::warning('SendBookingReminderJob: booking/related model not found, skipping', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        $customerMessage = sprintf(
            "%s عزیز، سلام 👋\n\n".
            "⏰ یادآوری نوبت:\n\n".
            "📋 سرویس: %s\n".
            "📅 تاریخ: %s\n".
            "🕐 ساعت: %s\n".
            "👤 متخصص: %s\n".
            "🔢 کد پیگیری: #%s\n\n".
            "⚠️ لطفاً 15 دقیقه قبل حضور داشته باشید.\n\n".
            '📞 برای هرگونه تغییر با ما تماس بگیرید.',
            $booking->user->name,
            $booking->service->name,
            verta($booking->booking_time)->format('Y/m/d'),
            verta($booking->booking_time)->format('H:i'),
            $booking->specialist->name,
            $booking->id
        );

        $specialistMessage = sprintf(
            "%s عزیز، سلام 👋\n\n".
            "⏰ یادآوری نوبت:\n\n".
            "👤 مشتری: %s\n".
            "📱 تماس: %s\n".
            "📋 سرویس: %s\n".
            "📅 تاریخ: %s\n".
            "🕐 ساعت: %s\n".
            "🔢 کد پیگیری: #%s\n\n".
            '🙏 منتظر حضور شما در زمان مقرر هستیم.',
            $booking->specialist->name,
            $booking->user->name,
            $booking->user->phone,
            $booking->service->name,
            verta($booking->booking_time)->format('Y/m/d'),
            verta($booking->booking_time)->format('H:i'),
            $booking->id
        );

        $customerSent = $smsService->send($booking->user->phone, $customerMessage);
        $specialistSent = $smsService->send($booking->specialist->phone, $specialistMessage);

        if (! $customerSent || ! $specialistSent) {
            Log::error('SendBookingReminderJob: یکی از دو پیامک یادآوری ارسال نشد', [
                'booking_id' => $booking->id,
                'customer_sent' => $customerSent,
                'specialist_sent' => $specialistSent,
            ]);
        }
    }
}
