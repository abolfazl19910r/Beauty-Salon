<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Payment::factory(5)
            ->completed()
            ->create();

        Payment::factory(5)
            ->failed()
            ->create();

        Payment::factory(5)
            ->pending()
            ->create();

        Payment::factory(2)
            ->pending()
            ->create([
                'expired_at' => now()->subMinutes(10),
            ])->each(function (Payment $payment) {
                $payment->booking->update(['status' => 'cancelled']);
            });

        $unpaidBooking = Booking::where('payment_status', 'unpaid')->inRandomOrder()->first();
        if ($unpaidBooking) {
            Payment::factory()
                ->pending()
                ->create([
                    'booking_id' => $unpaidBooking->id,
                    'amount' => $unpaidBooking->prepayment_amount,
                ]);
        }
    }
}
