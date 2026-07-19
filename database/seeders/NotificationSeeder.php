<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Booking;
use App\Notifications\Booking\AdminNewBookingNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $admins = User::where('is_admin', true)->get();

        if ($users->isEmpty()) {
            return;
        }

        $booking = Booking::first() ?? null;

        DB::table('user_notifications')->truncate();

        $notificationTypes = [
            [
                'type' => 'BookingConfirmed',
                'title' => 'رزرو تایید شد',
                'message' => 'نوبت شما با موفقیت تایید شد.',
                'icon' => 'success'
            ],
            [
                'type' => 'BookingCancelled',
                'title' => 'رزرو لغو شد',
                'message' => 'متأسفانه نوبت شما لغو شده است.',
                'icon' => 'warning'
            ],
            [
                'type' => 'PaymentReceived',
                'title' => 'پرداخت دریافت شد',
                'message' => 'پرداخت شما با موفقیت انجام شد.',
                'icon' => 'success'
            ],
            [
                'type' => 'AppointmentReminder',
                'title' => 'یادآوری نوبت',
                'message' => 'نوبت شما فردا است.',
                'icon' => 'info'
            ],
            [
                'type' => 'LoyaltyPointsEarned',
                'title' => 'امتیاز دریافت کردید',
                'message' => 'شما امتیاز جدید دریافت کردید.',
                'icon' => 'success'
            ]
        ];

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $notificationType = $notificationTypes[array_rand($notificationTypes)];

                DB::table('user_notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => $notificationType['type'],
                    'user_id' => $user->id,
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => $notificationType['message'] . ' (خوانده شده)',
                        'link' => fake()->optional(0.5)->url()
                    ]),
                    'read_at' => Carbon::now()->subDays(fake()->numberBetween(1, 10)),
                    'created_at' => Carbon::now()->subDays(fake()->numberBetween(10, 20)),
                    'updated_at' => Carbon::now()->subDays(fake()->numberBetween(10, 20)),
                ]);
            }

            for ($i = 0; $i < 5; $i++) {
                $notificationType = $notificationTypes[array_rand($notificationTypes)];

                DB::table('user_notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => $notificationType['type'],
                    'user_id' => $user->id,
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'message' => $notificationType['message'] . ' (خوانده نشده)',
                        'link' => fake()->optional(0.5)->url()
                    ]),
                    'read_at' => null,
                    'created_at' => Carbon::now()->subMinutes(fake()->numberBetween(1, 60)),
                    'updated_at' => Carbon::now()->subMinutes(fake()->numberBetween(1, 60)),
                ]);
            }
        }

        if ($booking && $admins->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new AdminNewBookingNotification($booking));
            }
            $this->command->info('Admin-specific notifications sent to ' . $admins->count() . ' admin(s).');
        }

        $this->command->info('*** Notifications seeded successfully for all users (read and unread). ***');
    }
}
