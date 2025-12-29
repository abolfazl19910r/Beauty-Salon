<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\DiscountCode;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function index()
    {
        try {
            $userId = auth()->id();
            $userPoints = $this->loyaltyService->getCurrentPoints($userId);
            $expiringPoints = $this->loyaltyService->getExpiringPoints($userId, 30);
            $history = $this->loyaltyService->getHistory($userId, 10);
            $rewards = $this->loyaltyService->getAvailableRewards($userId);
            $nextReward = $this->getNextReward($userPoints);
            $activeCodes = DiscountCode::where('user_id', $userId)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->where('used_count', '<', DB::raw('max_uses'))
                ->latest()
                ->get();

            return view('loyalty.index', compact(
                'userPoints',
                'expiringPoints',
                'history',
                'rewards',
                'nextReward',
                'activeCodes'
            ));

        } catch (\Exception $e) {
            Log::error('خطا در بارگذاری پنل امتیازات', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در بارگذاری اطلاعات امتیازات. لطفاً دوباره تلاش کنید.');
        }
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        try {
            $userId = auth()->id();

            if (!$reward->isAvailableForUser(auth()->user())) {
                return back()->with('error', 'امتیاز کافی ندارید یا این پاداش در دسترس نیست.');
            }

            DB::beginTransaction();
            $discountCode = $this->loyaltyService->redeemReward($userId, $reward);

            DB::commit();

            return redirect()
                ->route('loyalty.index')
                ->with('success', "🎉 تبریک! پاداش با موفقیت دریافت شد. کد تخفیف شما: {$discountCode->code}");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('خطا در تبدیل امتیاز به پاداش', [
                'user_id' => auth()->id(),
                'reward_id' => $reward->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در دریافت پاداش: ' . $e->getMessage());
        }
    }

    public function getPoints()
    {
        $points = $this->loyaltyService->getCurrentPoints(auth()->id());
        return response()->json(['points' => $points]);
    }

    public function getHistory(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $history = $this->loyaltyService->getHistory(auth()->id(), $perPage);

        return response()->json($history);
    }

    public function getRewards()
    {
        $rewards = $this->loyaltyService->getAvailableRewards(auth()->id());
        $userPoints = $this->loyaltyService->getCurrentPoints(auth()->id());

        return response()->json([
            'rewards' => $rewards,
            'user_points' => $userPoints
        ]);
    }

    public function getProgress()
    {
        $userPoints = $this->loyaltyService->getCurrentPoints(auth()->id());
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

    public function discountCodes()
    {
        $codes = DiscountCode::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('used_count', '<', DB::raw('max_uses'))
            ->latest()
            ->get()
            ->map(function($code) {
                return [
                    'code' => $code->code,
                    'type' => $code->type,
                    'amount' => $code->amount,
                    'expires_at' => $code->expires_at,
                    'remaining_uses' => $code->max_uses - $code->used_count,
                    'max_uses' => $code->max_uses
                ];
            });

        return response()->json([
            'discount_codes' => $codes
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
        $userPoints = $this->loyaltyService->getCurrentPoints($user->id);
        $expiringPoints = $this->loyaltyService->getExpiringPoints($user->id);
        $nextReward = $this->getNextReward($userPoints);

        return response()->json([
            'summary' => [
                'current_points' => $userPoints,
                'expiring_points' => $expiringPoints,
                'total_earned' => LoyaltyPoint::where('user_id', $user->id)
                    ->where('type', 'earned')
                    ->sum('points'),
                'total_spent' => abs(LoyaltyPoint::where('user_id', $user->id)
                    ->where('type', 'spent')
                    ->sum('points'))
            ],
            'next_reward' => $nextReward ? [
                'title' => $nextReward->title,
                'points_needed' => $nextReward->required_points - $userPoints,
                'progress_percentage' => min(($userPoints / $nextReward->required_points) * 100, 100)
            ] : null
        ]);
    }

    public function myCodes()
    {
        return view('loyalty.my-codes');
    }
}
