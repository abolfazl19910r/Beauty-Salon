<?php

namespace App\Services\Booking;

use App\Events\Booking\BookingCancelled;
use App\Exceptions\BookingNotAvailableException;
use App\Exceptions\DiscountCodeInvalidException;
use App\Models\Booking;
use App\Models\WalletSetting;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Discount\DiscountCalculator;
use App\Support\Idempotency;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(
        protected readonly DiscountCodeRepositoryInterface $discountCodeRepository,
        protected readonly DiscountCalculator $discountCalculator,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function isTimeAvailable(int $specialistId, string $bookingTime): bool
    {
        try {
            $specialist = $this->specialistRepository->findOrFail($specialistId);
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
            $specialist = $this->specialistRepository->findOrFail($specialistId);

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
        $specialist = $this->specialistRepository->findOrFail($specialistId);
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

    public function validateDiscountCode(string $code, int $userId, ?float $baseAmount = null): array
    {
        $discountCode = $this->discountCodeRepository->findByCode($code);

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
            'final_price' => $result['final_amount'],
            'message' => 'کد تخفیف معتبر است.',
        ];
    }

    public function applyDiscountCode(Booking $booking, string $code): array
    {
        $discountCode = $this->discountCodeRepository->findByCode($code);

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

        return DB::transaction(function () use ($booking, $code) {
            $lockedDiscountCode = $this->discountCodeRepository->lockByCode($code);

            if (! $lockedDiscountCode || ! $lockedDiscountCode->isValid()) {
                return ['success' => false, 'message' => 'کد تخفیف نامعتبر است.'];
            }

            if (! $lockedDiscountCode->canBeUsedBy($booking->user_id)) {
                return ['success' => false, 'message' => 'این کد تخفیف متعلق به شما نیست.'];
            }

            $result = $this->discountCalculator->calculate($lockedDiscountCode, (float) $booking->prepayment_amount);

            $booking = $this->bookingRepository->update($booking, [
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
            $discountCode = $this->discountCodeRepository->findByCode($code);

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

    public function createBooking(
        int $userId,
        int $serviceId,
        int $specialistId,
        string $bookingTime,
        ?string $discountCode = null,
        ?string $idempotencyKey = null,
    ): Booking {
        // idempotency (۲۰۲۶-۱۰-۰۱): همان فرم دوبار فرستاده شد → همان نوبت اول (نه «این ساعت گرفته شده»)
        $fingerprint = Idempotency::fingerprint([
            'service_id' => $serviceId, 'specialist_id' => $specialistId, 'booking_time' => $bookingTime, 'discount_code' => $discountCode,
        ]);
        if ($existing = $this->replayedBooking($userId, $idempotencyKey, $fingerprint)) {
            return $existing;
        }

        $specialist = $this->specialistRepository->findOrFail($specialistId);
        $bookingDate = date('Y-m-d', strtotime($bookingTime));
        $bookingTimeOnly = date('H:i', strtotime($bookingTime));
        $availableSlots = $specialist->getAvailableSlots($bookingDate);

        if (! in_array($bookingTimeOnly, $availableSlots)) {
            throw BookingNotAvailableException::slotTaken(
                "Slot {$bookingTime} is not available for specialist {$specialistId}.",
                ['specialist_id' => $specialistId, 'booking_time' => $bookingTime]
            );
        }

        $service = $this->beautyServiceRepository->findOrFail($serviceId);
        $prepaymentData = $this->calculatePrepayment((float) $service->price, $discountCode);

        try {
            return $this->createBookingInTransaction(
                $userId, $serviceId, $specialistId, $bookingTime, $discountCode, $prepaymentData, $specialist, $idempotencyKey, $fingerprint
            );
        } catch (UniqueConstraintViolationException $e) {
            // ارسال هم‌زمان همان فرم: درج کلید دوم پشت اولی ماند و بعد از commit آن تکراری شد — نوبت اولی را برگردان
            if ($existing = $this->replayedBooking($userId, $idempotencyKey, $fingerprint)) {
                return $existing;
            }

            throw $e;
        }
    }

    private function replayedBooking(int $userId, ?string $key, string $fingerprint): ?Booking
    {
        $id = Idempotency::existing(Idempotency::BOOKING, $userId, $key, $fingerprint);

        return $id ? $this->bookingRepository->find($id) : null;
    }

    private function createBookingInTransaction(
        int $userId, int $serviceId, int $specialistId, string $bookingTime, ?string $discountCode, array $prepaymentData,
        $specialist, ?string $idempotencyKey, string $fingerprint,
    ): Booking {
        return DB::transaction(function () use (
            $userId, $serviceId, $specialistId, $bookingTime, $discountCode, $prepaymentData, $specialist, $idempotencyKey, $fingerprint
        ) {
            // کلید اول تراکنش: ارسال هم‌زمان دوم روی ایندکس یکتای همین کلید منتظر می‌ماند
            $claim = Idempotency::claim(Idempotency::BOOKING, $userId, $idempotencyKey, $fingerprint);
            $this->assertBookingFits($specialistId, $bookingTime, $serviceId);

            $booking = $this->bookingRepository->create([
                'service_id' => $serviceId,
                'specialist_id' => $specialistId,
                'user_id' => $userId,
                'booking_time' => $bookingTime,
                'status' => $specialist->auto_confirm_bookings ? 'confirmed' : 'pending_payment',
                'prepayment_amount' => $prepaymentData['original_amount'],
                'payment_status' => 'unpaid',
                'discount_code' => $discountCode,
                'discount_amount' => $prepaymentData['discount_amount'],
            ]);
            Idempotency::complete($claim, $booking->id);

            if ($discountCode && $prepaymentData['discount_code']) {
                $lockedDiscountCode = $this->discountCodeRepository->lockByCode($discountCode);

                if ($lockedDiscountCode && $lockedDiscountCode->isValid()) {
                    $lockedDiscountCode->incrementUsage();
                } else {
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

    public function createManualBooking(array $data): Booking
    {
        $specialist = $this->specialistRepository->findOrFail($data['specialist_id']);
        $this->beautyServiceRepository->findOrFail($data['service_id']);

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
            $this->assertBookingFits((int) $data['specialist_id'], $data['booking_time'], (int) $data['service_id']);

            try {
                return $this->bookingRepository->create([
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
     * داخل تراکنش صدا زده شود: کل بازه‌ی خدمت را با ساعت کاری، استراحت و نوبت‌های دیگر می‌سنجد (۲۰۲۶-۱۰-۰۱).
     * قید یکتای active_slot فقط شروع یکسان را می‌گرفت.
     */
    public function assertBookingFits(int $specialistId, string $bookingTime, int $serviceId, ?int $excludeBookingId = null): void
    {
        $specialist = \App\Models\Specialist::withoutGlobalScopes()->find($specialistId);
        $duration = (int) (\Illuminate\Support\Facades\DB::table('beauty_services')->where('id', $serviceId)->value('duration') ?: 30);

        $reason = $specialist
            ? $specialist->conflictFor(Carbon::parse($bookingTime), $duration, $excludeBookingId)
            : 'specialist_not_found';

        if ($reason !== null) {
            throw BookingNotAvailableException::slotTaken(
                "Booking {$bookingTime} ({$duration} min) does not fit for specialist {$specialistId}: {$reason}.",
                ['specialist_id' => $specialistId, 'booking_time' => $bookingTime, 'duration' => $duration, 'reason' => $reason, 'exclude_booking_id' => $excludeBookingId]
            );
        }
    }

    public function isDuplicateActiveSlotError(\Illuminate\Database\QueryException $e): bool
    {
        return (int) $e->getCode() === 23000
            && str_contains($e->getMessage(), 'active_slot');
    }

    public function assertManualRescheduleAvailable(Booking $booking, int $specialistId, string $bookingTime): void
    {
        $specialist = $this->specialistRepository->findOrFail($specialistId);
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

    public function cancelBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $this->bookingRepository->update($booking, [
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
        return $this->bookingRepository->getUpcomingForUser($userId, $limit);
    }

    public function getPastBookings(int $userId, int $limit = 5): Collection
    {
        return $this->bookingRepository->getPastForUser($userId, $limit);
    }

    public function getUserBookings(int $userId): Collection
    {
        return $this->bookingRepository->getAllForUser($userId);
    }
}
