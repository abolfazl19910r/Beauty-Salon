<?php

namespace App\Traits;

use App\Models\Role;

trait HasRoles
{
    protected $permissionsCache = null;

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return (bool) $role->intersect($this->roles)->count();
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function assignRole($role): static
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching($role);

        return $this;
    }

    public function removeRole($role): static
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role);

        return $this;
    }

    public function syncRoles($roles): static
    {
        if (is_string($roles)) {
            $roles = Role::where('name', $roles)->get();
        }

        $this->roles()->sync($roles);

        return $this;
    }

    public function getAllPermissions()
    {
        if (! is_null($this->permissionsCache)) {
            return $this->permissionsCache;
        }

        $this->permissionsCache = $this->roles->load('permissions')
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('name')
            ->unique();

        return $this->permissionsCache;
    }

    public function hasPermissionTo(string $permissionName): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->getAllPermissions()->contains($permissionName);
    }

    public function hasAnyPermissionTo($permissions): bool
    {
        return collect($permissions)->some(fn ($permission) => $this->hasPermissionTo($permission));
    }
}
