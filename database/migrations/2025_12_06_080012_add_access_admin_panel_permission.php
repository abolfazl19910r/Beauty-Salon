<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::create([
            'name' => 'access_admin_panel',
            'label' => 'دسترسی به پنل مدیریت',
            'group' => 'تنظیمات',
            'description' => 'دسترسی به پنل مدیریت سیستم'
        ]);
    }

    public function down(): void
    {
        Permission::where('name', 'access_admin_panel')->delete();
    }
};
