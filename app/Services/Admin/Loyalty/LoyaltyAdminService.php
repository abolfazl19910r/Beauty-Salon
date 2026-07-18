<?php

namespace App\Services\Admin\Loyalty;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for managing the loyalty program from the admin panel.
 *
 * Extracted from AdminLoyaltyController (R-AdminLoyalty phase).
 *
 * ⚠️ Bug fixed (discovered when reporting "adding reward not working"): This class
 * did not have the methods getDashboardStats/createReward/updateReward/deleteReward/
 * getActiveRewards/redeemRewardForUser that AdminLoyaltyRewardController
 * called. Since PHP throws a non-existent method call on the object with
 * \Error and the controller has catch(\Throwable $e) everywhere, this error
 * would be silently swallowed and the user would only see a generic "error creating reward" message
 * (or even no indication, depending on the path) — without leaving anything in the log or
 * database.
 */
class LoyaltyAdminService
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
    ) {
    }

    // ─── Dashboard (admin.loyalty.index) ───────────────────────────────────

    public function getDashboardStats(): array
    {
        $totalActivePoints = (int) LoyaltyPoint::where('type', 'earned')->sum('points');
        $totalPointUsers = LoyaltyPoint::distinct('user_id')->count('user_id');
        $totalRedeemedRewards = (int) Reward::sum('used_count');
        $rewards = Reward::orderBy('required_points')->get();

        return [
            'totalActivePoints'    => $totalActivePoints,
            'totalPointUsers'      => $totalPointUsers,
            'averageUserPoints'    => $totalPointUsers > 0 ? round($totalActivePoints / $totalPointUsers) : 0,
            'totalRedeemedRewards' => $totalRedeemedRewards,
            'rewards'              => $rewards,
        ];
    }

    public function getActiveRewards()
    {
        return Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get();
    }

    // ─── Reward CRUD ────────────────────────────────────────────────────────

    public function createReward(array $data): Reward
    {
        return Reward::create($data);
    }

    public function updateReward(Reward $reward, array $data): Reward
    {
        $reward->update($data);

        return $reward;
    }

    public function deleteReward(Reward $reward): void
    {
        if ($reward->used_count > 0) {
            throw new \Exception('پاداشی که قبلاً استفاده شده قابل حذف نیست.');
        }

        $reward->delete();
    }

    /**
     * Activate/redeem Reward for a specific user from admin side.
     * * Uses exactly the same logic as LoyaltyService::redeemReward() (customer route)
     * * so that the points→discount code conversion formula is not repeated twice.
 */
    public function redeemRewardForUser(int $userId, Reward $reward): DiscountCode
    {
        return $this->loyaltyService->redeemReward($userId, $reward);
    }

    // ─── General statistics (Reports/history — No change) ─────────────────

    public function getStatistics(): array
    {
        $totalPoints   = LoyaltyPoint::where('type', 'earned')->sum('points');
        $usedPoints    = abs(LoyaltyPoint::where('type', 'spent')->sum('points'));
        $activeUsers   = LoyaltyPoint::select('user_id')->distinct()->count('user_id');
        $totalRewards  = Reward::where('is_active', true)->count();
        $redeemedCount = LoyaltyPoint::where('type', 'spent')->count();

        $topUsers = LoyaltyPoint::select('user_id', DB::raw('SUM(points) as total_points'))
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(5)
            ->with('user:id,name,phone')
            ->get();

        $recentRedemptions = LoyaltyPoint::where('type', 'spent')
            ->with(['user:id,name', 'booking'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'total_points_earned'  => $totalPoints,
            'total_points_used'    => $usedPoints,
            'active_points'        => $totalPoints - $usedPoints,
            'active_users'         => $activeUsers,
            'total_active_rewards' => $totalRewards,
            'total_redemptions'    => $redeemedCount,
            'avg_points_per_user'  => $activeUsers > 0
                ? round(($totalPoints - $usedPoints) / $activeUsers)
                : 0,
            'top_users'            => $topUsers,
            'recent_redemptions'   => $recentRedemptions,
        ];
    }

    public function getUserPoints(User $user): array
    {
        $earned  = LoyaltyPoint::where('user_id', $user->id)->where('type', 'earned')->sum('points');
        $spent   = abs(LoyaltyPoint::where('user_id', $user->id)->where('type', 'spent')->sum('points'));
        $balance = $earned - $spent;

        $expiringSoon = LoyaltyPoint::where('user_id', $user->id)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->sum('points');

        return [
            'user'             => $user->only(['id', 'name', 'phone', 'email']),
            'total_earned'     => $earned,
            'total_spent'      => $spent,
            'current_balance'  => $balance,
            'expiring_soon'    => $expiringSoon,
            'history'          => LoyaltyPoint::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(20),
        ];
    }

    public function addPoints(
        User $user,
        int $points,
        string $description,
        ?string $expiresAt = null
    ): LoyaltyPoint {
        return LoyaltyPoint::create([
            'user_id'     => $user->id,
            'points'      => $points,
            'type'        => 'earned',
            'description' => $description,
            'expires_at'  => $expiresAt ? Carbon::parse($expiresAt)->endOfDay() : null,
        ]);
    }

    /**
     * @throws InsufficientLoyaltyPointsException When the balance of user points is insufficient
     */
    public function deductPoints(User $user, int $points, string $description): LoyaltyPoint
    {
        $balance = LoyaltyPoint::where('user_id', $user->id)->sum('points');

        if ($balance < $points) {
            throw new InsufficientLoyaltyPointsException($user->id, (int) $balance, $points);
        }

        return LoyaltyPoint::create([
            'user_id'     => $user->id,
            'points'      => -$points,
            'type'        => 'spent',
            'description' => $description,
        ]);
    }

    public function getExportData(string $type = 'points'): array
    {
        if ($type === 'rewards') {
            return Reward::all()->toArray();
        }

        return User::select('id', 'name', 'phone', 'email')
            ->withSum(['loyaltyPoints as total_points' => fn ($q) =>
            $q->where('type', 'earned')
            ], 'points')
            ->withSum(['loyaltyPoints as used_points' => fn ($q) =>
            $q->where('type', 'spent')
            ], 'points')
            ->having(DB::raw('COALESCE(total_points, 0)'), '>', 0)
            ->orderByDesc('total_points')
            ->get()
            ->map(fn ($u) => [
                'name'            => $u->name,
                'phone'           => $u->phone,
                'total_earned'    => $u->total_points ?? 0,
                'total_spent'     => abs($u->used_points ?? 0),
                'current_balance' => ($u->total_points ?? 0) + ($u->used_points ?? 0),
            ])
            ->toArray();
    }

    public function getHistory(array $filters = [])
    {
        $query = LoyaltyPoint::with('user:id,name,phone')
            ->orderByDesc('created_at');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $query->paginate(20);
    }
}
