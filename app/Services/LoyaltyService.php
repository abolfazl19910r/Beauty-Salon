<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\DiscountCode;
use App\Notifications\RewardRedeemed;
use App\Notifications\PointsEarned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyService
{
    public function getCurrentPoints($userId): int
    {
        return LoyaltyPoint::where('user_id', $userId)->sum('points');
    }

    public function getExpiringPoints($userId, $days = 30): int
    {
        return LoyaltyPoint::where('user_id', $userId)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->sum('points');
    }

    public function getHistory($userId, $perPage = 10)
    {
        return LoyaltyPoint::where('user_id', $userId)
            ->with(['booking' => function($query) {
                $query->select('id', 'booking_time', 'service_id', 'specialist_id')
                    ->with(['service:id,name', 'specialist:id,name']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAvailableRewards($userId)
    {
        $userPoints = $this->getCurrentPoints($userId);

        return Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get();
    }

    /**
     * @throws \Exception
     */
    public function redeemReward($userId, Reward $reward)
    {
        if (!$reward->isAvailableForUser(auth()->user())) {
            throw new \Exception('امتیاز کافی نیست یا پاداش در دسترس نیست');
        }

        try {
            DB::beginTransaction();

            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $userId,
                'points' => -$reward->required_points,
                'description' => "استفاده از پاداش: {$reward->title}",
                'type' => 'spent'
            ]);

            $discountCode = DiscountCode::create([
                'code' => strtoupper(Str::random(8)),
                'type' => $reward->discount_type,
                'amount' => $reward->discount_amount,
                'user_id' => $userId,
                'max_uses' => 1,
                'expires_at' => now()->addDays(30),
                'is_active' => true
            ]);

            $reward->incrementUsage();

            DB::commit();

            auth()->user()->notify(new RewardRedeemed($reward, $discountCode));

            return $discountCode;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function earnPointsFromBooking($userId, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $points = $this->calculatePointsForBooking($booking);

        $loyaltyPoint = LoyaltyPoint::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'points' => $points,
            'type' => 'earned',
            'description' => 'امتیاز کسب شده از رزرو',
            'expires_at' => now()->addYear()
        ]);

        auth()->user()->notify(new PointsEarned($loyaltyPoint));

        return $loyaltyPoint;
    }

    protected function calculatePointsForBooking($booking): int
    {
        return (int) floor($booking->prepayment_amount / 10000);
    }
}
