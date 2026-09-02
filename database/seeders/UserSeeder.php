<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();
        $superAdminRole = Role::where('name', 'super-admin')->first();

        // ⭐ SaaS multi-tenant (2026-08-30): temporary seeded super-admin so /superadmin can be
        // tested end-to-end before `php artisan superadmin:create` (commit 7) exists. Not
        // linked to salon_admins at all — a super admin isn't the owner of any one salon (see
        // EnsureSuperAdmin/EnsureAdminSalonActive: both bypass entirely on hasRole('super-admin')
        // rather than resolving a salon for them).
        $superAdmin = User::firstOrCreate(
            ['phone' => '09399999999'],
            [
                'name' => 'سوپر ادمین (تست)',
                'password' => Hash::make('superadmin'),
                'is_admin' => true,
                'phone_verified_at' => now(),
                'user_type' => 'staff',
            ]
        );
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        $admin = User::firstOrCreate(
            ['phone' => '09399717435'],
            [
                'name' => 'مدیر سیستم',
                'password' => Hash::make('admin'),
                'is_admin' => true,
                'phone_verified_at' => now(),
                // ⭐ Customer identity redesign (2026-08-30): without this, the seeded admin's
                // user_type defaults to 'customer' (the users table's column default) and
                // AuthenticatedSessionController's login lookup — now filtered to
                // user_type='staff' precisely because customer phones aren't globally unique
                // anymore — would never find them. Discovered while wiring the seeders to work
                // under the new salon_id NOT NULL constraint.
                'user_type' => 'staff',
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
                'notifiable_type' => User::class,
            ])
            ->create();

        $user = User::firstOrCreate(
            ['phone' => '09111111111'],
            [
                'name' => 'کاربر تست عادی',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'phone_verified_at' => now(),
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
                    'notifiable_type' => User::class,
                ])
                ->read()
                ->create();
        });
    }
}
