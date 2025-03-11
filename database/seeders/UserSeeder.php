<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'label' => 'مدیر سیستم'
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'label' => 'کاربر عادی'
        ]);

        $admin = User::create([
            'name' => 'مدیر',
            'phone' => '09399717435',
            'password' => Hash::make('admin'),
            'is_admin' => true,
            'phone_verified_at' => now()
        ]);

        $admin->roles()->attach($adminRole);

        $user = User::create([
            'name' => 'کاربر تست',
            'phone' => '09111111111',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'phone_verified_at' => now()
        ]);

        $user->roles()->attach($userRole);

        User::factory(10)->create()->each(function ($user) use ($userRole) {
            $user->roles()->attach($userRole);
        });
    }
}
