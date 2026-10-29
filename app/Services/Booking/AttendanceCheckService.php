<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\Salon;
use App\Services\Links\ShortLinkService;
use App\Services\Push\AppMessenger;
use App\Services\Push\Outgoing;
use App\Services\Push\PushPolicy;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Sms\SmsText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * تأیید حضور با لینک (بسته‌ی 5ه، تصمیم ۱۶-۳). کلید مدیر: خاموش (پیش‌فرض)، فقط مشتری‌های بدقول، یا همه‌ی مشتری‌ها.
 * - ۲۴ ساعت پیش از نوبت: پیام با لینک «حضورم را تأیید می‌کنم» (پوش اپ یا پیامک از سهمیه‌ی سالن، AppMessenger).
 * - اگر تا ۳ ساعت مانده تأیید نشد: لغو خودکار و آزاد شدن ساعت؛ مشتری و متخصص خبر پیامک لغو را از BookingObserver می‌گیرند.
 * فقط نوبت‌های فعال «بدون پیش‌پرداخت» (نوبت پرداخت‌شده تعهد دارد و لغوش قوانین برگشت پول را درگیر می‌کند)؛ جلسه‌ی دوره نه.
 * نوبتی که پیامش واقعاً نرفت (بی شماره، سهمیه‌ی تمام‌شده) هرگز لغو خودکار نمی‌شود؛ نوبتی که کمتر از ۳ ساعت مانده ثبت شده هم نه.
 */
class AttendanceCheckService
{
    public const REQUEST_HOURS = 24;

    public const DEADLINE_HOURS = 3;

    /** دلیل لغو خودکار (پیامک متخصص هم از همین می‌خواند — SmsText::cancelledForSpecialist) */
    public const CANCELLATION_REASON = 'حضور تأیید نشد';

    public function __construct(
        private readonly AppMessenger $messenger,
        private readonly ShortLinkService $links,
        private readonly NoShowRecord $noShows,
    ) {}

    /** @return array{requested: int, cancelled: int} */
    public function runAll(): array
    {
        $salonIds = app(CurrentSalon::class)->allSalons(fn () => BookingPolicy::query()
            ->where('attendance_check', '!=', BookingPolicy::ATTENDANCE_OFF)->pluck('salon_id'));

        $totals = ['requested' => 0, 'cancelled' => 0];
        foreach (Salon::query()->whereKey($salonIds)->get() as $salon) {
            try {
                $result = app(CurrentSalon::class)->withSalon($salon, fn () => [
                    'cancelled' => $this->cancelUnconfirmed(),
                    'requested' => $this->sendRequests($salon),
                ]);
                $totals['requested'] += $result['requested'];
                $totals['cancelled'] += $result['cancelled'];
            } catch (\Throwable $e) {
                Log::error('Attendance check failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            }
        }

        return $totals;
    }

    /** سالن جاری: درخواست تأیید برای نوبت‌های ۳ تا ۲۴ ساعت آینده */
    public function sendRequests(Salon $salon): int
    {
        $policy = BookingPolicy::current();
        if ($policy->attendance_check === BookingPolicy::ATTENDANCE_OFF) {
            return 0;
        }

        $bookings = $this->eligible()
            ->whereNull('attendance_requested_at')
            ->where('booking_time', '>', now()->addHours(self::DEADLINE_HOURS)->format('Y-m-d H:i:s'))
            ->where('booking_time', '<=', now()->addHours(self::REQUEST_HOURS)->format('Y-m-d H:i:s'))
            ->with(['user', 'service'])
            ->get();

        if ($policy->attendance_check === BookingPolicy::ATTENDANCE_FLAGGED) {
            $flagged = $this->noShows->labelsFor($bookings->pluck('user_id'));
            $bookings = $bookings->filter(fn (Booking $b) => isset($flagged[$b->user_id]));
        }

        $sent = 0;
        foreach ($bookings as $booking) {
            $sent += $this->request($booking, $salon) ? 1 : 0;
        }

        return $sent;
    }

    /** سالن جاری: نوبت‌هایی که پیامشان رفت و تا ۳ ساعت مانده تأیید نشدند لغو می‌شوند */
    public function cancelUnconfirmed(): int
    {
        $ids = $this->eligible()
            ->whereNotNull('attendance_requested_at')
            ->whereNull('attendance_confirmed_at')
            ->where('booking_time', '>', now()->format('Y-m-d H:i:s'))
            ->where('booking_time', '<=', now()->addHours(self::DEADLINE_HOURS)->format('Y-m-d H:i:s'))
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            $cancelled = DB::transaction(function () use ($id) {
                $booking = Booking::query()->whereKey($id)->lockForUpdate()->first();
                if (! $booking || ! in_array($booking->status, ['pending', 'confirmed'], true) || $booking->attendance_confirmed_at) {
                    return false;
                }

                // BookingObserver: پیامک لغو به مشتری (با دلیل) و به متخصص
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => 'system',
                    'cancelled_at' => now(),
                    'cancellation_reason' => self::CANCELLATION_REASON,
                ]);

                return true;
            });
            $count += $cancelled ? 1 : 0;
        }

        return $count;
    }

    /** مشتری از لینک تأیید کرد @return bool true = همین الان تأیید شد */
    public function confirm(Booking $booking): bool
    {
        return Booking::query()->whereKey($booking->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('attendance_confirmed_at')
            ->toBase()->update(['attendance_confirmed_at' => now(), 'updated_at' => now()]) === 1;
    }

    public function findByToken(string $token): ?Booking
    {
        return Booking::query()->with(['service', 'specialist'])->where('attendance_token_hash', hash('sha256', $token))->first();
    }

    private function eligible()
    {
        return Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('prepayment_required', false)
            ->where('payment_status', '!=', 'paid')
            ->whereNull('customer_course_id');
    }

    private function request(Booking $booking, Salon $salon): bool
    {
        if (! $booking->user?->phone) {
            return false;
        }

        $token = Str::random(48);
        $link = $this->links->shorten(
            route('attendance.show', ['salon_slug' => $salon->slug, 'token' => $token]),
            $booking->booking_time?->copy()->addHour(),
        );

        $sent = $this->messenger->deliver(new Outgoing(
            recipient: $booking->user,
            phone: $booking->user->phone,
            text: SmsText::attendanceRequest($booking, $link),
            salonId: (int) $booking->salon_id,
            smsFeature: Feature::SmsCustomer,
            pushKey: PushPolicy::BOOKING_ATTENDANCE_REQUEST_CUSTOMER,
            data: ['booking_id' => $booking->id, 'type' => 'attendance'],
            inbox: true,
        ));

        if ($sent) {
            $booking->forceFill(['attendance_requested_at' => now(), 'attendance_token_hash' => hash('sha256', $token)])->saveQuietly();
        }

        return $sent;
    }
}
