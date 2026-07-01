<?php

namespace App\Services;

use App\Exceptions\BookingNotAvailableException;
use App\Exceptions\DiscountCodeInvalidException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Notifications\BookingNotification;
use App\Notifications\CustomerBookingNotification;
use App\Notifications\SpecialistBookingCancelledNotification;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    protected Specialist $specialist;
    protected Booking $booking;
    protected DiscountCode $discountCode;

    public function __construct(Specialist $specialist, Booking $booking, DiscountCode $discountCode)
    {
        $this->specialist = $specialist;
        $this->booking = $booking;
        $this->discountCode = $discountCode;
    }

    public function isTimeAvailable(int $specialistId, string $bookingTime): bool
    {
        try {
            $specialist = $this->specialist->findOrFail($specialistId);
            $bookingDate = date('Y-m-d', strtotime($bookingTime));
            $bookingTimeOnly = date('H:i', strtotime($bookingTime));

            return in_array($bookingTimeOnly, $specialist->getAvailableSlots($bookingDate));
        } catch (Exception $e) {
            return false;
        }
    }

    public function getMonthAvailability(int $specialistId, string $yearMonth): array
    {
        try {
            $specialist = $this->specialist->findOrFail($specialistId);

            return $specialist->getMonthAvailability($yearMonth);
        } catch (Exception $e) {
            return [
                'available_days'    => [],
                'holiday_days'      => [],
                'fully_booked_days' => [],
            ];
        }
    }

    public function getNextAvailableSlots(int $specialistId, int $count = 5): Collection
    {
        $specialist = $this->specialist->findOrFail($specialistId);
        $slots = collect();
        $date = Carbon::today();

        while ($slots->count() < $count && $date->lt(Carbon::today()->addDays(60))) {
            $daySlots = $specialist->getAvailableSlots($date->format('Y-m-d'));
            foreach ($daySlots as $slot) {
                $slots->push(['date' => $date->format('Y-m-d'), 'time' => $slot]);
                if ($slots->count() >= $count) {
                    break;
                }
            }
            $date->addDay();
        }

        return $slots;
    }

    public function getMonthlyAvailability(int $specialistId, string $yearMonth): array
    {
        return $this->getMonthAvailability($specialistId, $yearMonth);
    }

    /**
     *
     * @throws DiscountCodeInvalidException
     */
    public function validateDiscountCode(string $code, int $userId): array
    {
        $discountCode = $this->discountCode->where('code', $code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            throw DiscountCodeInvalidException::because(
                "Code '{$code}' is invalid or expired.",
                'کد تخفیف نامعتبر است یا منقضی شده.',
                ['code' => $code, 'user_id' => $userId]
            );
        }

        if ($discountCode->user_id && $discountCode->user_id !== $userId) {
            throw DiscountCodeInvalidException::because(
                "Code '{$code}' belongs to another user.",
                'این کد تخفیف متعلق به شما نیست.',
                ['code' => $code, 'user_id' => $userId, 'owner_id' => $discountCode->user_id]
            );
        }

        $prepaymentAmount = 50000;
        $discountAmount = $discountCode->type === 'percentage'
            ? ($prepaymentAmount * $discountCode->amount / 100)
            : $discountCode->amount;

        if (isset($discountCode->max_amount)) {
            $discountAmount = min($discountAmount, $discountCode->max_amount);
        }

        return [
            'valid'           => true,
            'discount_amount' => $discountAmount,
            'final_amount'    => max(0, $prepaymentAmount - $discountAmount),
            'message'         => 'کد تخفیف معتبر است.',
        ];
    }

    public function applyDiscountCode(Booking $booking, string $code): array
    {
        $discountCode = $this->discountCode->where('code', $code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            return ['success' => false, 'message' => 'کد تخفیف نامعتبر است.'];
        }

        $discountAmount = $discountCode->type === 'percentage'
            ? ($booking->prepayment_amount * $discountCode->amount / 100)
            : $discountCode->amount;

        if (isset($discountCode->max_amount)) {
            $discountAmount = min($discountAmount, $discountCode->max_amount);
        }

        $finalAmount = max(0, $booking->prepayment_amount - $discountAmount);

        $booking->update([
            'discount_code'     => $code,
            'discount_amount'   => $discountAmount,
            'prepayment_amount' => $finalAmount,
        ]);

        $discountCode->increment('used_count');

        return [
            'success'         => true,
            'discount_amount' => $discountAmount,
            'final_amount'    => $finalAmount,
            'message'         => 'کد تخفیف با موفقیت اعمال شد.',
        ];
    }

    public function calculatePrepayment(float $servicePrice, ?string $code = null): array
    {
        $prepaymentAmount = max(50000, $servicePrice * 0.3);
        $discountAmount = 0;
        $discountCode = null;

        if ($code) {
            $discountCode = $this->discountCode->where('code', $code)->first();

            if ($discountCode && $discountCode->isValid()) {
                $discountAmount = $discountCode->type === 'percentage'
                    ? ($prepaymentAmount * $discountCode->amount / 100)
                    : $discountCode->amount;
            }
        }

        return [
            'original_amount' => $prepaymentAmount,
            'discount_amount' => $discountAmount,
            'final_amount'    => max(0, $prepaymentAmount - $discountAmount),
            'discount_code'   => $discountCode?->code,
        ];
    }

    /**
     *
     * @throws BookingNotAvailableException
     */
    public function createBooking(
        int $userId,
        int $serviceId,
        int $specialistId,
        string $bookingTime,
        ?string $discountCode = null
    ): Booking {
        $specialist = $this->specialist->findOrFail($specialistId);
        $bookingDate = date('Y-m-d', strtotime($bookingTime));
        $bookingTimeOnly = date('H:i', strtotime($bookingTime));
        $availableSlots = $specialist->getAvailableSlots($bookingDate);

        if (! in_array($bookingTimeOnly, $availableSlots)) {
            throw BookingNotAvailableException::slotTaken(
                "Slot {$bookingTime} is not available for specialist {$specialistId}.",
                ['specialist_id' => $specialistId, 'booking_time' => $bookingTime]
            );
        }

        $prepaymentData = $this->calculatePrepayment(50000, $discountCode);

        return DB::transaction(function () use (
            $userId, $serviceId, $specialistId, $bookingTime, $discountCode, $prepaymentData, $specialist
        ) {
            $booking = Booking::create([
                'service_id'        => $serviceId,
                'specialist_id'     => $specialistId,
                'user_id'           => $userId,
                'booking_time'      => $bookingTime,
                'status'            => $specialist->auto_confirm_bookings ? 'confirmed' : 'pending_payment',
                'prepayment_amount' => $prepaymentData['final_amount'],
                'payment_status'    => 'unpaid',
                'discount_code'     => $discountCode,
                'discount_amount'   => $prepaymentData['discount_amount'],
            ]);

            if ($discountCode && $prepaymentData['discount_code']) {
                $this->discountCode->where('code', $discountCode)->increment('used_count');
            }

            $booking->load(['service', 'specialist', 'user']);

            try {
                $booking->user?->notify(new CustomerBookingNotification($booking));
                $booking->specialist?->notify(new BookingNotification($booking));
            } catch (Exception $e) {
                Log::warning('خطا در ارسال نوتیفیکیشن ثبت نوبت', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            try {
                $booking->user?->notify(new \App\Notifications\BookingStatusUpdated($booking));
                $booking->specialist?->notify(new SpecialistBookingCancelledNotification($booking));
            } catch (Exception $e) {
                Log::warning('خطا در ارسال نوتیفیکیشن لغو نوبت', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            return true;
        });
    }

    public function getUpcomingBookings(int $userId, int $limit = 5): Collection
    {
        return $this->booking
            ->where('user_id', $userId)
            ->where('booking_time', '>', Carbon::now())
            ->whereNotIn('status', ['cancelled'])
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->limit($limit)
            ->get();
    }

    public function getPastBookings(int $userId, int $limit = 5): Collection
    {
        return $this->booking
            ->where('user_id', $userId)
            ->where('booking_time', '<', Carbon::now())
            ->with(['service', 'specialist'])
            ->orderBy('booking_time', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserBookings(int $userId): Collection
    {
        return $this->booking
            ->where('user_id', $userId)
            ->with(['service', 'specialist'])
            ->orderBy('booking_time', 'desc')
            ->get();
    }
}
