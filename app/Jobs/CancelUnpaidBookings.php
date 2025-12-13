<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CancelUnpaidBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $backoff = 60;

    public function handle(SMSService $smsService): void
    {
        $expiredBookings = Booking::where('status', 'pending_payment')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<=', Carbon::now()->subMinutes(30))
            ->get();

        $cancelledCount = 0;
        $failedCount = 0;

        foreach ($expiredBookings as $booking) {
            try {
                DB::transaction(function() use ($booking, $smsService, &$cancelledCount) {
                    $booking->update([
                        'status' => 'cancelled',
                        'cancelled_by' => 'system',
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'عدم تکمیل پرداخت در زمان مقرر (30 دقیقه)'
                    ]);

                    if ($booking->user && $booking->user->phone) {
                        $message = sprintf(
                            'نوبت شما در تاریخ %s ساعت %s به دلیل عدم پرداخت لغو شد. برای رزرو مجدد به سایت مراجعه کنید.',
                            verta($booking->booking_time)->format('Y/m/d'),
                            verta($booking->booking_time)->format('H:i')
                        );

                        try {
                            $smsService->send($booking->user->phone, $message);
                        } catch (\Exception $smsException) {
                            Log::warning('Failed to send SMS for cancelled booking', [
                                'booking_id' => $booking->id,
                                'error' => $smsException->getMessage()
                            ]);
                        }
                    }

                    $cancelledCount++;
                });
            } catch (\Exception $e) {
                $failedCount++;

                Log::error('Error cancelling unpaid booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CancelUnpaidBookings job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
