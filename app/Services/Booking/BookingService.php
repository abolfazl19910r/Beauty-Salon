<?php

namespace App\Services\Booking;

use App\Events\Booking\BookingCancelled;
use App\Exceptions\BookingNotAvailableException;
use App\Exceptions\DiscountCodeInvalidException;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Services\Discount\DiscountCalculator;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    protected const MINIMUM_PREPAYMENT = 50000.0;

    protected Specialist $specialist;
    protected Booking $booking;
    protected DiscountCode $discountCode;
    protected DiscountCalculator $discountCalculator;

    public function __construct(
        Specialist $specialist,
        Booking $booking,
        DiscountCode $discountCode,
        DiscountCalculator $discountCalculator
    ) {
        $this->specialist = $specialist;
        $this->booking = $booking;
        $this->discountCode = $discountCode;
        $this->discountCalculator = $discountCalculator;
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
     * Check the validity of a discount code and preview its effect on a base amount, without persisting.
     * @throws DiscountCodeInvalidException
     */
    public function validateDiscountCode(string $code, int $userId, ?float $baseAmount = null): array
    {
        $discountCode = $this->discountCode->where('code', $code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            throw DiscountCodeInvalidException::because(
                "Code '{$code}' is invalid or expired.",
                'کد تخفیف نامعتبر است یا منقضی شده.',
                ['code' => $code, 'user_id' => $userId]
            );
        }

        if (! $discountCode->canBeUsedBy($userId)) {
            throw DiscountCodeInvalidException::because(
                "Code '{$code}' belongs to another user.",
                'این کد تخفیف متعلق به شما نیست.',
                ['code' => $code, 'user_id' => $userId, 'owner_id' => $discountCode->user_id]
            );
        }

        $result = $this->discountCalculator->calculate($discountCode, $baseAmount ?? self::MINIMUM_PREPAYMENT);

        return [
            'valid'           => true,
            'discount_amount' => $result['discount_amount'],
            // Note: The key final_price (not final_amount) is intentional, because preview consumers have already opened an account on the same key name in the frontend.
            'final_price'     => $result['final_amount'],
            'message'         => 'کد تخفیف معتبر است.',
        ];
    }

    /**
     * Persist the discount code on a specific order.
     *
     * Guards for this method (R-DiscountLogic):
     * - Ownership: User-specific discount code can no longer be used (canBeUsedBy)
     * - No re-apply: A order that already has a discount_code will not get a discount again
     * - No application on paid order: After payment, the discount will no longer be applied
     * - Atomicity: Update order + increment used_count in one transaction
     */
    public function applyDiscountCode(Booking $booking, string $code): array
    {
        $discountCode = $this->discountCode->where('code', $code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            return ['success' => false, 'message' => 'کد تخفیف نامعتبر است.'];
        }

        if (! $discountCode->canBeUsedBy($booking->user_id)) {
            return ['success' => false, 'message' => 'این کد تخفیف متعلق به شما نیست.'];
        }

        if ($booking->discount_code) {
            return ['success' => false, 'message' => 'کد تخفیف قبلاً برای این نوبت اعمال شده است.'];
        }

        if ($booking->payment_status === 'paid') {
            return ['success' => false, 'message' => 'این نوبت قبلاً پرداخت شده است.'];
        }

        /**
         * R-Observers: the checks above (isValid/canBeUsedBy) are only a fast pre-check outside
         * the transaction — two concurrent requests for the same near-exhausted code could both
         * pass them before either increments used_count, pushing it past max_uses (the exact
         * "used_count overflow" scenario flagged for review in this phase). The row is re-locked
         * and re-validated here, inside the transaction, right before incrementing, so only one
         * of two concurrent requests can actually consume the last remaining use.
         */
        return DB::transaction(function () use ($booking, $code) {
            $lockedDiscountCode = $this->discountCode->where('code', $code)->lockForUpdate()->first();

            if (! $lockedDiscountCode || ! $lockedDiscountCode->isValid()) {
                return ['success' => false, 'message' => 'کد تخفیف نامعتبر است.'];
            }

            if (! $lockedDiscountCode->canBeUsedBy($booking->user_id)) {
                return ['success' => false, 'message' => 'این کد تخفیف متعلق به شما نیست.'];
            }

            $result = $this->discountCalculator->calculate($lockedDiscountCode, (float) $booking->prepayment_amount);

            $booking->update([
                'discount_code'     => $code,
                'discount_amount'   => $result['discount_amount'],
                'prepayment_amount' => $result['final_amount'],
            ]);

            $lockedDiscountCode->incrementUsage();

            return [
                'success'         => true,
                'discount_amount' => $result['discount_amount'],
                'final_amount'    => $result['final_amount'],
                'message'         => 'کد تخفیف با موفقیت اعمال شد.',
            ];
        });
    }

    public function calculatePrepayment(float $servicePrice, ?string $code = null): array
    {
        $prepaymentAmount = max(self::MINIMUM_PREPAYMENT, $servicePrice * 0.3);
        $discountAmount = 0;
        $discountCode = null;

        if ($code) {
            $discountCode = $this->discountCode->where('code', $code)->first();

            if ($discountCode && $discountCode->isValid()) {
                $discountAmount = $this->discountCalculator
                    ->calculate($discountCode, $prepaymentAmount)['discount_amount'];
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

        $prepaymentData = $this->calculatePrepayment(self::MINIMUM_PREPAYMENT, $discountCode);

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
                $lockedDiscountCode = $this->discountCode->where('code', $discountCode)->lockForUpdate()->first();

                if ($lockedDiscountCode && $lockedDiscountCode->isValid()) {
                    $lockedDiscountCode->incrementUsage();
                } else {
                    // Extremely rare race: the code was exhausted by a concurrent request between the
                    // pre-transaction calculatePrepayment() check and this lock. The booking keeps the
                    // discount amount already calculated (so the customer isn't punished for a race
                    // they didn't cause), but we deliberately do not push used_count past max_uses.
                    Log::warning('⚠️ کد تخفیف بین محاسبه و رزرو نهایی به حداکثر استفاده رسید', [
                        'discount_code' => $discountCode,
                        'booking_id'    => $booking->id,
                    ]);
                }
            }

            $booking->load(['service', 'specialist', 'user']);

            try {
                $booking->user?->notify(new CustomerBookingNotification($booking));
            } catch (Exception $e) {
                Log::warning('خطا در ارسال نوتیفیکیشن ثبت نوبت', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            return $booking;
        });
    }

    /**
     * Fix (R-Events): previously this method did not set canceled_by (as a result
     * BookingObserver never executes the wallet refund path for this mode
     * would not) nor would it call notifications with the correct number of arguments
     * (Both BookingStatusUpdated and SpecialistBookingCancelledNotification
     * The second parameter is mandatory; A previous call with one less argument, one
     * \ArgumentCountError was thrown because it is not \Exception by
     * catch(Exception $e) was not caught here in the controller — i.e. canceling the queue
     * It was always failed by the customer with 500 fatal and the transaction was rolled back).
     */
    public function cancelBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status'       => 'cancelled',
                'cancelled_by' => 'customer',
                'cancelled_at' => now(),
            ]);

            event(new BookingCancelled($booking, 'customer'));

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
