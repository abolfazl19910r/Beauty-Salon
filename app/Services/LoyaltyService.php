<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Notifications\Loyalty\PointsEarned;
use App\Notifications\Loyalty\RewardRedeemed;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Contracts\LoyaltySettingRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyService
{
    public function __construct(
        private readonly LoyaltyPointRepositoryInterface $loyaltyPointRepository,
        private readonly RewardRepositoryInterface $rewardRepository,
        private readonly DiscountCodeRepositoryInterface $discountCodeRepository,
        private readonly LoyaltySettingRepositoryInterface $loyaltySettingRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    private function forgetPointsCache(int $userId): void
    {
        Cache::forget("user:{$userId}:loyalty_points");
    }

    public function getCurrentPoints($userId): int
    {
        return $this->loyaltyPointRepository->sumForUser($userId);
    }

    public function getExpiringPoints($userId, $days = 30): int
    {
        return $this->loyaltyPointRepository->sumExpiringForUser($userId, $days);
    }

    public function getHistory($userId, $perPage = 10): LengthAwarePaginator
    {
        return $this->loyaltyPointRepository->paginateForUserWithBooking($userId, $perPage);
    }

    public function getAvailableRewards($userId): Collection
    {
        return $this->rewardRepository->getActive();
    }

    /**
     * @throws \Exception
     */
    public function redeemReward(int $userId, Reward $reward): DiscountCode
    {
        $user = User::findOrFail($userId);

        if (! $reward->isAvailableForUser($user)) {
            throw new \Exception('امتیاز کافی نیست یا پاداش در دسترس نیست');
        }

        return DB::transaction(function () use ($userId, $reward, $user) {
            $this->loyaltyPointRepository->create([
                'user_id' => $userId,
                'points' => -$reward->required_points,
                'description' => "استفاده از پاداش: {$reward->title}",
                'type' => 'spent',
            ]);

            $discountCode = $this->discountCodeRepository->create([
                'code' => strtoupper(Str::random(8)),
                'type' => $reward->discount_type,
                'amount' => $reward->discount_amount,
                'user_id' => $userId,
                'max_uses' => 1,
                'expires_at' => now()->addDays(30),
                'is_active' => true,
            ]);

            $reward->incrementUsage();

            $user->notify(new RewardRedeemed($reward, $discountCode));

            $this->forgetPointsCache($userId);

            return $discountCode;
        });
    }

    public function earnPointsFromBooking($userId, $bookingId): LoyaltyPoint
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);
        $points = $this->calculatePointsForBooking($booking);

        $expiryMonths = (int) $this->loyaltySettingRepository->getValue('points_expiry_months', 12);
        $expiryMonths = $expiryMonths > 0 ? $expiryMonths : 12;

        $loyaltyPoint = $this->loyaltyPointRepository->create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'points' => $points,
            'type' => 'earned',
            'description' => 'امتیاز کسب شده از رزرو',
            'expires_at' => now()->addMonths($expiryMonths),
        ]);

        $loyaltyPoint->user->notify(new PointsEarned($loyaltyPoint));

        $this->forgetPointsCache($userId);

        return $loyaltyPoint;
    }

    protected function calculatePointsForBooking($booking): int
    {
        $pointsPerAmount = (int) $this->loyaltySettingRepository->getValue('points_per_amount', 10000);
        $pointsPerAmount = $pointsPerAmount > 0 ? $pointsPerAmount : 10000;

        return (int) floor($booking->prepayment_amount / $pointsPerAmount);
    }
}
