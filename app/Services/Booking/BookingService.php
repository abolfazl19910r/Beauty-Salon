<?php

namespace App\Services\Booking;

use App\Events\Booking\BookingCancelled;
use App\Exceptions\BookingNotAvailableException;
use App\Exceptions\DiscountCodeInvalidException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Models\WalletSetting;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Services\Discount\DiscountCalculator;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(protected readonly Specialist $specialist, protected readonly Booking $booking, protected readonly DiscountCode $discountCode, protected readonly DiscountCalculator $discountCalculator, protected readonly BeautyService $beautyService) {}

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
                'available_days' => [],
                'holiday_days' => [],
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
     *
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

        $result = $this->discountCalculator->calculate($discountCode, $baseAmount ?? (float) WalletSetting::get()->minimum_prepayment_amount);

        return [
            'valid' => true,
            'discount_amount' => $result['discount_amount'],
            // Note: The key final_price (not final_amount) is intentional, because preview consumers have already opened an account on the same key name in the frontend.
            'final_price' => $result['final_amount'],
            'message' => 'کد تخفیف معتبر است.',
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

            // ⭐ Same business decision as createBooking(): discount reduces what's owed to the
            // specialist in person (remaining_amount), never the amount actually charged online
            // (prepayment_amount stays untouched here).
            $booking->update([
                'discount_code' => $code,
                'discount_amount' => $result['discount_amount'],
            ]);

            $lockedDiscountCode->incrementUsage();

            return [
                'success' => true,
                'discount_amount' => $result['discount_amount'],
                'prepayment_amount' => (float) $booking->prepayment_amount,
                'remaining_amount' => $booking->fresh(['service'])->remaining_amount,
                'message' => 'کد تخفیف اعمال شد؛ این مبلغ از باقی‌مانده‌ای که موقع نوبت پرداخت می‌کنید کسر شد.',
            ];
        });
    }

    public function calculatePrepayment(float $servicePrice, ?string $code = null): array
    {
        $prepaymentAmount = WalletSetting::get()->calculatePrepaymentAmount($servicePrice);
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
            'final_amount' => max(0, $prepaymentAmount - $discountAmount),
            'discount_code' => $discountCode?->code,
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

        // ⚠️ Real service price must be used here (BeautyService was never fetched in this method
        // before): passing the flat minimum/constant here would silently ignore the actual service
        // price for every booking's prepayment, regardless of the discount question below.
        $service = $this->beautyService->findOrFail($serviceId);
        $prepaymentData = $this->calculatePrepayment((float) $service->price, $discountCode);

        return DB::transaction(function () use (
            $userId, $serviceId, $specialistId, $bookingTime, $discountCode, $prepaymentData, $specialist
        ) {
            $booking = Booking::create([
                'service_id' => $serviceId,
                'specialist_id' => $specialistId,
                'user_id' => $userId,
                'booking_time' => $bookingTime,
                'status' => $specialist->auto_confirm_bookings ? 'confirmed' : 'pending_payment',
                // ⭐ By explicit business decision: a discount code never reduces the amount actually
                // charged online (prepayment) — it only reduces the "remaining" amount the customer
                // settles with the specialist in person (see Booking::getRemainingAmountAttribute()).
                // This keeps the prepayment/wallet/commission math completely untouched by discounts
                // (which only ever affected the true charged amount previously) while still giving
                // the customer a real, non-circular discount instead of a wash (see the numeric
                // walkthrough that motivated this: discounting the prepayment while remaining =
                // price - prepayment meant remaining grew by the same amount the prepayment shrank,
                // netting the customer zero actual savings).
                'prepayment_amount' => $prepaymentData['original_amount'],
                'payment_status' => 'unpaid',
                'discount_code' => $discountCode,
                'discount_amount' => $prepaymentData['discount_amount'],
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
                        'booking_id' => $booking->id,
                    ]);
                }
            }

            $booking->load(['service', 'specialist', 'user']);

            try {
                $booking->user?->notify(new CustomerBookingNotification($booking));
            } catch (Exception $e) {
                Log::warning('خطا در ارسال نوتیفیکیشن ثبت نوبت', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $booking;
        });
    }

    /**
     * ⭐ Fix (fix/admin-booking-slot-conflict, commit 2): AdminBookingController::store()
     * previously called Booking::create() directly with zero availability check, so a manually
     * entered phone/walk-in booking could silently collide with an online booking (or another
     * manual one) for the exact same specialist+time. This method gives the admin-side manual
     * flow the SAME availability check the online flow (createBooking() above) already has —
     * via the shared Specialist::getAvailableSlots() — instead of a second, divergent
     * implementation. A DB-level unique index on bookings.active_slot_key (generated column,
     * see migration 2026_08_29_000001) is the actual last line of defense against the race
     * where two concurrent requests both pass this check before either commits; the
     * QueryException catch below turns that rare DB-level collision into the same friendly
     * BookingNotAvailableException the pre-check throws, so the caller never sees a raw SQL
     * error either way.
     *
     * @param  array{service_id:int,specialist_id:int,user_id:int,booking_time:string,status:string,payment_status:string,source:string,notes?:string|null}  $data
     *
     * @throws BookingNotAvailableException
     */
    public function createManualBooking(array $data): Booking
    {
        $specialist = $this->specialist->findOrFail($data['specialist_id']);
        // findOrFail() only to give a clean 404 on a bad service_id — intentionally not passed
        // into getAvailableSlots() below, matching createBooking()'s existing behavior exactly
        // (see note there: it also omits duration and lets the model default to 30 minutes).
        // Passing a real duration here while the online path doesn't would make the two paths
        // disagree about which slots are "available", reopening exactly the conflict this method
        // exists to close — so this deliberately stays consistent with createBooking() rather
        // than being "more correct" on its own. Fixing duration-aware availability for both
        // paths together is a separate, pre-existing concern outside this branch's scope.
        $this->beautyService->findOrFail($data['service_id']);

        $bookingDate = date('Y-m-d', strtotime($data['booking_time']));
        $bookingTimeOnly = date('H:i', strtotime($data['booking_time']));

        $availableSlots = $specialist->getAvailableSlots($bookingDate);

        if (! in_array($bookingTimeOnly, $availableSlots)) {
            throw BookingNotAvailableException::slotTaken(
                "Manual booking slot {$data['booking_time']} is not available for specialist {$data['specialist_id']}.",
                ['specialist_id' => $data['specialist_id'], 'booking_time' => $data['booking_time'], 'source' => $data['source']]
            );
        }

        return DB::transaction(function () use ($data) {
            try {
                return Booking::create([
                    'service_id' => $data['service_id'],
                    'specialist_id' => $data['specialist_id'],
                    'user_id' => $data['user_id'],
                    'booking_time' => $data['booking_time'],
                    'status' => $data['status'],
                    'payment_status' => $data['payment_status'],
                    'source' => $data['source'],
                    'notes' => $data['notes'] ?? null,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->isDuplicateActiveSlotError($e)) {
                    throw BookingNotAvailableException::slotTaken(
                        "Race condition: slot {$data['booking_time']} for specialist {$data['specialist_id']} was taken concurrently.",
                        ['specialist_id' => $data['specialist_id'], 'booking_time' => $data['booking_time'], 'source' => $data['source']]
                    );
                }

                throw $e;
            }
        });
    }

    /**
     * The exact duplicate-key error message differs by driver — MySQL names the index
     * ("...for key 'bookings_active_slot_unique'"), SQLite names the generated column instead
     * ("UNIQUE constraint failed: bookings.active_slot_key"), Postgres names the constraint
     * ('duplicate key value violates unique constraint "bookings_active_slot_unique"'). Checked
     * directly against real error strings from all three before settling on 'active_slot' as
     * the one substring common to all of them, rather than assuming a single driver's format —
     * this project's test suite runs SQLite (phpunit.xml) while production runs MySQL, so a
     * MySQL-only string match would have silently never matched in any test.
     */
    public function isDuplicateActiveSlotError(\Illuminate\Database\QueryException $e): bool
    {
        return (int) $e->getCode() === 23000
            && str_contains($e->getMessage(), 'active_slot');
    }

    /**
     * ⭐ Fix (fix/admin-booking-slot-conflict, commit 4): AdminBookingService::updateFull() —
     * the admin "edit booking" form — previously let specialist_id/booking_time be changed with
     * zero availability check, the exact same gap store() had before commit 2 closed it for
     * booking creation. Called from AdminBookingService::updateFull() only when specialist_id
     * or booking_time actually changed (unchanged edits skip this entirely).
     *
     * getAvailableSlots() is passed $booking->id as the exclude parameter (see the addition to
     * Specialist::getAvailableSlots() in this same commit) — without it, a booking whose time
     * *isn't* being changed would incorrectly collide with itself, since it already occupies
     * that exact specialist+time in the database.
     *
     * @throws BookingNotAvailableException
     */
    public function assertManualRescheduleAvailable(Booking $booking, int $specialistId, string $bookingTime): void
    {
        $specialist = $this->specialist->findOrFail($specialistId);
        $bookingDate = date('Y-m-d', strtotime($bookingTime));
        $bookingTimeOnly = date('H:i', strtotime($bookingTime));

        $availableSlots = $specialist->getAvailableSlots($bookingDate, null, $booking->id);

        if (! in_array($bookingTimeOnly, $availableSlots)) {
            throw BookingNotAvailableException::slotTaken(
                "Manual reschedule slot {$bookingTime} is not available for specialist {$specialistId} (excluding booking #{$booking->id}).",
                ['booking_id' => $booking->id, 'specialist_id' => $specialistId, 'booking_time' => $bookingTime]
            );
        }
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
                'status' => 'cancelled',
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
