<?php

namespace App\Jobs;

use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\SMSService;
use App\Support\Queues;
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

    public function __construct(protected int $bookingId)
    {
        $this->onQueue(Queues::SMS);
    }

    /** سالنِ همین نوبت برای worker (SetSalonForQueuedJob، حالت سخت‌گیر ۲۰۲۶-۱۰-۰۱) */
    public function salonId(): ?int
    {
        $salonId = \Illuminate\Support\Facades\DB::table('bookings')->where('id', $this->bookingId)->value('salon_id');

        return $salonId ? (int) $salonId : null;
    }

    public function handle(SMSService $smsService, BookingRepositoryInterface $bookingRepository): void
    {
        $booking = $bookingRepository->query()->with(['user', 'specialist', 'service'])->find($this->bookingId);

        if (! $booking || ! $booking->user || ! $booking->specialist || ! $booking->service) {
            Log::warning('SendBookingReminderJob: booking/related model not found, skipping', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        $customerMessage = \App\Support\Sms\SmsText::reminderForCustomer($booking);
        $specialistMessage = \App\Support\Sms\SmsText::reminderForSpecialist($booking);

        // ربات (اگر مشتری/متخصص وصل کرده‌اند)؛ یادآوری مثل پیامکش تنظیم جدا ندارد — ۲۰۲۶-۱۰-۰۱
        $bot = app(\App\Services\Bot\BotMessenger::class);
        $bot->send($booking->user, $customerMessage, null, $booking->salon_id);
        $bot->send($booking->specialist, $specialistMessage, null, $booking->salon_id);

        // پوش اپ یا پیامک (بسته‌ی ۴): یادآوری «فوری» است — بدون رسید در ۶۰ ثانیه پیامک. یادآوری مثل قبل سوییچ تنظیمات ندارد.
        $messenger = app(\App\Services\Push\AppMessenger::class);
        $customerSent = $messenger->deliver(new \App\Services\Push\Outgoing(
            recipient: $booking->user,
            phone: $booking->user->phone,
            text: $customerMessage,
            salonId: $booking->salon_id,
            smsFeature: \App\Support\Features\Feature::SmsReminders,
            pushKey: \App\Services\Push\PushPolicy::BOOKING_REMINDER_CUSTOMER,
            data: ['booking_id' => $booking->id],
            inbox: true,
        ));
        $specialistSent = $messenger->deliver(new \App\Services\Push\Outgoing(
            recipient: $booking->specialist,
            phone: $booking->specialist->phone,
            text: $specialistMessage,
            salonId: $booking->salon_id,
            smsFeature: \App\Support\Features\Feature::SmsReminders,
            pushKey: \App\Services\Push\PushPolicy::BOOKING_REMINDER_SPECIALIST,
            data: ['booking_id' => $booking->id],
            inbox: true,
        ));

        // یادآوری پیامکی در پلن سالن نیست (بسته‌ی پلن‌ها) — نفرستادن عمدی است، نه خطا
        if (! \App\Models\Salon::featureEnabledFor($booking->salon_id, \App\Support\Features\Feature::SmsReminders)) {
            return;
        }

        if (! $customerSent || ! $specialistSent) {
            Log::error('SendBookingReminderJob: یکی از دو پیامک یادآوری ارسال نشد', [
                'booking_id' => $booking->id,
                'customer_sent' => $customerSent,
                'specialist_sent' => $specialistSent,
            ]);
        }
    }
}
