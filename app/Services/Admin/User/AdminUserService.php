<?php

namespace App\Services\Admin\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'],
            'phone_verified_at' => $data['is_active'] ? now() : null,
            'user_type' => 'staff',
        ]);

        if (! empty($data['roles'])) {
            $user->roles()->sync($this->filterAssignableRoles($data['roles'] ?? []));
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'is_admin' => $data['is_admin'],
            'phone_verified_at' => $data['is_active']
                ? ($user->phone_verified_at ?? now())
                : null,
        ]);

        $user->roles()->sync($this->filterAssignableRoles($data['roles'] ?? []));

        return $user;
    }

    public function delete(User $user): int
    {
        $bookingsCount = $user->bookings()->count();
        if ($bookingsCount > 0) {
            return $bookingsCount;
        }

        $user->roles()->detach();
        $user->delete();

        return 0;
    }

    public function updateStatus(User $user, bool $activate): void
    {
        $user->forceFill([
            'phone_verified_at' => $activate ? ($user->phone_verified_at ?? now()) : null,
        ])->save();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
    }

    public function syncRoles(User $user, array $roles): void
    {
        // ⭐ Fix (session 7): was referencing an undefined $data variable instead of the
        // $roles parameter — every call silently stripped ALL of the target user's roles
        // regardless of what was submitted, since $data['roles'] ?? [] always resolved to [].
        $user->roles()->sync($this->filterAssignableRoles($roles));
    }

	private function filterAssignableRoles(array $roleIds): array
	{
		if (auth()->user()?->hasRole('super-admin')) {
			return $roleIds;
		}

		$superRoleId = \App\Models\Role::where('name', 'super-admin')->value('id');

		return array_values(array_diff($roleIds, array_filter([$superRoleId])));
	}
}
