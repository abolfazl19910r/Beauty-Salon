<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\DiscountCode;
use App\Notifications\RewardRedeemed;
use App\Notifications\PointsEarned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyController extends Controller
{
    public function index()
    {
        $userPoints = LoyaltyPoint::where('user_id', auth()->id())->sum('points');
        $history = LoyaltyPoint::where('user_id', auth()->id())
            ->with('booking')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $rewards = Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get();

        $nextReward = $this->getNextReward($userPoints);

        return view('loyalty.index', compact('userPoints', 'history', 'rewards', 'nextReward'));
    }

    public function getPoints()
    {
        $points = LoyaltyPoint::where('user_id', auth()->id())->sum('points');
        return response()->json(['points' => $points]);
    }

    public function getHistory()
    {
        $history = LoyaltyPoint::where('user_id', auth()->id())
            ->with('booking')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'history' => $history
        ]);
    }

    public function getRewards()
    {
        $rewards = Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get();

        $userPoints = LoyaltyPoint::where('user_id', auth()->id())->sum('points');

        return response()->json([
            'rewards' => $rewards,
            'user_points' => $userPoints
        ]);
    }

    public function getProgress()
    {
        $userPoints = LoyaltyPoint::where('user_id', auth()->id())->sum('points');
        $nextReward = $this->getNextReward($userPoints);

        return response()->json([
            'current_points' => $userPoints,
            'next_reward' => $nextReward ? [
                'title' => $nextReward->title,
                'points_needed' => $nextReward->required_points - $userPoints,
                'progress_percentage' => min(($userPoints / $nextReward->required_points) * 100, 100)
            ] : null
        ]);
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        if (!$reward->isAvailableForUser(auth()->user())) {
            return response()->json([
                'message' => 'امتیاز کافی ندارید یا پاداش در دسترس نیست'
            ], 400);
        }

        try {
            DB::beginTransaction();

            LoyaltyPoint::create([
                'user_id' => auth()->id(),
                'points' => -$reward->required_points,
                'description' => "استفاده از پاداش: {$reward->title}"
            ]);

            $discountCode = DiscountCode::create([
                'code' => strtoupper(Str::random(8)),
                'type' => $reward->discount_type,
                'amount' => $reward->discount_amount,
                'user_id' => auth()->id(),
                'max_uses' => 1,
                'expires_at' => now()->addDays(30)
            ]);

            $reward->incrementUsage();

            auth()->user()->notify(new RewardRedeemed($reward, $discountCode));

            DB::commit();

            return response()->json([
                'message' => 'پاداش با موفقیت دریافت شد',
                'discount_code' => $discountCode->code
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'خطا در دریافت پاداش'
            ], 500);
        }
    }

    public function earnPoints(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'points' => 'required|integer|min:1'
        ]);

        $loyaltyPoint = LoyaltyPoint::create([
            'user_id' => auth()->id(),
            'booking_id' => $validated['booking_id'],
            'points' => $validated['points'],
            'type' => 'earned',
            'description' => 'امتیاز کسب شده از رزرو'
        ]);

        auth()->user()->notify(new PointsEarned($loyaltyPoint));

        return response()->json([
            'message' => 'امتیاز با موفقیت افزوده شد',
            'points' => $validated['points']
        ]);
    }

    protected function getNextReward($userPoints)
    {
        return Reward::where('is_active', true)
            ->where('required_points', '>', $userPoints)
            ->orderBy('required_points')
            ->first();
    }

    public function overview()
    {
        $user = auth()->user();
        $userPoints = LoyaltyPoint::where('user_id', $user->id)->sum('points');
        $expiringPoints = $user->getExpiringPoints();
        $nextReward = $this->getNextReward($userPoints);

        return response()->json([
            'summary' => [
                'current_points' => $userPoints,
                'expiring_points' => $expiringPoints,
                'total_earned' => LoyaltyPoint::where('user_id', $user->id)
                    ->where('type', 'earned')
                    ->sum('points'),
                'total_spent' => LoyaltyPoint::where('user_id', $user->id)
                    ->where('type', 'spent')
                    ->sum('points')
            ],
            'next_reward' => $nextReward ? [
                'title' => $nextReward->title,
                'points_needed' => $nextReward->required_points - $userPoints,
                'progress_percentage' => min(($userPoints / $nextReward->required_points) * 100, 100)
            ] : null
        ]);
    }

    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $history = LoyaltyPoint::where('user_id', auth()->id())
            ->with(['booking' => function($query) {
                $query->select('id', 'booking_time', 'service_id', 'specialist_id')
                    ->with(['service:id,name', 'specialist:id,name']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $history->map(function($point) {
                return [
                    'id' => $point->id,
                    'points' => $point->points,
                    'type' => $point->type,
                    'description' => $point->description,
                    'expires_at' => $point->expires_at,
                    'created_at' => $point->created_at,
                    'booking' => $point->booking ? [
                        'id' => $point->booking->id,
                        'date' => $point->booking->booking_time,
                        'service' => $point->booking->service->name,
                        'specialist' => $point->booking->specialist->name
                    ] : null
                ];
            }),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total()
            ]
        ]);
    }

    public function rewards()
    {
        $userPoints = LoyaltyPoint::where('user_id', auth()->id())->sum('points');

        $rewards = Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get()
            ->map(function($reward) use ($userPoints) {
                return [
                    'id' => $reward->id,
                    'title' => $reward->title,
                    'description' => $reward->description,
                    'required_points' => $reward->required_points,
                    'discount_type' => $reward->discount_type,
                    'discount_amount' => $reward->discount_amount,
                    'available' => $reward->isAvailableForUser(auth()->user()),
                    'is_achievable' => $userPoints >= $reward->required_points,
                    'points_needed' => max(0, $reward->required_points - $userPoints),
                    'remaining_uses' => $reward->max_uses ?
                        ($reward->max_uses - $reward->used_count) : null
                ];
            });

        return response()->json([
            'rewards' => $rewards,
            'user_points' => $userPoints
        ]);
    }

    public function discountCodes()
    {
        $codes = DiscountCode::where('user_id', auth()->id())
            ->where(function($query) {
                $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->get()
            ->map(function($code) {
                return [
                    'code' => $code->code,
                    'type' => $code->type,
                    'amount' => $code->amount,
                    'expires_at' => $code->expires_at,
                    'remaining_uses' => $code->max_uses - $code->used_count
                ];
            });

        return response()->json([
            'discount_codes' => $codes
        ]);
    }
}
