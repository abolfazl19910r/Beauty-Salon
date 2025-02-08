<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyPointResource;
use App\Http\Resources\RewardResource;
use App\Http\Resources\DiscountCodeResource;
use App\Models\DiscountCode;
use App\Models\Reward;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function overview()
    {
        $userId = auth()->id();
        $currentPoints = $this->loyaltyService->getCurrentPoints($userId);
        $expiringPoints = $this->loyaltyService->getExpiringPoints($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $currentPoints,
                'expiring_points' => $expiringPoints,
                'rewards_available' => Reward::where('required_points', '<=', $currentPoints)
                    ->where('is_active', true)
                    ->count()
            ]
        ]);
    }

    public function history(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $history = $this->loyaltyService->getHistory(auth()->id(), $request->per_page);
        return LoyaltyPointResource::collection($history);
    }

    public function rewards(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $rewards = $this->loyaltyService->getAvailableRewards(auth()->id());
        return RewardResource::collection($rewards);
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        try {
            $discountCode = $this->loyaltyService->redeemReward(auth()->id(), $reward);
            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت دریافت شد',
                'data' => new DiscountCodeResource($discountCode)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function discountCodes(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $codes = DiscountCode::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        return DiscountCodeResource::collection($codes);
    }
}
