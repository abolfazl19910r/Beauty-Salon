<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin'], ['label' => 'مدیر سیستم']);
        Role::firstOrCreate(['name' => 'user'], ['label' => 'کاربر عادی']);
        Role::firstOrCreate(['name' => 'specialist'], ['label' => 'متخصص/پشتیبان']);
        // ⭐ SaaS multi-tenant (2026-08-30): EnsureSuperAdmin/EnsureAdminSalonActive check
        // hasRole('super-admin') specifically — see those middlewares' docblocks for why
        // hasPermission('super_admin') was rejected (User::hasPermission()'s is_admin bypass).
        // Formal creation of a super-admin USER is `php artisan superadmin:create` (commit 7,
        // not yet implemented as of this seeder update) — this just guarantees the role itself
        // exists so that command (and the temporary seeded account below) has something to attach.
        Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
    }
}
