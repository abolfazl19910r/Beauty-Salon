<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        $admin = User::firstOrCreate(
            ['phone' => '09399717435'],
            [
                'name' => 'مدیر سیستم',
                'password' => Hash::make('admin'),
                'is_admin' => true,
                'phone_verified_at' => now()
            ]
        );
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        UserNotification::factory()
            ->count(3)
            ->state([
                'user_id' => $admin->id,
                'notifiable_id' => $admin->id,
                'notifiable_type' => User::class
            ])
            ->create();

        $user = User::firstOrCreate(
            ['phone' => '09111111111'],
            [
                'name' => 'کاربر تست عادی',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'phone_verified_at' => now()
            ]
        );
        if ($userRole) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        }

        User::factory(10)->create()->each(function ($user) use ($userRole) {
            if ($userRole) {
                $user->roles()->attach($userRole);
            }
            UserNotification::factory(rand(1, 3))
                ->state([
                    'user_id' => $user->id,
                    'notifiable_id' => $user->id,
                    'notifiable_type' => User::class
                ])
                ->read()
                ->create();
        });
    }
}
