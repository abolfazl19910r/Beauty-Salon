<?php

namespace App\Models;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'name',
        'label',
    ];

    /**
     * salon_id = null: نقش سیستمی (مشترک، فقط مدیر پلتفرم تغییرش می‌دهد). وقتی CurrentSalon ست است، فقط نقش‌های سیستمی و
     * نقش‌های همان سالن دیده می‌شوند — شامل نقش‌های کاربر ($user->roles) و hasRole/hasPermission.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('salon', function (Builder $builder) {
            $salonId = app(CurrentSalon::class)->id();

            if ($salonId !== null) {
                $builder->where(fn (Builder $q) => $q->whereNull('roles.salon_id')->orWhere('roles.salon_id', $salonId));
            }
        });
    }

    public function isSystem(): bool
    {
        return $this->salon_id === null;
    }

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
            $permission = app(PermissionRepositoryInterface::class)->findByNameOrFail($permission);
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
