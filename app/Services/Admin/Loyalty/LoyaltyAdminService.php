<?php

namespace App\Services\Admin\Loyalty;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Services\LoyaltyService;
use App\Support\CurrentSalon;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LoyaltyAdminService
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
        private readonly LoyaltyPointRepositoryInterface $loyaltyPointRepository,
        private readonly RewardRepositoryInterface $rewardRepository,
    ) {}

    public function getDashboardStats(): array
    {
        $salonId = app(CurrentSalon::class)->id();
        $totalActivePoints = $this->loyaltyPointRepository->sumByType('earned', $salonId);
        $totalPointUsers = $this->loyaltyPointRepository->countDistinctUsers($salonId);
        $totalRedeemedRewards = $this->rewardRepository->sumUsedCount();
        $rewards = $this->rewardRepository->allOrderedByRequiredPoints();

        return [
            'totalActivePoints' => $totalActivePoints,
            'totalPointUsers' => $totalPointUsers,
            'averageUserPoints' => $totalPointUsers > 0 ? round($totalActivePoints / $totalPointUsers) : 0,
            'totalRedeemedRewards' => $totalRedeemedRewards,
            'rewards' => $rewards,
        ];
    }

    public function getActiveRewards(): Collection
    {
        return $this->rewardRepository->getActive();
    }

    public function createReward(array $data): Reward
    {
        return $this->rewardRepository->create($data);
    }

    public function updateReward(Reward $reward, array $data): Reward
    {
        return $this->rewardRepository->update($reward, $data);
    }

    public function deleteReward(Reward $reward): void
    {
        if ($reward->used_count > 0) {
            throw new \Exception('پاداشی که قبلاً استفاده شده قابل حذف نیست.');
        }

        $this->rewardRepository->delete($reward);
    }

    public function redeemRewardForUser(int $userId, Reward $reward): DiscountCode
    {
        return $this->loyaltyService->redeemReward($userId, $reward);
    }

    public function getUserPoints(User $user): array
    {
        $earned = $this->loyaltyPointRepository->sumForUserByType($user->id, 'earned');
        $spent = abs($this->loyaltyPointRepository->sumForUserByType($user->id, 'spent'));
        $balance = $earned - $spent;

        $expiringSoon = $this->loyaltyPointRepository->sumExpiringSoonForUser($user->id, 30);

        return [
            'user' => $user->only(['id', 'name', 'phone', 'email']),
            'total_earned' => $earned,
            'total_spent' => $spent,
            'current_balance' => $balance,
            'expiring_soon' => $expiringSoon,
            'history' => $this->loyaltyPointRepository->paginateForUser($user->id, 20),
        ];
    }

    public function addPoints(
        User $user,
        int $points,
        string $description,
        ?string $expiresAt = null
    ): LoyaltyPoint {
        $loyaltyPoint = $this->loyaltyPointRepository->create([
            'user_id' => $user->id,
            'points' => $points,
            'type' => 'earned',
            'description' => $description,
            'expires_at' => $expiresAt ? Carbon::parse($expiresAt)->endOfDay() : null,
        ]);

        Cache::forget("user:{$user->id}:loyalty_points");

        return $loyaltyPoint;
    }

    /**
     * @throws InsufficientLoyaltyPointsException When the balance of user points is insufficient
     */
    public function deductPoints(User $user, int $points, string $description): LoyaltyPoint
    {
        $balance = $this->loyaltyPointRepository->sumForUser($user->id);

        if ($balance < $points) {
            throw new InsufficientLoyaltyPointsException($user->id, (int) $balance, $points);
        }

        $loyaltyPoint = $this->loyaltyPointRepository->create([
            'user_id' => $user->id,
            'points' => -$points,
            'type' => 'spent',
            'description' => $description,
        ]);

        Cache::forget("user:{$user->id}:loyalty_points");

        return $loyaltyPoint;
    }
}
