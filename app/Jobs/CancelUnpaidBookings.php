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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public function __construct()
    {
        $this->onQueue(Queues::PAYMENTS);
    }

    public function handle(SMSService $smsService, BookingRepositoryInterface $bookingRepository): void
    {
        // روی همه‌ی سالن‌ها می‌گردد (صریح، حالت سخت‌گیر ۲۰۲۶-۱۰-۰۱)؛ هر نوبت داخل سالن خودش لغو می‌شود
        $currentSalon = app(\App\Support\CurrentSalon::class);
        $expiredBookings = $currentSalon->allSalons(fn () => $bookingRepository->query()
            ->where('status', 'pending_payment')
            ->where('payment_status', 'unpaid')
            ->where('prepayment_required', true) // بسته‌ی 5ه: فقط نوبتی که واقعاً پیش‌پرداخت لازم دارد
            ->where('created_at', '<=', Carbon::now()->subMinutes(\App\Support\Booking\PurchaseTerms::UNPAID_HOLD_MINUTES))
            ->withoutPaymentInProgress() // ⭐ مشتری هنوز در صفحه‌ی بانکه — لغو نکن
            ->get());

        $cancelledCount = 0;
        $failedCount = 0;

        foreach ($expiredBookings as $booking) {
            try {
                $currentSalon->withSalon((int) $booking->salon_id, fn () => DB::transaction(function () use ($booking, &$cancelledCount) {
                    $booking->update([
                        'status' => 'cancelled',
                        'cancelled_by' => 'system',
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'عدم تکمیل پرداخت در زمان مقرر (30 دقیقه)',
                    ]);

                    if ($booking->user && $booking->user->phone) {
                        $message = \App\Support\Sms\SmsText::bookingCancelledUnpaid($booking);
                        try {
                            // پوش اپ یا پیامک از سهمیه‌ی سالن (تصمیم ۲۰۲۶-۰۹-۳۰، بسته‌ی ۴)
                            app(\App\Services\Push\AppMessenger::class)->deliver(new \App\Services\Push\Outgoing(
                                recipient: $booking->user,
                                phone: $booking->user->phone,
                                text: $message,
                                salonId: $booking->salon_id,
                                smsFeature: \App\Support\Features\Feature::SmsCustomer,
                                pushKey: \App\Services\Push\PushPolicy::BOOKING_CANCELLED_UNPAID_CUSTOMER,
                                data: ['booking_id' => $booking->id],
                                inbox: true,
                            ));
                        } catch (\Exception $smsException) {
                            Log::warning('Failed to send SMS for cancelled booking', [
                                'booking_id' => $booking->id,
                                'error' => $smsException->getMessage(),
                            ]);
                        }
                    }

                    $cancelledCount++;
                }));
            } catch (\Exception $e) {
                $failedCount++;

                Log::error('Error cancelling unpaid booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CancelUnpaidBookings job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
