<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'access_admin_panel')->first();

        if ($permission) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching($permission->id);
            }

            $specialistRole = Role::where('name', 'specialists')->first();
            if ($specialistRole) {
                $specialistRole->permissions()->syncWithoutDetaching($permission->id);
            }
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'access_admin_panel')->first();

        if ($permission) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->detach($permission->id);
            }

            $specialistRole = Role::where('name', 'specialists')->first();
            if ($specialistRole) {
                $specialistRole->permissions()->detach($permission->id);
            }
        }
    }
};
