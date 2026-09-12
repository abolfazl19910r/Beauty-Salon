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

        // ⭐ fix/admin-booking-slot-conflict added a DB-level unique index (active_slot_key) so
        // no two non-cancelled bookings can share the same (specialist_id, booking_time). This
        // seeder was never updated for that: with only 3 specialists and 20 random draws across
        // just 30 days × 9 hours, a collision is common enough that `php artisan db:seed` was
        // confirmed (by actually running migrate:fresh --seed 5 times in a row) to hard-crash
        // with a real UniqueConstraintViolationException about 40% of the time. Tracking taken
        // slots here and redrawing on a collision (bounded, so it can't loop forever) fixes that
        // without touching the constraint itself or BookingFactory (used by ~900 other tests).
        $activeSlotKeys = Booking::whereIn('specialist_id', $specialists->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->get(['specialist_id', 'booking_time'])
            ->mapWithKeys(fn ($b) => [$b->specialist_id.'|'.$b->booking_time->format('Y-m-d H:i:s') => true])
            ->all();

        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $service = $services->random();
            $specialist = $specialists->random();
            $status = $statuses[array_rand($statuses)];

            $attempts = 0;
            do {
                $bookingTime = now()->addDays(rand(1, 30))->addHours(rand(9, 17));
                $slotKey = $specialist->id.'|'.$bookingTime->format('Y-m-d H:i:s');
                $attempts++;
            } while ($status !== 'cancelled' && isset($activeSlotKeys[$slotKey]) && $attempts < 30);

            if ($status !== 'cancelled') {
                $activeSlotKeys[$slotKey] = true;
            }

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

        $this->createRandomFactoryBookingsWithoutSlotCollisions(10);
    }

    /**
     * @see run() docblock above the $activeSlotKeys tracking — same active_slot_key collision
     * risk applies here too, since BookingFactory also picks a random specialist + hour-rounded
     * time with no awareness of what's already taken. BookingFactory itself is deliberately left
     * untouched (it's used directly by ~900 other tests); each attempt is simply retried with a
     * freshly randomized set of factory attributes on a collision.
     */
    private function createRandomFactoryBookingsWithoutSlotCollisions(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            for ($attempt = 0; $attempt < 10; $attempt++) {
                try {
                    Booking::factory()->create();
                    break;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    if ($attempt === 9) {
                        throw $e;
                    }
                }
            }
        }
    }
}
