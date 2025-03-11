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
        DiscountCode::create([
            'code' => 'WELCOME',
            'type' => 'percentage',
            'amount' => 20,
            'max_uses' => 50,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addMonths(2),
        ]);

        DiscountCode::create([
            'code' => 'SUMMER',
            'type' => 'fixed',
            'amount' => 50000,
            'max_uses' => 100,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addMonths(3),
        ]);

        DiscountCode::create([
            'code' => 'EXPIRED',
            'type' => 'percentage',
            'amount' => 30,
            'max_uses' => 10,
            'used_count' => 10,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        DiscountCode::factory(5)->create();

        $users = User::where('is_admin', false)->get();

        $specialists = Specialist::all();
        $services = BeautyService::all();

        if ($specialists->count() == 0 || $services->count() == 0 || $users->count() == 0) {
            return;
        }

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $service = $services->random();
                $specialist = $specialists->random();

                $booking = Booking::create([
                    'service_id' => $service->id,
                    'specialist_id' => $specialist->id,
                    'user_id' => $user->id,
                    'booking_time' => now()->subDays(rand(10, 60)),
                    'status' => 'confirmed',
                    'prepayment_amount' => $service->price,
                    'payment_status' => 'paid',
                    'paid_at' => now()->subDays(rand(10, 60)),
                    'rating' => rand(3, 5),
                    'review' => fake()->paragraph(),
                ]);

                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->prepayment_amount,
                    'reference_id' => 'PAY-' . Str::upper(Str::random(8)),
                    'status' => 'completed',
                    'gateway_reference' => 'TRX' . rand(10000000, 99999999),
                    'paid_at' => $booking->paid_at,
                ]);
            }

            if (rand(0, 1)) {
                $service = $services->random();
                $specialist = $specialists->random();

                $booking = Booking::create([
                    'service_id' => $service->id,
                    'specialist_id' => $specialist->id,
                    'user_id' => $user->id,
                    'booking_time' => now()->subDays(rand(5, 30)),
                    'status' => 'cancelled',
                    'prepayment_amount' => $service->price,
                    'payment_status' => 'paid',
                    'paid_at' => now()->subDays(rand(31, 60)),
                ]);

                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->prepayment_amount,
                    'reference_id' => 'PAY-' . Str::upper(Str::random(8)),
                    'status' => 'completed',
                    'gateway_reference' => 'TRX' . rand(10000000, 99999999),
                    'paid_at' => $booking->paid_at,
                ]);

                if (rand(0, 1)) {
                    $booking->update([
                        'refund_status' => 'refunded',
                        'refunded_at' => now()->subDays(rand(1, 10)),
                        'refunded_amount' => $booking->prepayment_amount,
                        'refund_reference' => 'REFUND-' . Str::upper(Str::random(8)),
                    ]);
                }
            }

            for ($i = 0; $i < rand(1, 2); $i++) {
                $service = $services->random();
                $specialist = $specialists->random();
                $status = rand(0, 1) ? 'pending' : 'confirmed';

                $booking = Booking::create([
                    'service_id' => $service->id,
                    'specialist_id' => $specialist->id,
                    'user_id' => $user->id,
                    'booking_time' => now()->addDays(rand(3, 30))->hours(rand(9, 16))->minutes(rand(0, 1) ? 0 : 30),
                    'status' => $status,
                    'prepayment_amount' => $service->price,
                    'payment_status' => 'unpaid',
                ]);

                if (rand(0, 1)) {
                    $discountCode = DiscountCode::where('is_active', true)
                        ->where('used_count', '<', 'max_uses')
                        ->whereDate('expires_at', '>', now())
                        ->inRandomOrder()
                        ->first();

                    if ($discountCode) {
                        $discountAmount = $discountCode->type === 'percentage'
                            ? ($booking->prepayment_amount * $discountCode->amount / 100)
                            : $discountCode->amount;

                        $booking->update([
                            'discount_code' => $discountCode->code,
                            'discount_amount' => $discountAmount,
                            'prepayment_amount' => $booking->prepayment_amount - $discountAmount,
                        ]);

                        $discountCode->increment('used_count');
                    }
                }

                if ($status === 'confirmed' && rand(0, 1)) {
                    $booking->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $booking->prepayment_amount,
                        'reference_id' => 'PAY-' . Str::upper(Str::random(8)),
                        'status' => 'completed',
                        'gateway_reference' => 'TRX' . rand(10000000, 99999999),
                        'paid_at' => now(),
                    ]);
                }
            }
        }

        Booking::factory(10)
            ->create()
            ->each(function ($booking) {
                if (rand(0, 1)) {
                    $booking->update([
                        'payment_status' => 'paid',
                        'paid_at' => now()->subDays(rand(1, 5)),
                    ]);

                    Payment::factory()->create([
                        'booking_id' => $booking->id,
                        'amount' => $booking->prepayment_amount,
                        'status' => 'completed',
                        'paid_at' => $booking->paid_at,
                    ]);
                }
            });
    }
}
