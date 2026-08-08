<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class UserNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found to attach notifications via UserNotificationSeeder.');

            return;
        }

        foreach ($users as $user) {
            UserNotification::factory()
                ->count(rand(0, 5))
                ->state([
                    'user_id' => $user->id,
                    'notifiable_id' => $user->id,
                    'notifiable_type' => User::class,
                ])
                ->create();
        }
    }
}
