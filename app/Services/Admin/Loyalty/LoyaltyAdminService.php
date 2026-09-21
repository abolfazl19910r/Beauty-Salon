<?php

namespace App\Services\Admin\Loyalty;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoyaltyAdminService
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
        private readonly LoyaltyPointRepositoryInterface $loyaltyPointRepository,
        private readonly RewardRepositoryInterface $rewardRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getDashboardStats(): array
    {
        $totalActivePoints = $this->loyaltyPointRepository->sumByType('earned');
        $totalPointUsers = $this->loyaltyPointRepository->countDistinctUsers();
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

    public function getStatistics(): array
    {
        $totalPoints = $this->loyaltyPointRepository->sumByType('earned');
        $usedPoints = abs($this->loyaltyPointRepository->sumByType('spent'));
        $activeUsers = $this->loyaltyPointRepository->countDistinctUsers();
        $totalRewards = $this->rewardRepository->countActive();
        $redeemedCount = $this->loyaltyPointRepository->countByType('spent');

        $topUsers = $this->loyaltyPointRepository->topUsersByPoints(5);

        $recentRedemptions = $this->loyaltyPointRepository->recentByType('spent', 10);

        return [
            'total_points_earned' => $totalPoints,
            'total_points_used' => $usedPoints,
            'active_points' => $totalPoints - $usedPoints,
            'active_users' => $activeUsers,
            'total_active_rewards' => $totalRewards,
            'total_redemptions' => $redeemedCount,
            'avg_points_per_user' => $activeUsers > 0
                ? round(($totalPoints - $usedPoints) / $activeUsers)
                : 0,
            'top_users' => $topUsers,
            'recent_redemptions' => $recentRedemptions,
        ];
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

    public function getExportData(string $type = 'points'): array
    {
        if ($type === 'rewards') {
            return $this->rewardRepository->all()->toArray();
        }

        return $this->userRepository->query()->select('id', 'name', 'phone', 'email')
            ->withSum(['loyaltyPoints as total_points' => fn ($q) => $q->where('type', 'earned'),
            ], 'points')
            ->withSum(['loyaltyPoints as used_points' => fn ($q) => $q->where('type', 'spent'),
            ], 'points')
            ->having(DB::raw('COALESCE(total_points, 0)'), '>', 0)
            ->orderByDesc('total_points')
            ->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'phone' => $u->phone,
                'total_earned' => $u->total_points ?? 0,
                'total_spent' => abs($u->used_points ?? 0),
                'current_balance' => ($u->total_points ?? 0) + ($u->used_points ?? 0),
            ])
            ->toArray();
    }

    public function getHistory(array $filters = []): LengthAwarePaginator
    {
        return $this->loyaltyPointRepository->paginateWithFilters($filters, 20);
    }
}
