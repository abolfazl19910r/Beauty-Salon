<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoyaltySimulationSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::doesntHave('loyaltyPoints')
            ->where('payment_status', 'paid')
            ->take(50)
            ->get();

        foreach ($bookings as $booking) {
            $points = (int) ($booking->prepayment_amount / 10000);
            if ($points > 0) {
                LoyaltyPoint::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'points' => $points,
                    'type' => 'earned',
                    'description' => 'امتیاز سیستمی از رزرو #'.$booking->id,
                    'expires_at' => now()->addYear(),
                ]);
            }
        }

        User::factory(10)->create()->each(function ($user) {
            LoyaltyPoint::factory(3)->create([
                'user_id' => $user->id,
                'type' => 'earned',
            ]);

            if (rand(0, 1)) {
                LoyaltyPoint::factory()->spent()->create([
                    'user_id' => $user->id,
                ]);
            }
        });
    }
}
