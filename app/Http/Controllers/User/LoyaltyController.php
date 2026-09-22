<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    public function __construct(
        protected readonly LoyaltyService $loyaltyService,
        protected readonly DiscountCodeRepositoryInterface $discountCodeRepository,
        protected readonly RewardRepositoryInterface $rewardRepository,
        protected readonly LoyaltyPointRepositoryInterface $loyaltyPointRepository,
    ) {}

    public function index(): View|RedirectResponse
    {
        try {
            $userId = auth()->id();
            $userPoints = $this->loyaltyService->getCurrentPoints($userId);
            $expiringPoints = $this->loyaltyService->getExpiringPoints($userId, 30);
            $history = $this->loyaltyService->getHistory($userId, 10);
            $rewards = $this->loyaltyService->getAvailableRewards($userId);
            $nextReward = $this->getNextReward($userPoints);
            $activeCodes = $this->discountCodeRepository->getActiveForUser($userId);

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
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در بارگذاری اطلاعات امتیازات. لطفاً دوباره تلاش کنید.');
        }
    }

    public function redeemReward(Request $request, Reward $reward): RedirectResponse
    {
        try {
            $userId = auth()->id();

            if (! $reward->isAvailableForUser(auth()->user())) {
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
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در دریافت پاداش: '.$e->getMessage());
        }
    }

    public function getPoints(): JsonResponse
    {
        $points = $this->loyaltyService->getCurrentPoints(auth()->id());

        return response()->json(['points' => $points]);
    }

    public function getHistory(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $history = $this->loyaltyService->getHistory(auth()->id(), $perPage);

        return response()->json($history);
    }

    public function getRewards(): JsonResponse
    {
        $rewards = $this->loyaltyService->getAvailableRewards(auth()->id());
        $userPoints = $this->loyaltyService->getCurrentPoints(auth()->id());

        return response()->json([
            'rewards' => $rewards,
            'user_points' => $userPoints,
        ]);
    }

    public function getProgress(): JsonResponse
    {
        $userPoints = $this->loyaltyService->getCurrentPoints(auth()->id());
        $nextReward = $this->getNextReward($userPoints);

        return response()->json([
            'current_points' => $userPoints,
            'next_reward' => $nextReward ? [
                'title' => $nextReward->title,
                'points_needed' => $nextReward->required_points - $userPoints,
                'progress_percentage' => min(($userPoints / $nextReward->required_points) * 100, 100),
            ] : null,
        ]);
    }

    public function discountCodes(): JsonResponse
    {
        $codes = $this->discountCodeRepository->getActiveForUser(auth()->id())
            ->map(function ($code) {
                return [
                    'code' => $code->code,
                    'type' => $code->type,
                    'amount' => $code->amount,
                    'expires_at' => $code->expires_at,
                    'remaining_uses' => $code->max_uses - $code->used_count,
                    'max_uses' => $code->max_uses,
                ];
            });

        return response()->json([
            'discount_codes' => $codes,
        ]);
    }

    protected function getNextReward($userPoints)
    {
        return $this->rewardRepository->getNextForPoints($userPoints);
    }

    public function overview(): JsonResponse
    {
        $user = auth()->user();
        $userPoints = $this->loyaltyService->getCurrentPoints($user->id);
        $expiringPoints = $this->loyaltyService->getExpiringPoints($user->id);
        $nextReward = $this->getNextReward($userPoints);

        return response()->json([
            'summary' => [
                'current_points' => $userPoints,
                'expiring_points' => $expiringPoints,
                'total_earned' => $this->loyaltyPointRepository->sumForUserByType($user->id, 'earned'),
                'total_spent' => abs($this->loyaltyPointRepository->sumForUserByType($user->id, 'spent')),
            ],
            'next_reward' => $nextReward ? [
                'title' => $nextReward->title,
                'points_needed' => $nextReward->required_points - $userPoints,
                'progress_percentage' => min(($userPoints / $nextReward->required_points) * 100, 100),
            ] : null,
        ]);
    }

    public function myCodes(): View
    {
        $activeCodes = $this->discountCodeRepository->getActiveForUser(auth()->id());
        $expiredCodes = $this->discountCodeRepository->getExpiredForUser(auth()->id());
        $totalDiscount = $activeCodes->sum(fn ($c) => $c->type === 'fixed' ? $c->amount : 0);

        return view('loyalty.my-codes', compact('activeCodes', 'expiredCodes', 'totalDiscount'));
    }
}
