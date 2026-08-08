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
        ]);

        if (! empty($data['roles'])) {
            $user->roles()->sync($data['roles']);
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

        $user->roles()->sync($data['roles'] ?? []);

        return $user;
    }

    /**
     * @return int Number of available turns; if zero, it means the deletion was done
     */
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
        $user->roles()->sync($roles);
    }
}
