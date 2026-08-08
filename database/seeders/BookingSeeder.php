<?php

namespace Database\Seeders;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Payment;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $discountCodesData = [
            [
                'code' => 'WELCOME',
                'type' => 'percentage',
                'amount' => 20,
                'max_uses' => 50,
                'is_active' => true,
                'expires_at' => now()->addMonths(2),
            ],
            [
                'code' => 'SUMMER',
                'type' => 'fixed',
                'amount' => 50000,
                'max_uses' => 100,
                'is_active' => true,
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'EXPIRED',
                'type' => 'percentage',
                'amount' => 30,
                'max_uses' => 10,
                'used_count' => 10,
                'is_active' => true,
                'expires_at' => now()->subDay(),
            ],
        ];

        foreach ($discountCodesData as $data) {
            DiscountCode::firstOrCreate(['code' => $data['code']], $data);
        }

        DiscountCode::factory(5)->create();

        $users = User::limit(5)->get();
        $services = BeautyService::limit(5)->get();
        $specialists = Specialist::limit(3)->get();
        $discountCode = DiscountCode::where('code', 'WELCOME')->first();

        if ($users->isEmpty() || $services->isEmpty() || $specialists->isEmpty() || ! $discountCode) {
            echo "Skipping BookingSeeder: Not enough Users, Services, Specialists or the WELCOME DiscountCode.\n";

            return;
        }

        $statuses = ['pending', 'confirmed', 'cancelled'];

        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $service = $services->random();
            $specialist = $specialists->random();
            $status = $statuses[array_rand($statuses)];
            $bookingTime = now()->addDays(rand(1, 30))->addHours(rand(9, 17));

            $booking = Booking::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'service_id' => $service->id,
                    'booking_time' => $bookingTime,
                ],
                [
                    'specialist_id' => $specialist->id,
                    'status' => $status,
                    'prepayment_amount' => 50000,
                    'payment_status' => 'unpaid',
                ]
            );

            if ($i % 3 == 0 && $status !== 'cancelled' && $discountCode->used_count < $discountCode->max_uses) {
                $discountAmount = 10000;

                $booking->update([
                    'discount_code' => $discountCode->code,
                    'discount_amount' => $discountAmount,
                    'prepayment_amount' => 50000 - $discountAmount,
                ]);

                $discountCode->increment('used_count');
            }

            if ($status === 'confirmed' && rand(0, 1)) {
                $prepayment = $booking->prepayment_amount;

                $booking->update([
                    'payment_status' => 'paid',
                    'paid_at' => now()->subDays(rand(1, 5)),
                ]);

                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $prepayment,
                    'reference_id' => 'PAY-'.Str::upper(Str::random(8)),
                    'status' => 'completed',
                    'gateway_reference' => 'TRX'.rand(10000000, 99999999),
                    'paid_at' => $booking->paid_at,
                ]);
            }

            if ($status === 'cancelled') {
                $booking->update([
                    'cancelled_by' => fake()->randomElement(['customer', 'specialist']),
                    'cancellation_reason' => fake()->sentence(3),
                    'cancelled_at' => now()->subDays(rand(1, 5)),
                ]);
            }
        }

        Booking::factory(10)->create();
    }
}
