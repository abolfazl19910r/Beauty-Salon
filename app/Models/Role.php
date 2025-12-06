<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function assignToUser(User $user): null
    {
        return $this->users()->attach($user);
    }

    public function removeFromUser(User $user): int
    {
        return $this->users()->detach($user);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching($permission);
        return $this;
    }

    public function getAllPermissions()
    {
        return $this->permissions;
    }

    public function hasPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions->contains('name', $permission);
        }

        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if ($this->hasPermission($perm)) {
                    return true;
                }
            }
            return false;
        }

        return $this->permissions->contains($permission);
    }
}
