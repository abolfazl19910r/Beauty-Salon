<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();

        if ($users->isEmpty()) {
            return;
        }

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
            $notificationCount = rand(3, 8);

            for ($i = 0; $i < $notificationCount; $i++) {
                $notificationType = $notificationTypes[array_rand($notificationTypes)];

                DB::table('user_notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => $notificationType['type'],
                    'user_id' => $user->id,
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'title' => $notificationType['title'],
                        'message' => $notificationType['message'],
                        'icon' => $notificationType['icon'],
                        'action_url' => fake()->optional(0.3)->url()
                    ]),
                    'read_at' => fake()->optional(0.6)->dateTime(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $user = $users->random();
            $notificationType = $notificationTypes[array_rand($notificationTypes)];

            DB::table('user_notifications')->insert([
                'id' => Str::uuid(),
                'type' => $notificationType['type'],
                'user_id' => $user->id,
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $notificationType['title'],
                    'message' => $notificationType['message'],
                    'icon' => $notificationType['icon'],
                    'action_url' => fake()->optional(0.3)->url()
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
