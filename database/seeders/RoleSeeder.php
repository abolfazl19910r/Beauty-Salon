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

    }
}
