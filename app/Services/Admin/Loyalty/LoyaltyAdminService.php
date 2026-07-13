<?php

namespace App\Services\Admin\Loyalty;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * منطق تجاری مدیریت برنامه‌ی وفاداری از پنل ادمین.
 * از AdminLoyaltyController استخراج شد (فاز R-AdminLoyalty).
 */
class LoyaltyAdminService
{
    // ─── آمار کلی ────────────────────────────────────────────────

    public function getStatistics(): array
    {
        $totalPoints   = LoyaltyPoint::where('type', 'earned')->sum('points');
        $usedPoints    = abs(LoyaltyPoint::where('type', 'spent')->sum('points'));
        $activeUsers   = LoyaltyPoint::select('user_id')->distinct()->count('user_id');
        $totalRewards  = LoyaltyReward::where('is_active', true)->count();
        $redeemedCount = LoyaltyPoint::where('type', 'spent')->count();

        return [
            'total_points_earned'   => $totalPoints,
            'total_points_used'     => $usedPoints,
            'active_points'         => $totalPoints - $usedPoints,
            'active_users'          => $activeUsers,
            'total_active_rewards'  => $totalRewards,
            'total_redemptions'     => $redeemedCount,
            'avg_points_per_user'   => $activeUsers > 0
                ? round(($totalPoints - $usedPoints) / $activeUsers)
                : 0,
        ];
    }

    // ─── امتیازات کاربر ──────────────────────────────────────────

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
            'user'          => $user->only(['id', 'name', 'phone', 'email']),
            'total_earned'  => $earned,
            'total_spent'   => $spent,
            'current_balance' => $balance,
            'expiring_soon' => $expiringSoon,
            'history'       => LoyaltyPoint::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(20),
        ];
    }

    // ─── افزودن / کسر امتیاز ─────────────────────────────────────

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

    public function deductPoints(User $user, int $points, string $description): LoyaltyPoint
    {
        $balance = LoyaltyPoint::where('user_id', $user->id)->sum('points');

        if ($balance < $points) {
            throw new \App\Exceptions\InsufficientWalletBalanceException(
                "User {$user->id} has insufficient loyalty points: balance={$balance}, required={$points}"
            );
        }

        return LoyaltyPoint::create([
            'user_id'     => $user->id,
            'points'      => -$points,
            'type'        => 'spent',
            'description' => $description,
        ]);
    }

    // ─── خروجی ───────────────────────────────────────────────────

    public function getExportData(string $type = 'points'): array
    {
        if ($type === 'rewards') {
            return LoyaltyReward::all()->toArray();
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
                'name'           => $u->name,
                'phone'          => $u->phone,
                'total_earned'   => $u->total_points ?? 0,
                'total_spent'    => abs($u->used_points ?? 0),
                'current_balance'=> ($u->total_points ?? 0) + ($u->used_points ?? 0),
            ])
            ->toArray();
    }

    // ─── تاریخچه ─────────────────────────────────────────────────

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
