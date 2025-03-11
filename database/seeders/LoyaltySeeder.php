<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'title' => 'تخفیف ۱۰ درصدی',
                'description' => 'تخفیف ۱۰ درصدی روی همه خدمات',
                'required_points' => 100,
                'discount_type' => 'percentage',
                'discount_amount' => 10,
            ],
            [
                'title' => 'تخفیف ۲۰ درصدی',
                'description' => 'تخفیف ۲۰ درصدی روی همه خدمات',
                'required_points' => 200,
                'discount_type' => 'percentage',
                'discount_amount' => 20,
            ],
            [
                'title' => 'تخفیف ۵۰ هزار تومانی',
                'description' => 'تخفیف ثابت ۵۰ هزار تومانی روی همه خدمات',
                'required_points' => 300,
                'discount_type' => 'fixed',
                'discount_amount' => 50000,
            ],
            [
                'title' => 'تخفیف ۱۰۰ هزار تومانی',
                'description' => 'تخفیف ثابت ۱۰۰ هزار تومانی روی همه خدمات',
                'required_points' => 500,
                'discount_type' => 'fixed',
                'discount_amount' => 100000,
            ],
            [
                'title' => 'تخفیف ۳۰ درصدی ویژه',
                'description' => 'تخفیف ۳۰ درصدی روی همه خدمات',
                'required_points' => 800,
                'discount_type' => 'percentage',
                'discount_amount' => 30,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::create($reward);
        }

        $bookings = Booking::where('payment_status', 'paid')->get();

        foreach ($bookings as $booking) {
            $points = intval($booking->prepayment_amount / 10000);

            if ($points > 0) {
                LoyaltyPoint::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'points' => $points,
                    'type' => 'earned',
                    'description' => 'امتیاز از رزرو خدمت',
                    'expires_at' => now()->addYear(),
                ]);
            }
        }

        $users = User::whereHas('loyaltyPoints', function ($query) {
            $query->where('type', 'earned');
        })->get();

        foreach ($users as $user) {
            if (rand(0, 1)) {
                $earnedPoints = LoyaltyPoint::where('user_id', $user->id)
                    ->where('type', 'earned')
                    ->sum('points');

                if ($earnedPoints >= 100) {
                    $reward = Reward::where('required_points', '<=', $earnedPoints)
                        ->inRandomOrder()
                        ->first();

                    if ($reward) {
                        LoyaltyPoint::create([
                            'user_id' => $user->id,
                            'points' => -$reward->required_points,
                            'type' => 'spent',
                            'description' => 'استفاده از پاداش: ' . $reward->title,
                            'expires_at' => null,
                        ]);

                        $reward->increment('used_count');
                    }
                }
            }
        }
    }
}
