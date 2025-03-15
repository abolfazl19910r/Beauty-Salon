<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Exception;

class BookingService
{
    protected Specialist $specialist;
    protected Booking $booking;
    protected DiscountCode $discountCode;

    /**
     *
     * @param Specialist $specialist
     * @param Booking $booking
     * @param DiscountCode $discountCode
     * @return void
     */
    public function __construct(Specialist $specialist, Booking $booking, DiscountCode $discountCode)
    {
        $this->specialist = $specialist;
        $this->booking = $booking;
        $this->discountCode = $discountCode;
    }

    /**
     *
     * @param int $specialistId
     * @param string $bookingTime
     * @return bool
     */
    public function isTimeAvailable(int $specialistId, string $bookingTime): bool
    {
        try {
            $specialist = $this->specialist->findOrFail($specialistId);
            $bookingDate = date('Y-m-d', strtotime($bookingTime));
            $bookingTimeOnly = date('H:i', strtotime($bookingTime));

            $availableSlots = $specialist->getAvailableSlots($bookingDate);

            return in_array($bookingTimeOnly, $availableSlots);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param Booking $booking
     * @param string $code
     * @return array
     */
    public function applyDiscountCode(Booking $booking, string $code): array
    {
        $discountCode = $this->discountCode->where('code', $code)->first();

        if (!$discountCode || !$discountCode->isValid()) {
            return [
                'success' => false,
                'message' => 'کد تخفیف نامعتبر است.'
            ];
        }

        $discountAmount = $discountCode->type === 'percentage'
            ? ($booking->prepayment_amount * $discountCode->amount / 100)
            : $discountCode->amount;

        $finalAmount = max(0, $booking->prepayment_amount - $discountAmount);

        $booking->update([
            'discount_code' => $code,
            'discount_amount' => $discountAmount,
            'prepayment_amount' => $finalAmount
        ]);

        $discountCode->increment('used_count');

        return [
            'success' => true,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'message' => 'کد تخفیف با موفقیت اعمال شد.'
        ];
    }

    /**
     *
     * @param int $specialistId
     * @param string $yearMonth
     * @return array
     */
    public function getMonthAvailability(int $specialistId, string $yearMonth): array
    {
        try {
            $specialist = $this->specialist->findOrFail($specialistId);
            return $specialist->getMonthAvailability($yearMonth);
        } catch (Exception $e) {
            return [
                'available_days' => [],
                'holiday_days' => [],
                'fully_booked_days' => []
            ];
        }
    }

    /**
     *
     * @param float $servicePrice
     * @param string|null $code
     * @return array
     */
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

        $finalAmount = max(0, $prepaymentAmount - $discountAmount);

        return [
            'original_amount' => $prepaymentAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'discount_code' => $discountCode ? $discountCode->code : null
        ];
    }

    /**
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
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

    /**
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
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
}
